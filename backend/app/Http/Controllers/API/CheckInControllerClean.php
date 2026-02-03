<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckIn\CheckInRequest;
use App\Http\Resources\CheckInResource;
use App\Services\CheckInService;
use App\DTOs\CheckIn\CheckInDTO;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class CheckInControllerClean extends Controller
{
    public function __construct(
        private CheckInService $checkInService
    ) {}

    /**
     * Realizar check-in
     */
    public function checkIn(CheckInRequest $request): JsonResponse
    {
        try {
            $dto = CheckInDTO::fromArray(
                $request->validated(),
                $request->user()->id
            );

            $result = $this->checkInService->checkIn($dto);

            return response()->json([
                'success' => true,
                'message' => 'Check-in realizado com sucesso!',
                'data' => [
                    'checkin' => new CheckInResource($result['checkin']),
                    'pontos_ganhos' => $result['pontos_ganhos'],
                    'pontos_totais' => $result['pontos_totais']
                ]
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Histórico de check-ins
     */
    public function history(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            $result = $this->checkInService->getHistory($request->user()->id, $perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'checkins' => CheckInResource::collection($result['checkins']),
                    'total_pontos' => $result['total_pontos'],
                    'total_checkins' => $result['total_checkins'],
                    'pagination' => [
                        'current_page' => $result['checkins']->currentPage(),
                        'last_page' => $result['checkins']->lastPage(),
                        'per_page' => $result['checkins']->perPage(),
                        'total' => $result['checkins']->total()
                    ]
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar histórico'
            ], 500);
        }
    }

    /**
     * Detalhes de um check-in
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $checkInRepository = app(\App\Repositories\CheckInRepository::class);
            $checkIn = $checkInRepository->findById($id);

            if (!$checkIn) {
                return response()->json([
                    'success' => false,
                    'message' => 'Check-in não encontrado'
                ], 404);
            }

            // Verifica permissão
            if ($checkIn->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não autorizado'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'checkin' => new CheckInResource($checkIn)
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar check-in'
            ], 500);
        }
    }
}
