<?php

namespace App\Http\Controllers;

use App\Models\Badge;
use App\Models\User;
use Illuminate\Http\Request;

class BadgeController extends Controller
{
    /**
     * Listar todos os badges disponíveis
     */
    public function index()
    {
        $badges = Badge::where('ativo', true)
                      ->orderBy('ordem')
                      ->get();
        
        return response()->json([
            'success' => true,
            'badges' => $badges
        ]);
    }

    /**
     * Badges do usuário autenticado
     */
    public function meusBadges(Request $request)
    {
        $user = $request->user();
        
        $badges = $user->badges()
                      ->orderBy('user_badges.conquistado_em', 'desc')
                      ->get();
        
        return response()->json([
            'success' => true,
            'badges' => $badges,
            'total' => $badges->count()
        ]);
    }

    /**
     * Verificar novos badges para o usuário
     */
    public function verificarNovos(Request $request)
    {
        $user = $request->user();
        $novos_badges = $user->verificarBadges();
        
        return response()->json([
            'success' => true,
            'novos_badges' => $novos_badges,
            'total_novos' => count($novos_badges)
        ]);
    }

    /**
     * Ranking de usuários por badges
     */
    public function ranking()
    {
        $ranking = User::withCount('badges')
                      ->where('perfil', 'cliente')
                      ->orderBy('badges_count', 'desc')
                      ->orderBy('pontos_lifetime', 'desc')
                      ->limit(50)
                      ->get()
                      ->map(function($user, $index) {
                          return [
                              'posicao' => $index + 1,
                              'nome' => $user->name,
                              'badges_count' => $user->badges_count,
                              'pontos_lifetime' => $user->pontos_lifetime,
                              'nivel' => $user->calcularNivel()
                          ];
                      });
        
        return response()->json([
            'success' => true,
            'ranking' => $ranking
        ]);
    }

    /**
     * Detalhes de um badge específico
     */
    public function show($id)
    {
        $badge = Badge::find($id);
        
        if (!$badge) {
            return response()->json([
                'success' => false,
                'message' => 'Badge não encontrado'
            ], 404);
        }
        
        // Estatísticas do badge
        $total_conquistadores = $badge->users()->count();
        $usuarios_recentes = $badge->users()
                                  ->orderBy('user_badges.conquistado_em', 'desc')
                                  ->limit(10)
                                  ->get(['name', 'user_badges.conquistado_em']);
        
        return response()->json([
            'success' => true,
            'badge' => $badge,
            'estatisticas' => [
                'total_conquistadores' => $total_conquistadores,
                'usuarios_recentes' => $usuarios_recentes
            ]
        ]);
    }

    /**
     * Progresso do usuário em direção aos badges
     */
    public function progresso(Request $request)
    {
        $user = $request->user();
        $badges_disponiveis = Badge::where('ativo', true)
                                  ->orderBy('ordem')
                                  ->get();
        
        $progresso = [];
        
        foreach ($badges_disponiveis as $badge) {
            $conquistado = $user->badges()->where('badge_id', $badge->id)->exists();
            
            if (!$conquistado) {
                $progresso_atual = $this->calcularProgressoBadge($user, $badge);
                
                $progresso[] = [
                    'badge' => $badge,
                    'progresso_atual' => $progresso_atual,
                    'progresso_percentual' => min(100, ($progresso_atual / $badge->condicao_valor) * 100)
                ];
            }
        }
        
        return response()->json([
            'success' => true,
            'progresso' => $progresso
        ]);
    }

    /**
     * Calcular progresso específico para um badge
     */
    private function calcularProgressoBadge(User $user, Badge $badge)
    {
        switch ($badge->condicao_tipo) {
            case 'pontos':
                return $user->pontos_lifetime ?? 0;
            
            case 'checkins':
                return $user->checkIns()->count();
            
            case 'empresas_visitadas':
                return $user->empresas_visitadas ?? 0;
            
            case 'dias_consecutivos':
                return $user->dias_consecutivos ?? 0;
            
            case 'valor_gasto':
                return $user->valor_gasto_total ?? 0;
            
            case 'nivel':
                return $user->nivel ?? 1;
            
            default:
                return 0;
        }
    }
}