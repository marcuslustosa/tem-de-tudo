<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\RedemptionIntent;
use App\Models\User;
use App\Services\RedemptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RedemptionController extends Controller
{
    public function __construct(
        private readonly RedemptionService $redemptionService
    ) {}

    /**
     * Solicitar resgate (cria intenção e reserva pontos)
     * POST /api/redemption/request
     */
    public function request(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'nullable|integer|exists:users,id',
            'company_id' => 'nullable|integer|exists:empresas,id',
            'points' => 'required|integer|min:1',
            'type' => 'nullable|in:product,discount,cashback',
            'metadata' => 'nullable|array',
            'pdv_operator_id' => 'nullable|integer|exists:users,id',
            'pdv_terminal_id' => 'nullable|string|max:50',
            'expires_minutes' => 'nullable|integer|min:1|max:60',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados invalidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        $authUser = $request->user();
        $perfil = $this->normalizePerfil($authUser?->perfil);

        [$resolvedUserId, $resolvedCompanyId, $error] = $this->resolveRequestScope($request, $perfil);
        if ($error) {
            return $error;
        }

        try {
            $intent = $this->redemptionService->requestRedemption(
                userId: $resolvedUserId,
                companyId: $resolvedCompanyId,
                points: (int) $request->input('points'),
                options: [
                    'type' => $request->input('type', 'product'),
                    'metadata' => $request->input('metadata'),
                    'pdv_operator_id' => $request->input('pdv_operator_id', $authUser?->id),
                    'pdv_terminal_id' => $request->input('pdv_terminal_id'),
                    'expires_minutes' => (int) $request->input('expires_minutes', 15),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Resgate solicitado e pontos reservados',
                'data' => [
                    'intent_id' => $intent->intent_id,
                    'user_id' => $intent->user_id,
                    'company_id' => $intent->company_id,
                    'points_reserved' => $intent->points_requested,
                    'status' => $intent->status,
                    'expires_at' => $intent->expires_at?->toIso8601String(),
                    'expires_in_minutes' => $intent->expires_at?->diffInMinutes(now()),
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Confirmar resgate (debita pontos)
     * POST /api/redemption/confirm
     */
    public function confirm(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'intent_id' => 'required|string',
            'final_points' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados invalidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        [$intent, $error] = $this->loadAuthorizedIntent($request->user(), $request->input('intent_id'));
        if ($error) {
            return $error;
        }

        try {
            $updatedIntent = $this->redemptionService->confirmRedemption(
                intentId: $intent->intent_id,
                finalPoints: $request->input('final_points') !== null ? (int) $request->input('final_points') : null
            );

            return response()->json([
                'success' => true,
                'message' => 'Resgate confirmado com sucesso',
                'data' => [
                    'intent_id' => $updatedIntent->intent_id,
                    'points_confirmed' => $updatedIntent->points_confirmed,
                    'status' => $updatedIntent->status,
                    'confirmed_at' => $updatedIntent->confirmed_at?->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Cancelar resgate (libera reserva)
     * POST /api/redemption/cancel
     */
    public function cancel(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'intent_id' => 'required|string',
            'reason' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados invalidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        [$intent, $error] = $this->loadAuthorizedIntent($request->user(), $request->input('intent_id'));
        if ($error) {
            return $error;
        }

        try {
            $updatedIntent = $this->redemptionService->cancelRedemption(
                intentId: $intent->intent_id,
                reason: (string) $request->input('reason', 'Cancelado pelo operador')
            );

            return response()->json([
                'success' => true,
                'message' => 'Resgate cancelado e pontos liberados',
                'data' => [
                    'intent_id' => $updatedIntent->intent_id,
                    'status' => $updatedIntent->status,
                    'canceled_at' => $updatedIntent->canceled_at?->toIso8601String(),
                    'reason' => $updatedIntent->cancellation_reason,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Estornar resgate confirmado (requer admin)
     * POST /api/redemption/reverse
     */
    public function reverse(Request $request): JsonResponse
    {
        if (!$this->isAdmin($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado. Apenas administradores podem estornar resgates.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'intent_id' => 'required|string',
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados invalidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        [$intent, $error] = $this->loadAuthorizedIntent($request->user(), $request->input('intent_id'));
        if ($error) {
            return $error;
        }

        try {
            $updatedIntent = $this->redemptionService->reverseRedemption(
                intentId: $intent->intent_id,
                reason: (string) $request->input('reason'),
                reversedBy: (int) $request->user()->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Resgate estornado e pontos devolvidos',
                'data' => [
                    'intent_id' => $updatedIntent->intent_id,
                    'points_returned' => $updatedIntent->points_confirmed,
                    'status' => $updatedIntent->status,
                    'reversed_at' => $updatedIntent->reversed_at?->toIso8601String(),
                    'reason' => $updatedIntent->reversal_reason,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Buscar detalhes de um resgate
     * GET /api/redemption/{intentId}
     */
    public function show(string $intentId, Request $request): JsonResponse
    {
        $intent = $this->redemptionService->getIntent($intentId);
        if (!$intent) {
            return response()->json([
                'success' => false,
                'message' => 'Resgate nao encontrado',
            ], 404);
        }

        if (!$this->canAccessIntent($request->user(), $intent)) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado para este resgate.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'intent_id' => $intent->intent_id,
                'user' => [
                    'id' => $intent->user?->id,
                    'nome' => $intent->user?->name ?? $intent->user?->nome,
                    'email' => $intent->user?->email,
                ],
                'company' => [
                    'id' => $intent->company?->id,
                    'nome' => $intent->company?->nome ?? $intent->company?->nome_fantasia,
                ],
                'points_requested' => $intent->points_requested,
                'points_confirmed' => $intent->points_confirmed,
                'status' => $intent->status,
                'type' => $intent->redemption_type,
                'metadata' => $intent->metadata,
                'requested_at' => $intent->requested_at?->toIso8601String(),
                'reserved_at' => $intent->reserved_at?->toIso8601String(),
                'confirmed_at' => $intent->confirmed_at?->toIso8601String(),
                'canceled_at' => $intent->canceled_at?->toIso8601String(),
                'reversed_at' => $intent->reversed_at?->toIso8601String(),
                'expires_at' => $intent->expires_at?->toIso8601String(),
                'is_expired' => $intent->isExpired(),
            ],
        ]);
    }

    /**
     * Listar resgates do usuário
     * GET /api/redemption/user/{userId}
     */
    public function userHistory(int $userId, Request $request): JsonResponse
    {
        $authUser = $request->user();
        $perfil = $this->normalizePerfil($authUser?->perfil);

        if ($perfil === 'cliente' && $authUser && $authUser->id !== $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado ao historico de outro usuario.',
            ], 403);
        }

        $companyFilter = null;
        if ($perfil === 'empresa') {
            $companyFilter = $this->resolveCompanyId($authUser);
            if (!$companyFilter) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario empresa sem estabelecimento vinculado.',
                ], 403);
            }
        } elseif ($perfil !== 'admin' && $perfil !== 'cliente') {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado.',
            ], 403);
        }

        try {
            $redemptions = $this->redemptionService->getUserRedemptions($userId, 20, $companyFilter);

            return response()->json([
                'success' => true,
                'data' => $redemptions->map(function (RedemptionIntent $intent) {
                    return [
                        'intent_id' => $intent->intent_id,
                        'points' => $intent->points_confirmed ?? $intent->points_requested,
                        'status' => $intent->status,
                        'type' => $intent->redemption_type,
                        'company' => $intent->company?->nome ?? $intent->company?->nome_fantasia,
                        'date' => ($intent->confirmed_at ?? $intent->requested_at)?->toIso8601String(),
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Listar resgates pendentes da empresa (PDV)
     * GET /api/redemption/company/{companyId}/pending
     */
    public function companyPending(int $companyId, Request $request): JsonResponse
    {
        $authUser = $request->user();
        $perfil = $this->normalizePerfil($authUser?->perfil);

        if ($perfil === 'cliente') {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado.',
            ], 403);
        }

        if ($perfil === 'empresa') {
            $myCompanyId = $this->resolveCompanyId($authUser);
            if (!$myCompanyId || $myCompanyId !== $companyId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acesso negado para outra empresa.',
                ], 403);
            }
        } elseif ($perfil !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado.',
            ], 403);
        }

        try {
            $pending = $this->redemptionService->getCompanyPendingRedemptions($companyId);

            return response()->json([
                'success' => true,
                'count' => $pending->count(),
                'data' => $pending->map(function (RedemptionIntent $intent) {
                    return [
                        'intent_id' => $intent->intent_id,
                        'user' => [
                            'id' => $intent->user?->id,
                            'nome' => $intent->user?->name ?? $intent->user?->nome,
                        ],
                        'points' => $intent->points_requested,
                        'status' => $intent->status,
                        'type' => $intent->redemption_type,
                        'requested_at' => $intent->requested_at?->toIso8601String(),
                        'expires_at' => $intent->expires_at?->toIso8601String(),
                        'expires_in_minutes' => $intent->expires_at?->diffInMinutes(now()),
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * @return array{0: int, 1: int, 2: JsonResponse|null}
     */
    private function resolveRequestScope(Request $request, ?string $perfil): array
    {
        $authUser = $request->user();
        $payloadUserId = $request->input('user_id');
        $payloadCompanyId = $request->input('company_id');

        if ($perfil === 'admin') {
            if ($payloadUserId === null || $payloadCompanyId === null) {
                return [0, 0, response()->json([
                    'success' => false,
                    'message' => 'user_id e company_id sao obrigatorios para administradores.',
                ], 422)];
            }

            return [(int) $payloadUserId, (int) $payloadCompanyId, null];
        }

        if ($perfil === 'empresa') {
            $myCompanyId = $this->resolveCompanyId($authUser);
            if (!$myCompanyId) {
                return [0, 0, response()->json([
                    'success' => false,
                    'message' => 'Usuario empresa sem estabelecimento vinculado.',
                ], 403)];
            }

            if ($payloadCompanyId !== null && (int) $payloadCompanyId !== $myCompanyId) {
                return [0, 0, response()->json([
                    'success' => false,
                    'message' => 'Nao e permitido operar resgates de outra empresa.',
                ], 403)];
            }

            if ($payloadUserId === null) {
                return [0, 0, response()->json([
                    'success' => false,
                    'message' => 'user_id do cliente e obrigatorio para operacao PDV.',
                ], 422)];
            }

            return [(int) $payloadUserId, $myCompanyId, null];
        }

        if ($perfil === 'cliente' && $authUser) {
            if ($payloadUserId !== null && (int) $payloadUserId !== (int) $authUser->id) {
                return [0, 0, response()->json([
                    'success' => false,
                    'message' => 'Cliente nao pode solicitar resgate para outro usuario.',
                ], 403)];
            }

            if ($payloadCompanyId === null) {
                return [0, 0, response()->json([
                    'success' => false,
                    'message' => 'company_id e obrigatorio para cliente.',
                ], 422)];
            }

            return [(int) $authUser->id, (int) $payloadCompanyId, null];
        }

        return [0, 0, response()->json([
            'success' => false,
            'message' => 'Perfil sem permissao para operar resgates.',
        ], 403)];
    }

    /**
     * @return array{0: RedemptionIntent|null, 1: JsonResponse|null}
     */
    private function loadAuthorizedIntent(?User $authUser, string $intentId): array
    {
        $intent = $this->redemptionService->getIntent($intentId);
        if (!$intent) {
            return [null, response()->json([
                'success' => false,
                'message' => 'Resgate nao encontrado',
            ], 404)];
        }

        if (!$this->canAccessIntent($authUser, $intent)) {
            return [null, response()->json([
                'success' => false,
                'message' => 'Acesso negado para este resgate.',
            ], 403)];
        }

        return [$intent, null];
    }

    private function canAccessIntent(?User $authUser, RedemptionIntent $intent): bool
    {
        if (!$authUser) {
            return false;
        }

        if ($this->isAdmin($authUser)) {
            return true;
        }

        $perfil = $this->normalizePerfil($authUser->perfil ?? null);
        if ($perfil === 'cliente') {
            return (int) $intent->user_id === (int) $authUser->id;
        }

        if ($perfil === 'empresa') {
            $myCompanyId = $this->resolveCompanyId($authUser);
            return $myCompanyId !== null && (int) $intent->company_id === (int) $myCompanyId;
        }

        return false;
    }

    private function resolveCompanyId(?User $user): ?int
    {
        if (!$user) {
            return null;
        }

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

    private function isAdmin(?User $user): bool
    {
        return $this->normalizePerfil($user?->perfil) === 'admin';
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

