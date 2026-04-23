<?php

namespace App\Http\Controllers;

use App\Services\RedemptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RedemptionController extends Controller
{
    protected $redemptionService;

    public function __construct(RedemptionService $redemptionService)
    {
        $this->redemptionService = $redemptionService;
    }

    /**
     * Solicitar resgate (cria intenção e reserva pontos)
     * POST /api/redemption/request
     */
    public function request(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'company_id' => 'required|integer|exists:empresas,id',
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
                'message' => 'Dados inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $intent = $this->redemptionService->requestRedemption(
                userId: $request->user_id,
                companyId: $request->company_id,
                points: $request->points,
                options: [
                    'type' => $request->type ?? 'product',
                    'metadata' => $request->metadata,
                    'pdv_operator_id' => $request->pdv_operator_id ?? auth()->id(),
                    'pdv_terminal_id' => $request->pdv_terminal_id,
                    'expires_minutes' => $request->expires_minutes ?? 15,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Resgate solicitado e pontos reservados',
                'data' => [
                    'intent_id' => $intent->intent_id,
                    'points_reserved' => $intent->points_requested,
                    'status' => $intent->status,
                    'expires_at' => $intent->expires_at->toIso8601String(),
                    'expires_in_minutes' => $intent->expires_at->diffInMinutes(now()),
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
    public function confirm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'intent_id' => 'required|string',
            'final_points' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $intent = $this->redemptionService->confirmRedemption(
                intentId: $request->intent_id,
                finalPoints: $request->final_points
            );

            return response()->json([
                'success' => true,
                'message' => 'Resgate confirmado com sucesso',
                'data' => [
                    'intent_id' => $intent->intent_id,
                    'points_confirmed' => $intent->points_confirmed,
                    'status' => $intent->status,
                    'confirmed_at' => $intent->confirmed_at->toIso8601String(),
                ],
            ], 200);

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
    public function cancel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'intent_id' => 'required|string',
            'reason' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $intent = $this->redemptionService->cancelRedemption(
                intentId: $request->intent_id,
                reason: $request->reason ?? 'Cancelado pelo operador'
            );

            return response()->json([
                'success' => true,
                'message' => 'Resgate cancelado e pontos liberados',
                'data' => [
                    'intent_id' => $intent->intent_id,
                    'status' => $intent->status,
                    'canceled_at' => $intent->canceled_at->toIso8601String(),
                    'reason' => $intent->cancellation_reason,
                ],
            ], 200);

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
    public function reverse(Request $request)
    {
        // Apenas admin/master pode estornar
        if (!in_array($request->user()->perfil, ['admin', 'master'])) {
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
                'message' => 'Dados inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $intent = $this->redemptionService->reverseRedemption(
                intentId: $request->intent_id,
                reason: $request->reason,
                reversedBy: $request->user()->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Resgate estornado e pontos devolvidos',
                'data' => [
                    'intent_id' => $intent->intent_id,
                    'points_returned' => $intent->points_confirmed,
                    'status' => $intent->status,
                    'reversed_at' => $intent->reversed_at->toIso8601String(),
                    'reason' => $intent->reversal_reason,
                ],
            ], 200);

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
    public function show(string $intentId)
    {
        try {
            $intent = $this->redemptionService->getIntent($intentId);

            if (!$intent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resgate não encontrado',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'intent_id' => $intent->intent_id,
                    'user' => [
                        'id' => $intent->user->id,
                        'nome' => $intent->user->nome,
                        'email' => $intent->user->email,
                    ],
                    'company' => [
                        'id' => $intent->company->id,
                        'nome_fantasia' => $intent->company->nome_fantasia,
                    ],
                    'points_requested' => $intent->points_requested,
                    'points_confirmed' => $intent->points_confirmed,
                    'status' => $intent->status,
                    'type' => $intent->redemption_type,
                    'metadata' => $intent->metadata,
                    'requested_at' => $intent->requested_at->toIso8601String(),
                    'reserved_at' => $intent->reserved_at?->toIso8601String(),
                    'confirmed_at' => $intent->confirmed_at?->toIso8601String(),
                    'canceled_at' => $intent->canceled_at?->toIso8601String(),
                    'reversed_at' => $intent->reversed_at?->toIso8601String(),
                    'expires_at' => $intent->expires_at?->toIso8601String(),
                    'is_expired' => $intent->isExpired(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Listar resgates do usuário
     * GET /api/redemption/user/{userId}
     */
    public function userHistory(int $userId)
    {
        try {
            $redemptions = $this->redemptionService->getUserRedemptions($userId);

            return response()->json([
                'success' => true,
                'data' => $redemptions->map(function ($intent) {
                    return [
                        'intent_id' => $intent->intent_id,
                        'points' => $intent->points_confirmed ?? $intent->points_requested,
                        'status' => $intent->status,
                        'type' => $intent->redemption_type,
                        'company' => $intent->company->nome_fantasia,
                        'date' => $intent->confirmed_at ?? $intent->requested_at,
                    ];
                }),
            ], 200);

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
    public function companyPending(int $companyId)
    {
        try {
            $pending = $this->redemptionService->getCompanyPendingRedemptions($companyId);

            return response()->json([
                'success' => true,
                'count' => $pending->count(),
                'data' => $pending->map(function ($intent) {
                    return [
                        'intent_id' => $intent->intent_id,
                        'user' => [
                            'id' => $intent->user->id,
                            'nome' => $intent->user->nome,
                        ],
                        'points' => $intent->points_requested,
                        'status' => $intent->status,
                        'type' => $intent->redemption_type,
                        'requested_at' => $intent->requested_at->toIso8601String(),
                        'expires_at' => $intent->expires_at->toIso8601String(),
                        'expires_in_minutes' => $intent->expires_at->diffInMinutes(now()),
                    ];
                }),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
