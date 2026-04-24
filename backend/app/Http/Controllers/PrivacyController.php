<?php

namespace App\Http\Controllers;

use App\Models\DataPrivacyRequest;
use App\Services\DataPrivacyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrivacyController extends Controller
{
    public function __construct(
        private readonly DataPrivacyService $privacyService
    ) {}

    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        // Tentar obter histórico, mas ser resiliente se tabela não existir
        try {
            $history = DataPrivacyRequest::query()
                ->where('user_id', $user->id)
                ->orderByDesc('id')
                ->limit(20)
                ->get([
                    'id',
                    'request_type',
                    'status',
                    'reason',
                    'requested_at',
                    'processed_at',
                    'created_at',
                ]);
        } catch (\Exception $e) {
            // Tabela data_privacy_requests pode não existir ainda
            $history = [];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'consents' => [
                    'terms_accepted_at' => $user->terms_accepted_at ?? null,
                    'privacy_policy_accepted_at' => $user->privacy_policy_accepted_at ?? null,
                    'data_processing_consent_at' => $user->data_processing_consent_at ?? null,
                    'marketing_consent' => (bool) ($user->marketing_consent ?? false),
                    'consent_version' => $user->consent_version ?? 'v1',
                ],
                'requests' => $history,
            ],
        ]);
    }

    public function updateConsent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'marketing_consent' => 'sometimes|boolean',
            'terms_accepted' => 'sometimes|boolean',
            'privacy_policy_accepted' => 'sometimes|boolean',
            'data_processing_consent' => 'sometimes|boolean',
            'consent_version' => 'sometimes|string|max:20',
        ]);

        $user = $this->privacyService->updateConsent(
            $request->user(),
            $validated,
            [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Preferencias de privacidade atualizadas com sucesso.',
            'data' => [
                'terms_accepted_at' => $user->terms_accepted_at ?? null,
                'privacy_policy_accepted_at' => $user->privacy_policy_accepted_at ?? null,
                'data_processing_consent_at' => $user->data_processing_consent_at ?? null,
                'marketing_consent' => (bool) ($user->marketing_consent ?? false),
                'consent_version' => $user->consent_version ?? 'v1',
            ],
        ]);
    }

    public function requestExport(Request $request): JsonResponse
    {
        $privacyRequest = $this->privacyService->requestExport(
            $request->user(),
            [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]
        );

        if ($privacyRequest->status === DataPrivacyRequest::STATUS_FAILED) {
            return response()->json([
                'success' => false,
                'message' => 'Nao foi possivel gerar seu pacote de dados no momento.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Exportacao de dados concluida.',
            'data' => [
                'request_id' => $privacyRequest->id,
                'status' => $privacyRequest->status,
                'download_url' => "/api/privacy/export/{$privacyRequest->id}/download",
            ],
        ], 202);
    }

    public function downloadExport(Request $request, int $privacyRequestId): JsonResponse|StreamedResponse
    {
        $privacyRequest = DataPrivacyRequest::query()
            ->where('id', $privacyRequestId)
            ->where('user_id', $request->user()->id)
            ->where('request_type', DataPrivacyRequest::TYPE_EXPORT)
            ->first();

        if (!$privacyRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Solicitacao de exportacao nao encontrada.',
            ], 404);
        }

        if ($privacyRequest->status !== DataPrivacyRequest::STATUS_COMPLETED || !$privacyRequest->file_path) {
            $privacyRequest = $this->privacyService->processExport($privacyRequest);
        }

        if ($privacyRequest->status !== DataPrivacyRequest::STATUS_COMPLETED || !$privacyRequest->file_path) {
            return response()->json([
                'success' => false,
                'message' => 'Sua exportacao ainda nao esta disponivel.',
                'status' => $privacyRequest->status,
            ], 422);
        }

        $disk = $this->privacyService->exportDisk();
        if (!Storage::disk($disk)->exists($privacyRequest->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Arquivo de exportacao nao encontrado.',
            ], 404);
        }

        return Storage::disk($disk)->download(
            $privacyRequest->file_path,
            sprintf('lgpd-export-user-%d-%d.json', $request->user()->id, $privacyRequest->id)
        );
    }

    public function deleteAccount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'password' => 'required|string',
            'reason' => 'nullable|string|max:1000',
        ]);

        $user = $request->user();
        if (!Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Senha incorreta. Por favor, confirme sua senha para excluir a conta.',
            ], 403);
        }

        $this->privacyService->deleteAccount(
            $user,
            $validated['reason'] ?? null,
            [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Sua conta foi excluida com sucesso. Os dados pessoais foram anonimizados.',
        ]);
    }
}
