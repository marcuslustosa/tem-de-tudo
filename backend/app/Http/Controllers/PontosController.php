<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Empresa;
use App\Models\Ponto;
use App\Models\CheckIn;
use App\Models\Coupon;
use App\Models\QRCode;

class PontosController extends Controller
{
    /**
     * Registrar um check-in e calcular pontos
     */
    public function checkin(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'empresa_id' => 'required|exists:empresas,id',
                'valor_compra' => 'required|numeric|min:0.01',
                'foto_cupom' => 'required|file|mimes:jpeg,jpg,png|max:5120', // 5MB
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'observacoes' => 'nullable|string|max:500',
                'qr_code_id' => 'nullable|exists:qr_codes,id'
            ]);

            $user = Auth::user();
            $empresa = Empresa::findOrFail($request->empresa_id);

            // Verificar se já fez check-in na empresa hoje
            $checkinHoje = CheckIn::where('user_id', $user->id)
                ->where('empresa_id', $empresa->id)
                ->whereDate('created_at', today())
                ->first();

            if ($checkinHoje) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você já fez check-in neste estabelecimento hoje. Apenas um check-in por estabelecimento por dia é permitido.'
                ], 400);
            }

            // Salvar foto do cupom
            $fotoPath = null;
            if ($request->hasFile('foto_cupom')) {
                $foto = $request->file('foto_cupom');
                $fileName = 'cupom_' . $user->id . '_' . time() . '.' . $foto->getClientOriginalExtension();
                $fotoPath = $foto->storeAs('cupons', $fileName, 'public');
            }

            // Calcular pontos baseado no valor da compra
            $pontosCalculados = $this->calcularPontos($request->valor_compra, $empresa);

            // Criar o check-in (inicialmente pendente)
            $checkin = CheckIn::create([
                'user_id' => $user->id,
                'empresa_id' => $empresa->id,
                'qr_code_id' => $request->qr_code_id,
                'valor_compra' => $request->valor_compra,
                'pontos_calculados' => $pontosCalculados,
                'foto_cupom' => $fotoPath,
                'status' => 'pending',
                'codigo_validacao' => strtoupper(Str::random(8)),
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'observacoes' => $request->observacoes,
                'bonus_applied' => false
            ]);

            // Adicionar pontos pendentes ao usuário
            $user->increment('pontos_pendentes', $pontosCalculados);

            // Log da atividade
            $this->registrarAtividade($user->id, 'checkin_pending',
                "Check-in pendente no {$empresa->nome} - R$ " . number_format($request->valor_compra, 2, ',', '.'),
                $pontosCalculados);

            return response()->json([
                'success' => true,
                'message' => 'Check-in registrado com sucesso! Seus pontos estão em validação.',
                'data' => [
                    'checkin_id' => $checkin->id,
                    'pontos_calculados' => $pontosCalculados,
                    'codigo_validacao' => $checkin->codigo_validacao,
                    'status' => 'pending',
                    'pontos_pendentes' => $user->fresh()->pontos_pendentes,
                    'total_pontos' => $user->fresh()->pontos
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro no check-in', ['error' => $e->getMessage(), 'request' => $request->all()]);
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calcular pontos baseado no valor da compra e regras da empresa
     */
    private function calcularPontos(float $valorCompra, Empresa $empresa): int
    {
        // Regra base: 1 ponto a cada R$ 1,00 gasto
        $pontosBase = floor($valorCompra);

        // Multiplicador baseado no nível do usuário (se configurado)
        $multiplicador = $empresa->getPointsMultiplier($valorCompra);
        
        // Aplicar multiplicador
        $pontosFinais = floor($pontosBase * $multiplicador);

        // Bônus por faixa de valor
        if ($valorCompra >= 100) {
            $pontosFinais += 50; // Bônus para compras acima de R$ 100
        } elseif ($valorCompra >= 50) {
            $pontosFinais += 20; // Bônus para compras acima de R$ 50
        }

        // Garantir mínimo de 1 ponto para qualquer compra
        return max(1, $pontosFinais);
    }

    /**
     * Aprovar um check-in (admin)
     */
    public function aprovarCheckin(Request $request, $checkinId): JsonResponse
    {
        try {
            $request->validate([
                'aprovado' => 'required|boolean',
                'motivo_rejeicao' => 'nullable|string|max:500'
            ]);

            $checkin = CheckIn::findOrFail($checkinId);
            $user = $checkin->user;

            if ($checkin->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Este check-in já foi processado.'
                ], 400);
            }

            if ($request->aprovado) {
                // Aprovar - transferir pontos pendentes para confirmados
                $checkin->update([
                    'status' => 'approved',
                    'aprovado_em' => now(),
                    'aprovado_por' => Auth::id()
                ]);

                // Transferir pontos
                $user->decrement('pontos_pendentes', $checkin->pontos_calculados);
                $user->increment('pontos', $checkin->pontos_calculados);

                // Atualizar QR code se foi usado
                if ($checkin->qr_code_id) {
                    $qrCode = QRCode::find($checkin->qr_code_id);
                    if ($qrCode) {
                        $qrCode->incrementarUso();
                    }
                }

                // Registrar no histórico de pontos
                Ponto::create([
                    'user_id' => $user->id,
                    'empresa_id' => $checkin->empresa_id,
                    'checkin_id' => $checkin->id,
                    'pontos' => $checkin->pontos_calculados,
                    'descricao' => "Check-in aprovado no {$checkin->empresa->nome} - R$ " . number_format($checkin->valor_compra, 2, ',', '.'),
                    'tipo' => 'earn'
                ]);

                // Verificar se atingiu novo nível
                $this->verificarNivel($user);

                $message = 'Check-in aprovado com sucesso!';

            } else {
                // Rejeitar - remover pontos pendentes
                $checkin->update([
                    'status' => 'rejected',
                    'rejeitado_em' => now(),
                    'motivo_rejeicao' => $request->motivo_rejeicao,
                    'rejeitado_por' => Auth::id()
                ]);

                $user->decrement('pontos_pendentes', $checkin->pontos_calculados);

                $message = 'Check-in rejeitado.';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'checkin' => $checkin->fresh(),
                    'user_pontos' => $user->fresh()->pontos,
                    'user_pontos_pendentes' => $user->fresh()->pontos_pendentes
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resgatar pontos por uma recompensa
     */
    public function resgatarPontos(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'recompensa_tipo' => 'required|string',
                'custo_pontos' => 'required|integer|min:1',
                'descricao' => 'required|string|max:255'
            ]);

            $user = Auth::user();

            if ($user->pontos < $request->custo_pontos) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pontos insuficientes para este resgate.'
                ], 400);
            }

            // Deduzir pontos
            $user->decrement('pontos', $request->custo_pontos);

            // Gerar cupom
            $cupom = Coupon::create([
                'user_id' => $user->id,
                'codigo' => 'TDT' . strtoupper(Str::random(6)),
                'tipo' => $request->recompensa_tipo,
                'descricao' => $request->descricao,
                'custo_pontos' => $request->custo_pontos,
                'status' => 'active', // active, used, expired
                'expira_em' => Carbon::now()->addDays(30), // Válido por 30 dias
                'dados_extra' => json_encode($request->only(['valor_desconto', 'porcentagem_desconto', 'empresa_id']))
            ]);

            // Registrar no histórico
            Ponto::create([
                'user_id' => $user->id,
                'empresa_id' => null,
                'coupon_id' => $cupom->id,
                'pontos' => -$request->custo_pontos,
                'descricao' => "Resgatado: {$request->descricao}",
                'tipo' => 'redeem'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Recompensa resgatada com sucesso!',
                'data' => [
                    'cupom' => $cupom,
                    'pontos_restantes' => $user->fresh()->pontos
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Usar um cupom
     */
    public function usarCupom(Request $request, $cupomId): JsonResponse
    {
        try {
            $cupom = Coupon::where('id', $cupomId)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            if ($cupom->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Este cupom não está mais válido.'
                ], 400);
            }

            if ($cupom->expira_em && Carbon::now()->gt($cupom->expira_em)) {
                $cupom->update(['status' => 'expired']);
                return response()->json([
                    'success' => false,
                    'message' => 'Este cupom expirou.'
                ], 400);
            }

            // Marcar como usado
            $cupom->update([
                'status' => 'used',
                'usado_em' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cupom utilizado com sucesso!',
                'data' => $cupom->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obter dados do usuário (pontos, nível, etc.)
     */
    public function meusDados(): JsonResponse
    {
        try {
            $user = Auth::user();
            $nivel = $this->calcularNivel($user->pontos);

            return response()->json([
                'success' => true,
                'data' => [
                    'pontos_total' => $user->pontos,
                    'pontos_pendentes' => $user->pontos_pendentes,
                    'nivel' => $nivel,
                    'checkins_total' => CheckIn::where('user_id', $user->id)->where('status', 'approved')->count(),
                    'cupons_ativos' => Coupon::where('user_id', $user->id)->where('status', 'active')->count()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Histórico de pontos do usuário
     */
    public function historicoPontos(): JsonResponse
    {
        try {
            $historico = Ponto::where('user_id', Auth::id())
                ->with(['empresa:id,nome', 'coupon:id,descricao'])
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $historico
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Meus cupons
     */
    public function meusCupons(): JsonResponse
    {
        try {
            $cupons = Coupon::where('user_id', Auth::id())
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $cupons
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check-ins pendentes (para admin)
     */
    public function checkinsPendentes(): JsonResponse
    {
        try {
            $checkins = CheckIn::with(['user:id,nome,email', 'empresa:id,nome'])
                ->where('status', 'pending')
                ->orderBy('created_at', 'asc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $checkins
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calcular nível do usuário
     */
    private function calcularNivel(int $pontos): array
    {
        if ($pontos >= 10000) {
            return ['nome' => 'Diamante', 'cor' => '#b9f2ff', 'min' => 10000, 'proximo' => null];
        } elseif ($pontos >= 5000) {
            return ['nome' => 'Ouro', 'cor' => '#ffd700', 'min' => 5000, 'proximo' => 10000];
        } elseif ($pontos >= 1000) {
            return ['nome' => 'Prata', 'cor' => '#c0c0c0', 'min' => 1000, 'proximo' => 5000];
        }

        return ['nome' => 'Bronze', 'cor' => '#cd7f32', 'min' => 0, 'proximo' => 1000];
    }

    /**
     * Verificar se usuário atingiu novo nível
     */
    private function verificarNivel(User $user): void
    {
        $nivelAtual = $this->calcularNivel($user->pontos);
        $nivelAnterior = $this->calcularNivel($user->pontos - $user->pontos_pendentes);

        if ($nivelAtual['nome'] !== $nivelAnterior['nome']) {
            // Usuário subiu de nível - pode enviar notificação
            // Implementar lógica de notificação aqui
        }
    }

    /**
     * Registrar atividade no sistema
     */
    private function registrarAtividade(int $userId, string $tipo, string $descricao, int $pontos): void
    {
        try {
            // Criar registro de histórico de pontos
            Ponto::create([
                'user_id' => $userId,
                'pontos' => $pontos,
                'tipo' => $tipo,
                'descricao' => $descricao,
                'created_at' => now()
            ]);

            \Log::info("Atividade registrada: {$tipo} - User: {$userId} - Pontos: {$pontos}");
        } catch (\Exception $e) {
            \Log::error("Erro ao registrar atividade: " . $e->getMessage());
        }
    }

    /**
     * Estatísticas para dashboard admin
     */
    public function estatisticas(): JsonResponse
    {
        try {
            $hoje = today();
            $ontem = $hoje->copy()->subDay();
            $esteMes = now()->startOfMonth();

            $stats = [
                'checkins_hoje' => CheckIn::whereDate('created_at', $hoje)->count(),
                'checkins_ontem' => CheckIn::whereDate('created_at', $ontem)->count(),
                'checkins_pendentes' => CheckIn::where('status', 'pending')->count(),
                'pontos_distribuidos_mes' => Ponto::where('created_at', '>=', $esteMes)->where('pontos', '>', 0)->sum('pontos'),
                'cupons_resgatados_mes' => Coupon::where('created_at', '>=', $esteMes)->count(),
                'usuarios_ativos_mes' => CheckIn::where('created_at', '>=', $esteMes)->distinct('user_id')->count()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor: ' . $e->getMessage()
            ], 500);
        }
    }
}