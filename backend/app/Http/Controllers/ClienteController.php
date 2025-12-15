<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\BonusAniversario;
use App\Models\CartaoFidelidade;
use App\Models\BonusAdesao;
use Carbon\Carbon;

class ClienteController extends Controller
{
    /**
     * Verificar se cliente tem bônus de aniversário disponível
     */
    public function verificarAniversario(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user || $user->perfil !== 'cliente') {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não autorizado'
                ], 403);
            }

            // Verificar se hoje é aniversário
            if (!$user->data_nascimento) {
                return response()->json([
                    'success' => true,
                    'tem_bonus' => false
                ]);
            }

            $hoje = Carbon::today();
            $aniversario = Carbon::parse($user->data_nascimento);
            
            $ehAniversario = $hoje->month === $aniversario->month && 
                           $hoje->day === $aniversario->day;

            if (!$ehAniversario) {
                return response()->json([
                    'success' => true,
                    'tem_bonus' => false
                ]);
            }

            // Verificar se já resgatou este ano
            $jaResgatou = BonusAniversario::where('user_id', $user->id)
                ->whereYear('data_resgate', $hoje->year)
                ->exists();

            if ($jaResgatou) {
                return response()->json([
                    'success' => true,
                    'tem_bonus' => false,
                    'message' => 'Bônus já resgatado este ano'
                ]);
            }

            // Tem bônus disponível
            $pontosBonus = 100; // Padrão: 100 pontos

            return response()->json([
                'success' => true,
                'tem_bonus' => true,
                'pontos' => $pontosBonus,
                'mensagem' => 'Feliz aniversário! Você ganhou pontos extras!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao verificar aniversário: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resgatar bônus de aniversário
     */
    public function resgatarBonusAniversario(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user || $user->perfil !== 'cliente') {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não autorizado'
                ], 403);
            }

            $hoje = Carbon::today();
            $aniversario = Carbon::parse($user->data_nascimento);
            
            $ehAniversario = $hoje->month === $aniversario->month && 
                           $hoje->day === $aniversario->day;

            if (!$ehAniversario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bônus disponível apenas no dia do aniversário'
                ], 400);
            }

            // Verificar se já resgatou
            $jaResgatou = BonusAniversario::where('user_id', $user->id)
                ->whereYear('data_resgate', $hoje->year)
                ->exists();

            if ($jaResgatou) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bônus já resgatado este ano'
                ], 400);
            }

            $pontosBonus = 100;

            // Registrar resgate
            BonusAniversario::create([
                'user_id' => $user->id,
                'pontos' => $pontosBonus,
                'data_resgate' => now(),
                'ano' => $hoje->year
            ]);

            // Adicionar pontos ao usuário
            $user->pontos += $pontosBonus;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Bônus resgatado com sucesso!',
                'pontos_ganhos' => $pontosBonus,
                'pontos_totais' => $user->pontos
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao resgatar bônus: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar cartões fidelidade do cliente
     */
    public function cartoesFidelidade(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user || $user->perfil !== 'cliente') {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não autorizado'
                ], 403);
            }

            $cartoes = CartaoFidelidade::where('user_id', $user->id)
                ->with('empresa:id,name')
                ->get()
                ->map(function ($cartao) {
                    return [
                        'id' => $cartao->id,
                        'empresa_id' => $cartao->empresa_id,
                        'empresa_nome' => $cartao->empresa->name ?? 'Empresa',
                        'categoria' => $cartao->categoria ?? 'Geral',
                        'carimbos_atual' => $cartao->carimbos_atual ?? 0,
                        'carimbos_necessarios' => $cartao->carimbos_necessarios ?? 10,
                        'recompensa' => $cartao->recompensa ?? 'Brinde especial',
                        'descricao' => $cartao->descricao ?? 'Acumule carimbos e ganhe prêmios!',
                        'validade' => $cartao->validade,
                        'ativo' => $cartao->ativo ?? true
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $cartoes
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar cartões: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verificar bônus de adesão disponível em uma empresa
     */
    public function verificarBonusAdesao(Request $request, $empresaId)
    {
        try {
            $user = Auth::user();
            
            if (!$user || $user->perfil !== 'cliente') {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não autorizado'
                ], 403);
            }

            // Verificar se já resgatou bônus desta empresa
            $jaResgatou = BonusAdesao::where('user_id', $user->id)
                ->where('empresa_id', $empresaId)
                ->exists();

            if ($jaResgatou) {
                return response()->json([
                    'success' => true,
                    'tem_bonus' => false,
                    'message' => 'Bônus já resgatado nesta empresa'
                ]);
            }

            // Verificar se empresa oferece bônus de adesão
            $bonus = BonusAdesao::where('empresa_id', $empresaId)
                ->where('ativo', true)
                ->first();

            if (!$bonus) {
                return response()->json([
                    'success' => true,
                    'tem_bonus' => false
                ]);
            }

            return response()->json([
                'success' => true,
                'tem_bonus' => true,
                'pontos' => $bonus->pontos ?? 50,
                'descricao' => $bonus->descricao ?? 'Ganhe pontos no seu primeiro check-in!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao verificar bônus: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resgatar bônus de adesão
     */
    public function resgatarBonusAdesao(Request $request, $bonusId)
    {
        try {
            $user = Auth::user();
            
            if (!$user || $user->perfil !== 'cliente') {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não autorizado'
                ], 403);
            }

            $bonus = BonusAdesao::findOrFail($bonusId);

            // Verificar se já resgatou
            $jaResgatou = BonusAdesao::where('user_id', $user->id)
                ->where('empresa_id', $bonus->empresa_id)
                ->exists();

            if ($jaResgatou) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bônus já resgatado'
                ], 400);
            }

            // Registrar resgate
            BonusAdesao::create([
                'user_id' => $user->id,
                'empresa_id' => $bonus->empresa_id,
                'pontos' => $bonus->pontos,
                'data_resgate' => now(),
                'ativo' => false
            ]);

            // Adicionar pontos
            $user->pontos += $bonus->pontos;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Bônus resgatado com sucesso!',
                'pontos_ganhos' => $bonus->pontos,
                'pontos_totais' => $user->pontos
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao resgatar bônus: ' . $e->getMessage()
            ], 500);
        }
    }
}
