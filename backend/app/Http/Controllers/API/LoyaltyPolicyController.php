<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\User;
use App\Services\LoyaltyProgramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoyaltyPolicyController extends Controller
{
    public function __construct(
        private readonly LoyaltyProgramService $loyalty
    ) {
    }

    public function companyConfig(Request $request): JsonResponse
    {
        $user = $request->user();
        $company = $this->resolveCompanyFromUser($user);

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa nao encontrada para o usuario autenticado.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->buildPayload($company),
        ]);
    }

    public function companyUpdateConfig(Request $request): JsonResponse
    {
        $user = $request->user();
        $company = $this->resolveCompanyFromUser($user);

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa nao encontrada para o usuario autenticado.',
            ], 404);
        }

        $payload = $this->validatedConfigPayload($request);
        if ($payload === []) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhum campo valido enviado para atualizacao da politica.',
            ], 422);
        }

        $this->loyalty->upsertCompanyConfig($company, $payload, $user?->id);

        return response()->json([
            'success' => true,
            'message' => 'Politica de fidelidade atualizada com sucesso.',
            'data' => $this->buildPayload($company->fresh()),
        ]);
    }

    public function companyOnboardingStatus(Request $request): JsonResponse
    {
        $user = $request->user();
        $company = $this->resolveCompanyFromUser($user);

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa nao encontrada para o usuario autenticado.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->loyalty->onboardingStatus($company),
        ]);
    }

    public function adminCompanyConfig(Request $request, int $companyId): JsonResponse
    {
        $company = Empresa::query()->find($companyId);
        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa nao encontrada.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->buildPayload($company),
        ]);
    }

    public function adminUpdateCompanyConfig(Request $request, int $companyId): JsonResponse
    {
        $company = Empresa::query()->find($companyId);
        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa nao encontrada.',
            ], 404);
        }

        $payload = $this->validatedConfigPayload($request);
        if ($payload === []) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhum campo valido enviado para atualizacao da politica.',
            ], 422);
        }

        $this->loyalty->upsertCompanyConfig($company, $payload, $request->user()?->id);

        return response()->json([
            'success' => true,
            'message' => 'Politica de fidelidade da empresa atualizada com sucesso.',
            'data' => $this->buildPayload($company->fresh()),
        ]);
    }

    public function adminCompanyOnboardingStatus(Request $request, int $companyId): JsonResponse
    {
        $company = Empresa::query()->find($companyId);
        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa nao encontrada.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->loyalty->onboardingStatus($company),
        ]);
    }

    private function buildPayload(Empresa $company): array
    {
        $company = $company->loadMissing('loyaltyConfig');

        return [
            'company' => [
                'id' => $company->id,
                'nome' => $company->nome,
                'owner_id' => $company->owner_id,
                'ativo' => (bool) $company->ativo,
            ],
            'policy' => $this->loyalty->effectivePolicy($company),
            'onboarding' => $this->loyalty->onboardingStatus($company),
        ];
    }

    private function validatedConfigPayload(Request $request): array
    {
        $validated = $request->validate([
            'points_per_real' => 'sometimes|nullable|numeric|min:0.01|max:200',
            'scan_base_points' => 'sometimes|nullable|integer|min:1|max:10000',
            'redeem_points_per_currency' => 'sometimes|integer|min:1|max:1000',
            'min_redeem_points' => 'sometimes|integer|min:1|max:100000',
            'welcome_bonus_points' => 'sometimes|integer|min:0|max:100000',
            'is_active' => 'sometimes',
            'metadata' => 'sometimes|array',
        ]);

        if (array_key_exists('is_active', $validated)) {
            $validated['is_active'] = $this->normalizeBool($validated['is_active']);
        }

        return $validated;
    }

    private function normalizeBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $raw = strtolower(trim((string) $value));

        return in_array($raw, ['1', 'true', 'sim', 'yes', 'on'], true);
    }

    private function resolveCompanyFromUser(?User $user): ?Empresa
    {
        if (!$user) {
            return null;
        }

        $company = Empresa::query()->where('owner_id', $user->id)->first();
        if ($company) {
            return $company;
        }

        if (isset($user->empresa_id) && $user->empresa_id) {
            return Empresa::query()->find($user->empresa_id);
        }

        return null;
    }
}
