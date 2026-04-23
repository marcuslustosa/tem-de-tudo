<?php

namespace App\Http\Controllers;

use App\Services\LeaderboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    public function __construct(
        private readonly LeaderboardService $leaderboardService
    ) {}

    /**
     * Ranking global por pontos lifetime.
     */
    public function global(Request $request): JsonResponse
    {
        $limit = min((int) $request->input('limit', 100), 500);
        $offset = max(0, (int) $request->input('offset', 0));

        $ranking = $this->leaderboardService->globalRanking($limit, $offset);

        return response()->json([
            'success' => true,
            'leaderboard' => $ranking,
        ]);
    }

    /**
     * Ranking por empresa.
     */
    public function company(Request $request, int $empresaId): JsonResponse
    {
        $limit = min((int) $request->input('limit', 50), 200);

        $ranking = $this->leaderboardService->companyRanking($empresaId, $limit);

        return response()->json([
            'success' => true,
            'leaderboard' => $ranking,
        ]);
    }

    /**
     * Ranking mensal (quem mais ganhou pontos no mês).
     */
    public function monthly(Request $request): JsonResponse
    {
        $limit = min((int) $request->input('limit', 100), 500);

        $ranking = $this->leaderboardService->monthlyRanking($limit);

        return response()->json([
            'success' => true,
            'leaderboard' => $ranking,
        ]);
    }

    /**
     * Ranking de badges.
     */
    public function badges(Request $request): JsonResponse
    {
        $limit = min((int) $request->input('limit', 50), 200);

        $ranking = $this->leaderboardService->badgeRanking($limit);

        return response()->json([
            'success' => true,
            'leaderboard' => $ranking,
        ]);
    }

    /**
     * Posição do usuário autenticado no ranking.
     */
    public function myPosition(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $position = $this->leaderboardService->getUserPosition($userId);

        return response()->json([
            'success' => true,
            'position' => $position,
        ]);
    }

    /**
     * Posição de um usuário específico (público).
     */
    public function userPosition(int $userId): JsonResponse
    {
        try {
            $position = $this->leaderboardService->getUserPosition($userId);

            return response()->json([
                'success' => true,
                'position' => $position,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Usuário não encontrado',
            ], 404);
        }
    }

    /**
     * Estatísticas gerais de gamificação.
     */
    public function stats(): JsonResponse
    {
        $stats = $this->leaderboardService->getGamificationStats();

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }

    /**
     * Limpa cache do leaderboard (admin).
     */
    public function clearCache(Request $request): JsonResponse
    {
        $empresaId = $request->input('empresa_id');

        $this->leaderboardService->clearCache($empresaId);

        return response()->json([
            'success' => true,
            'message' => 'Cache do leaderboard limpo com sucesso',
        ]);
    }
}
