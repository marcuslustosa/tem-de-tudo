<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\User;
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
        [$empresaId, $error] = $this->resolveEmpresaScope($request);
        if ($error) {
            return $error;
        }

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
        [$empresaId, $error] = $this->resolveEmpresaScope($request);
        if ($error) {
            return $error;
        }

        $cltv = $this->analyticsService->calculateCLTV($empresaId);

        return response()->json([
            'success' => true,
            'cltv' => $cltv,
        ]);
    }

    /**
     * Calcula taxa de retencao.
     */
    public function retention(Request $request): JsonResponse
    {
        [$empresaId, $error] = $this->resolveEmpresaScope($request);
        if ($error) {
            return $error;
        }

        $periodoDias = (int) $request->input('periodo_dias', 30);
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
        [$empresaId, $error] = $this->resolveEmpresaScope($request);
        if ($error) {
            return $error;
        }

        $diasInatividade = (int) $request->input('dias_inatividade', 90);
        $churn = $this->analyticsService->calculateChurn($empresaId, $diasInatividade);

        return response()->json([
            'success' => true,
            'churn' => $churn,
        ]);
    }

    /**
     * Analise de cohort.
     */
    public function cohort(Request $request): JsonResponse
    {
        [$empresaId, $error] = $this->resolveEmpresaScope($request);
        if ($error) {
            return $error;
        }

        $meses = (int) $request->input('meses', 6);
        $cohort = $this->analyticsService->cohortAnalysis($empresaId, $meses);

        return response()->json([
            'success' => true,
            'cohorts' => $cohort,
        ]);
    }

    /**
     * Metricas de transacoes.
     */
    public function transactions(Request $request): JsonResponse
    {
        [$empresaId, $error] = $this->resolveEmpresaScope($request);
        if ($error) {
            return $error;
        }

        $dias = (int) $request->input('dias', 30);
        $metrics = $this->analyticsService->transactionMetrics($empresaId, $dias);

        return response()->json([
            'success' => true,
            'metrics' => $metrics,
        ]);
    }

    /**
     * Distribuicao de usuarios por nivel.
     */
    public function levelDistribution(Request $request): JsonResponse
    {
        [$empresaId, $error] = $this->resolveEmpresaScope($request);
        if ($error) {
            return $error;
        }

        $distribution = $this->analyticsService->userDistributionByLevel($empresaId);

        return response()->json([
            'success' => true,
            'distribution' => $distribution,
        ]);
    }

    /**
     * Top usuarios.
     */
    public function topUsers(Request $request): JsonResponse
    {
        [$empresaId, $error] = $this->resolveEmpresaScope($request);
        if ($error) {
            return $error;
        }

        $limit = max(1, min(100, (int) $request->input('limit', 10)));
        $topUsers = $this->analyticsService->topUsers($empresaId, $limit);

        return response()->json([
            'success' => true,
            'top_users' => $topUsers,
        ]);
    }

    /**
     * @return array{0: int|null, 1: JsonResponse|null}
     */
    private function resolveEmpresaScope(Request $request): array
    {
        $user = $request->user();
        if (!$user) {
            return [null, response()->json([
                'success' => false,
                'message' => 'Nao autenticado.',
            ], 401)];
        }

        $perfil = $this->normalizePerfil($user->perfil ?? null);

        if ($perfil === 'cliente') {
            return [null, response()->json([
                'success' => false,
                'message' => 'Clientes nao possuem acesso aos analytics.',
            ], 403)];
        }

        if ($perfil === 'empresa') {
            $empresaId = $this->resolveCompanyId($user);
            if (!$empresaId) {
                return [null, response()->json([
                    'success' => false,
                    'message' => 'Usuario empresa sem estabelecimento vinculado.',
                ], 403)];
            }

            return [$empresaId, null];
        }

        // Admin: pode consultar todas empresas ou uma especifica.
        $empresaId = $request->input('empresa_id');
        return [$empresaId !== null ? (int) $empresaId : null, null];
    }

    private function resolveCompanyId(User $user): ?int
    {
        if (isset($user->empresa_id) && is_numeric($user->empresa_id) && (int) $user->empresa_id > 0) {
            return (int) $user->empresa_id;
        }

        if (method_exists($user, 'empresa')) {
            $empresa = $user->empresa()->first();
            if ($empresa?->id) {
                return (int) $empresa->id;
            }
        }

        if (Empresa::query()->whereKey($user->id)->exists()) {
            return (int) $user->id;
        }

        return null;
    }

    private function normalizePerfil(?string $perfil): ?string
    {
        if (!$perfil) {
            return null;
        }

        $value = strtolower(trim($perfil));

        if (in_array($value, ['admin', 'administrador', 'master', 'admin_master', 'administrador_master'], true)) {
            return 'admin';
        }
        if (in_array($value, ['empresa', 'estabelecimento', 'parceiro', 'lojista'], true)) {
            return 'empresa';
        }
        if (in_array($value, ['cliente', 'customer'], true)) {
            return 'cliente';
        }

        return $value;
    }
}

