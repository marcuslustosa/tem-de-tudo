<?php

namespace App\Services;

use App\Models\User;
use App\Models\CheckIn;
use App\Models\Ledger;
use App\Models\Badge;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class LeaderboardService
{
    /**
     * Ranking geral por pontos lifetime com cache de 5 minutos.
     */
    public function globalRanking(int $limit = 100, int $offset = 0): array
    {
        $cacheKey = "leaderboard_global_{$limit}_{$offset}";

        return Cache::remember($cacheKey, 300, function () use ($limit, $offset) {
            $users = User::select([
                    'id',
                    'name',
                    'pontos',
                    'pontos_lifetime',
                    'nivel',
                    'posicao_ranking',
                    'multiplicador_pontos',
                ])
                ->where('pontos_lifetime', '>', 0)
                ->orderBy('pontos_lifetime', 'desc')
                ->offset($offset)
                ->limit($limit)
                ->get()
                ->map(function ($user, $index) use ($offset) {
                    return [
                        'posicao' => $offset + $index + 1,
                        'user_id' => $user->id,
                        'name' => $user->name,
                        'pontos_atuais' => $user->pontos,
                        'pontos_lifetime' => $user->pontos_lifetime,
                        'nivel' => $user->nivel,
                        'multiplicador' => $user->multiplicador_pontos,
                        'badges_count' => $user->badges()->count(),
                    ];
                });

            return [
                'ranking' => $users->toArray(),
                'total_users' => User::where('pontos_lifetime', '>', 0)->count(),
                'updated_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Ranking por empresa (usuários que mais interagiram).
     */
    public function companyRanking(int $empresaId, int $limit = 50): array
    {
        $cacheKey = "leaderboard_company_{$empresaId}_{$limit}";

        return Cache::remember($cacheKey, 300, function () use ($empresaId, $limit) {
            $usersWithCheckins = CheckIn::where('empresa_id', $empresaId)
                ->select('user_id', DB::raw('COUNT(*) as total_checkins'), DB::raw('SUM(pontos_ganhos) as total_pontos'))
                ->groupBy('user_id')
                ->orderBy('total_pontos', 'desc')
                ->limit($limit)
                ->get();

            $ranking = $usersWithCheckins->map(function ($item, $index) {
                $user = User::find($item->user_id);
                if (!$user) {
                    return null;
                }

                return [
                    'posicao' => $index + 1,
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'total_checkins' => $item->total_checkins,
                    'total_pontos_empresa' => $item->total_pontos,
                    'pontos_lifetime' => $user->pontos_lifetime,
                    'nivel' => $user->nivel,
                ];
            })->filter()->values();

            return [
                'ranking' => $ranking->toArray(),
                'empresa_id' => $empresaId,
                'updated_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Ranking mensal (usuários que mais ganharam pontos no mês atual).
     */
    public function monthlyRanking(int $limit = 100): array
    {
        $cacheKey = "leaderboard_monthly_{$limit}_" . now()->format('Y_m');

        return Cache::remember($cacheKey, 300, function () use ($limit) {
            $startOfMonth = now()->startOfMonth();

            $rankings = Ledger::where('created_at', '>=', $startOfMonth)
                ->whereIn('type', ['earn', 'earn_bonus'])
                ->select('user_id', DB::raw('SUM(points) as pontos_mes'))
                ->groupBy('user_id')
                ->orderBy('pontos_mes', 'desc')
                ->limit($limit)
                ->get();

            $ranking = $rankings->map(function ($item, $index) {
                $user = User::find($item->user_id);
                if (!$user) {
                    return null;
                }

                return [
                    'posicao' => $index + 1,
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'pontos_mes_atual' => $item->pontos_mes,
                    'pontos_lifetime' => $user->pontos_lifetime,
                    'nivel' => $user->nivel,
                ];
            })->filter()->values();

            return [
                'ranking' => $ranking->toArray(),
                'mes' => now()->format('Y-m'),
                'updated_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Ranking de badges (usuários com mais badges conquistados).
     */
    public function badgeRanking(int $limit = 50): array
    {
        $cacheKey = "leaderboard_badges_{$limit}";

        return Cache::remember($cacheKey, 600, function () use ($limit) {
            $usersWithBadges = DB::table('user_badges')
                ->select('user_id', DB::raw('COUNT(*) as total_badges'))
                ->groupBy('user_id')
                ->orderBy('total_badges', 'desc')
                ->limit($limit)
                ->get();

            $ranking = $usersWithBadges->map(function ($item, $index) {
                $user = User::find($item->user_id);
                if (!$user) {
                    return null;
                }

                $badges = $user->badges()->get(['id', 'nome', 'icone']);

                return [
                    'posicao' => $index + 1,
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'total_badges' => $item->total_badges,
                    'badges' => $badges->toArray(),
                    'nivel' => $user->nivel,
                ];
            })->filter()->values();

            return [
                'ranking' => $ranking->toArray(),
                'updated_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Posição do usuário no ranking global.
     */
    public function getUserPosition(int $userId): array
    {
        $user = User::findOrFail($userId);

        // Calcula posição baseada em pontos lifetime
        $posicao = User::where('pontos_lifetime', '>', $user->pontos_lifetime)->count() + 1;

        // Usuários acima (top 3)
        $usuariosAcima = User::where('pontos_lifetime', '>', $user->pontos_lifetime)
            ->orderBy('pontos_lifetime', 'asc')
            ->limit(3)
            ->get(['id', 'name', 'pontos_lifetime', 'nivel'])
            ->reverse()
            ->values();

        // Usuários abaixo (próximos 3)
        $usuariosAbaixo = User::where('pontos_lifetime', '<', $user->pontos_lifetime)
            ->orderBy('pontos_lifetime', 'desc')
            ->limit(3)
            ->get(['id', 'name', 'pontos_lifetime', 'nivel']);

        return [
            'user_id' => $user->id,
            'posicao_global' => $posicao,
            'pontos_lifetime' => $user->pontos_lifetime,
            'nivel' => $user->nivel,
            'usuarios_acima' => $usuariosAcima->toArray(),
            'usuarios_abaixo' => $usuariosAbaixo->toArray(),
            'total_users' => User::where('pontos_lifetime', '>', 0)->count(),
        ];
    }

    /**
     * Limpa cache de leaderboards.
     */
    public function clearCache(?int $empresaId = null): void
    {
        if ($empresaId) {
            Cache::forget("leaderboard_company_{$empresaId}_50");
        } else {
            // Limpa todos os caches de leaderboard
            Cache::flush(); // Simplificado - em produção usar tags
        }
    }

    /**
     * Estatísticas gerais de gamificação.
     */
    public function getGamificationStats(): array
    {
        return Cache::remember('gamification_stats', 600, function () {
            return [
                'total_usuarios_ativos' => User::where('pontos_lifetime', '>', 0)->count(),
                'total_pontos_circulacao' => User::sum('pontos'),
                'total_pontos_emitidos' => User::sum('pontos_lifetime'),
                'total_badges_distribuidos' => DB::table('user_badges')->count(),
                'total_badges_unicos' => Badge::where('ativo', true)->count(),
                'nivel_medio' => round(User::where('pontos_lifetime', '>', 0)->avg('nivel') ?? 0, 2),
                'usuario_top' => User::orderBy('pontos_lifetime', 'desc')->first(['id', 'name', 'pontos_lifetime']),
            ];
        });
    }
}
