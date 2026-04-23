<?php

namespace App\Http\Controllers;

use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(
        private readonly AnalyticsService $analyticsService
    ) {}

    /**
     * Dashboard completo de analytics.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $empresaId = $request->input('empresa_id');
        $dashboard = $this->analyticsService->dashboard($empresaId);

        return response()->json([
            'success' => true,
            'dashboard' => $dashboard,
        ]);
    }

    /**
     * Calcula CLTV (Customer Lifetime Value).
     */
    public function cltv(Request $request): JsonResponse
    {
        $empresaId = $request->input('empresa_id');
        $cltv = $this->analyticsService->calculateCLTV($empresaId);

        return response()->json([
            'success' => true,
            'cltv' => $cltv,
        ]);
    }

    /**
     * Calcula taxa de retenção.
     */
    public function retention(Request $request): JsonResponse
    {
        $empresaId = $request->input('empresa_id');
        $periodoDias = $request->input('periodo_dias', 30);

        $retention = $this->analyticsService->calculateRetention($empresaId, $periodoDias);

        return response()->json([
            'success' => true,
            'retention' => $retention,
        ]);
    }

    /**
     * Calcula taxa de churn.
     */
    public function churn(Request $request): JsonResponse
    {
        $empresaId = $request->input('empresa_id');
        $diasInatividade = $request->input('dias_inatividade', 90);

        $churn = $this->analyticsService->calculateChurn($empresaId, $diasInatividade);

        return response()->json([
            'success' => true,
            'churn' => $churn,
        ]);
    }

    /**
     * Análise de cohort.
     */
    public function cohort(Request $request): JsonResponse
    {
        $empresaId = $request->input('empresa_id');
        $meses = $request->input('meses', 6);

        $cohort = $this->analyticsService->cohortAnalysis($empresaId, $meses);

        return response()->json([
            'success' => true,
            'cohorts' => $cohort,
        ]);
    }

    /**
     * Métricas de transações.
     */
    public function transactions(Request $request): JsonResponse
    {
        $empresaId = $request->input('empresa_id');
        $dias = $request->input('dias', 30);

        $metrics = $this->analyticsService->transactionMetrics($empresaId, $dias);

        return response()->json([
            'success' => true,
            'metrics' => $metrics,
        ]);
    }

    /**
     * Distribuição de usuários por nível.
     */
    public function levelDistribution(Request $request): JsonResponse
    {
        $empresaId = $request->input('empresa_id');
        $distribution = $this->analyticsService->userDistributionByLevel($empresaId);

        return response()->json([
            'success' => true,
            'distribution' => $distribution,
        ]);
    }

    /**
     * Top usuários.
     */
    public function topUsers(Request $request): JsonResponse
    {
        $empresaId = $request->input('empresa_id');
        $limit = $request->input('limit', 10);

        $topUsers = $this->analyticsService->topUsers($empresaId, $limit);

        return response()->json([
            'success' => true,
            'top_users' => $topUsers,
        ]);
    }
}
