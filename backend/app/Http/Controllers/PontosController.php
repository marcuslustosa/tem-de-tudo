<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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
            $modoCompleto = $request->hasFile('foto_cupom')
                || $request->filled('qr_code_id')
                || $request->filled('latitude')
                || $request->filled('longitude');

            if ($modoCompleto) {
                $request->validate([
                    'empresa_id'   => 'required|exists:empresas,id',
                    'valor_compra' => 'required|numeric|min:0.01',
                    'foto_cupom'   => 'required|file|mimes:jpeg,jpg,png|max:5120',
                    'latitude'     => 'required|numeric|between:-90,90',
                    'longitude'    => 'required|numeric|between:-180,180',
                    'observacoes'  => 'nullable|string|max:500',
                    'qr_code_id'   => 'required|exists:qr_codes,id',
                ]);
            } else {
                $request->validate([
                    'empresa_id'   => 'required|exists:empresas,id',
                    'valor_compra' => 'required|numeric|min:0.01',
                    'observacoes'  => 'nullable|string|max:500',
                ]);
            }

            $user = Auth::user();
            $empresa = Empresa::findOrFail($request->empresa_id);

            if (!$modoCompleto) {
                $pontosCalculados = $this->calcularPontos((float) $request->valor_compra, $empresa);
                $user->increment('pontos', $pontosCalculados);

                if (Schema::hasTable('pontos')) {
                    Ponto::create([
                        'user_id' => $user->id,
                        'empresa_id' => $empresa->id,
                        'pontos' => $pontosCalculados,
                        'descricao' => $request->observacoes ?: "Acumulo de pontos em {$empresa->nome} - R$ " . number_format((float) $request->valor_compra, 2, ',', '.'),
                        'tipo' => 'earn',
                    ]);
                }

                $this->registrarAtividade(
                    $user->id,
                    'checkin_manual',
                    "Acumulo manual no {$empresa->nome}",
                    $pontosCalculados
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Pontos acumulados com sucesso.',
                    'data' => [
                        'modo' => 'manual',
                        'empresa_id' => $empresa->id,
                        'pontos_calculados' => $pontosCalculados,
                        'total_pontos' => $user->fresh()->pontos,
                    ],
                ]);
            }

            // Garantir geolocalizacao configurada na empresa
            if (!$empresa->latitude || !$empresa->longitude) {
                return response()->json([
                    'success' => false,
                    'message' => 'Empresa sem coordenadas configuradas. Cadastre latitude/longitude para habilitar check-in com validacao de presenca.',
                ], 422);
            }

            // Validar QR code pertence a empresa e esta ativo
            $qrCode = QRCode::where('id', $request->qr_code_id)
                ->where('active', true)
                ->first();

            if (!$qrCode || $qrCode->empresa_id !== $empresa->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'QR Code invalido para este estabelecimento.',
                ], 400);
            }

            // Antifraude: exigir proximidade fisica
            $distanciaKm = $this->calcularDistanciaKm(
                (float) $request->latitude,
                (float) $request->longitude,
                (float) $empresa->latitude,
                (float) $empresa->longitude
            );

            if ($distanciaKm > self::MAX_CHECKIN_DISTANCE_KM) {
                return response()->json([
                    'success' => false,
                    'message' => 'Voce precisa estar no local para fazer check-in (raio maximo de ' . (self::MAX_CHECKIN_DISTANCE_KM * 1000) . 'm).',
                ], 403);
            }

            // Verificar se ja fez check-in na empresa hoje
            $checkinHoje = CheckIn::where('user_id', $user->id)
                ->where('empresa_id', $empresa->id)
                ->whereDate('created_at', today())
                ->first();

            if ($checkinHoje) {
                return response()->json([
                    'success' => false,
                    'message' => 'Voce ja fez check-in neste estabelecimento hoje. Apenas um check-in por estabelecimento por dia e permitido.',
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
            $pontosCalculados = $this->calcularPontos((float) $request->valor_compra, $empresa);

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
                'bonus_applied' => false,
            ]);

            // Adicionar pontos pendentes ao usuario
            $user->increment('pontos_pendentes', $pontosCalculados);

            // Log da atividade
            $this->registrarAtividade(
                $user->id,
                'checkin_pending',
                "Check-in pendente no {$empresa->nome} - R$ " . number_format((float) $request->valor_compra, 2, ',', '.'),
                $pontosCalculados
            );

            return response()->json([
                'success' => true,
                'message' => 'Check-in registrado com sucesso! Seus pontos estao em validacao.',
                'data' => [
                    'checkin_id' => $checkin->id,
                    'pontos_calculados' => $pontosCalculados,
                    'codigo_validacao' => $checkin->codigo_validacao,
                    'status' => 'pending',
                    'pontos_pendentes' => $user->fresh()->pontos_pendentes,
                    'total_pontos' => $user->fresh()->pontos,
                ],
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dados de check-in invalidos.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erro no check-in', ['error' => $e->getMessage(), 'request' => $request->all()]);
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor.',
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

        // Multiplicador baseado na configuração da empresa
        $multiplicador = $empresa->getPointsMultiplier($valorCompra);
        
        // Aplicar multiplicador
        $pontosFinais = floor($pontosBase * $multiplicador);

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
            $query = Coupon::query()->where('user_id', Auth::id());
            $cupom = ctype_digit((string) $cupomId)
                ? (clone $query)->where('id', (int) $cupomId)->first()
                : (clone $query)->where('codigo', (string) $cupomId)->first();

            if (!$cupom) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cupom nao encontrado para este usuario.'
                ], 404);
            }

            if ($cupom->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Este cupom nao esta mais valido.'
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
            Log::error('Erro ao usar cupom', [
                'cupom' => $cupomId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor ao usar cupom.'
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
        if ($pontos >= 5000) {
            return ['nome' => 'Platina', 'cor' => '#e5e4e2', 'min' => 5000, 'proximo' => null];
        } elseif ($pontos >= 1500) {
            return ['nome' => 'Ouro', 'cor' => '#ffd700', 'min' => 1500, 'proximo' => 5000];
        } elseif ($pontos >= 500) {
            return ['nome' => 'Prata', 'cor' => '#c0c0c0', 'min' => 500, 'proximo' => 1500];
        }

        return ['nome' => 'Bronze', 'cor' => '#cd7f32', 'min' => 0, 'proximo' => 500];
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
        $fallback = [
            'checkins_hoje' => 0,
            'checkins_ontem' => 0,
            'checkins_pendentes' => 0,
            'pontos_distribuidos_mes' => 0,
            'cupons_resgatados_mes' => 0,
            'usuarios_ativos_mes' => 0,
        ];

        try {
            $hoje = today();
            $ontem = $hoje->copy()->subDay();
            $esteMes = now()->startOfMonth();
            $hasCheckins = Schema::hasTable('checkins');
            $hasPontos = Schema::hasTable('pontos');
            $hasCoupons = Schema::hasTable('coupons');
            $checkinsHasCreatedAt = $hasCheckins && Schema::hasColumn('checkins', 'created_at');

            $checkinsPendentes = 0;
            if ($hasCheckins) {
                $pendingQuery = CheckIn::query();
                if (Schema::hasColumn('checkins', 'status')) {
                    $pendingQuery->whereIn('status', ['pending', 'pendente']);
                } elseif (Schema::hasColumn('checkins', 'aprovado')) {
                    $pendingQuery->where('aprovado', false);
                } elseif (Schema::hasColumn('checkins', 'approved_at')) {
                    $pendingQuery->whereNull('approved_at');
                } else {
                    // Sem coluna de estado, nao bloqueia endpoint por schema diferente.
                    $pendingQuery->whereRaw('1 = 0');
                }
                $checkinsPendentes = $pendingQuery->count();
            }

            $pontosMes = 0;
            if ($hasPontos && Schema::hasColumn('pontos', 'pontos')) {
                $pontosMesQuery = Ponto::query();
                if (Schema::hasColumn('pontos', 'created_at')) {
                    $pontosMesQuery->where('created_at', '>=', $esteMes);
                }
                if (Schema::hasColumn('pontos', 'pontos')) {
                    $pontosMesQuery->where('pontos', '>', 0);
                }
                $pontosMes = (int) $pontosMesQuery->sum('pontos');
            }

            $cuponsMes = 0;
            if ($hasCoupons) {
                $couponQuery = Coupon::query();
                if (Schema::hasColumn('coupons', 'created_at')) {
                    $couponQuery->where('created_at', '>=', $esteMes);
                }
                $cuponsMes = $couponQuery->count();
            }

            $usuariosAtivosMes = 0;
            if ($hasCheckins) {
                $ativosQuery = CheckIn::query();
                if (Schema::hasColumn('checkins', 'created_at')) {
                    $ativosQuery->where('created_at', '>=', $esteMes);
                }
                $usuariosAtivosMes = $ativosQuery->distinct('user_id')->count();
            }

            $stats = [
                'checkins_hoje' => $checkinsHasCreatedAt ? CheckIn::whereDate('created_at', $hoje)->count() : 0,
                'checkins_ontem' => $checkinsHasCreatedAt ? CheckIn::whereDate('created_at', $ontem)->count() : 0,
                'checkins_pendentes' => $checkinsPendentes,
                'pontos_distribuidos_mes' => $pontosMes,
                'cupons_resgatados_mes' => $cuponsMes,
                'usuarios_ativos_mes' => $usuariosAtivosMes,
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::warning('PontosController@estatisticas fallback', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => true,
                'warning' => 'Falha parcial ao consolidar estatisticas.',
                'data' => $fallback,
            ], 200);
        }
    }

    /**
     * DistÃ¢ncia entre duas coordenadas (Haversine) em KM
     */
    private function calcularDistanciaKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $raioTerraKm = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $raioTerraKm * $c;
    }

    private const MAX_CHECKIN_DISTANCE_KM = 0.2; // 200 metros
}
