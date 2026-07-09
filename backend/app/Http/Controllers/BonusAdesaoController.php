<?php

namespace App\Http\Controllers;

use App\Models\BonusAdesao;
use App\Models\Empresa;
use App\Models\User;
use App\Services\BonusAdesaoService;
use App\Services\BonusAniversarioService;
use App\Services\CartaoFidelidadeService;
use App\Services\PromocaoInstantaneaService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class BonusAdesaoController extends Controller
{
    public function __construct(
        private readonly BonusAdesaoService $bonusService,
        private readonly BonusAniversarioService $bonusAniversarioService,
        private readonly CartaoFidelidadeService $cartaoService,
        private readonly PromocaoInstantaneaService $promocaoService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $perfil = $this->normalizePerfil($user->perfil ?? $user->role ?? $user->tipo ?? null);

        if ($perfil === 'empresa') {
            $empresa = $this->resolveOwnedEmpresa($user);
            if (!$empresa) {
                return $this->empresaNaoEncontrada();
            }

            $bonuses = $this->bonusService->companyBonuses($empresa)
                ->get()
                ->map(fn (BonusAdesao $bonus) => $this->bonusService->serializeBonus($bonus))
                ->values();

            return response()->json([
                'success' => true,
                'data' => $bonuses,
            ]);
        }

        if ($perfil !== 'admin') {
            return $this->forbidden('Perfil sem permissao para consultar bonus de adesao.');
        }

        $query = BonusAdesao::query()->with('empresa')->orderByDesc('created_at');
        if ($request->filled('empresa_id')) {
            $query->where('empresa_id', (int) $request->input('empresa_id'));
        }

        $bonuses = $query->get()->map(function (BonusAdesao $bonus) {
            return array_merge(
                $this->bonusService->serializeBonus($bonus),
                [
                    'empresa' => $bonus->empresa ? [
                        'id' => $bonus->empresa->id,
                        'nome' => $bonus->empresa->nome,
                        'status' => $bonus->empresa->operationalStatus(),
                        'ativo' => (bool) $bonus->empresa->ativo,
                    ] : null,
                ]
            );
        })->values();

        return response()->json([
            'success' => true,
            'data' => $bonuses,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();
        $perfil = $this->normalizePerfil($user->perfil ?? $user->role ?? $user->tipo ?? null);

        $empresa = $perfil === 'admin'
            ? $this->resolveAdminTargetEmpresa($request)
            : $this->resolveOwnedEmpresa($user);

        if (!$empresa) {
            return $this->empresaNaoEncontrada();
        }

        $validated = $request->validate([
            'titulo' => 'required|string|max:80',
            'descricao' => 'nullable|string|max:280',
            'data_expiracao' => 'nullable|date',
            'ativo' => 'sometimes|boolean',
            'ordem' => 'nullable|integer|min:1|max:999',
            'termos' => 'nullable|string|max:500',
            'imagem' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'imagem_url' => 'nullable|string|max:2048',
            'tipo_desconto' => 'nullable|in:porcentagem,valor_fixo',
            'valor_desconto' => 'nullable|numeric|min:0',
        ]);

        $payload = $this->buildBonusPayload($request, $validated);
        $bonus = $this->bonusService->saveBonus($empresa, $payload);

        return response()->json([
            'success' => true,
            'message' => 'Bonus de adesao salvo com sucesso.',
            'data' => $this->bonusService->serializeBonus($bonus),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $bonus = BonusAdesao::query()->find($id);
        if (!$bonus) {
            return $this->bonusNaoEncontrado();
        }

        if (!$this->canAccessBonus(Auth::user(), $bonus)) {
            return $this->forbidden('Voce nao pode visualizar este bonus.');
        }

        return response()->json([
            'success' => true,
            'data' => $this->bonusService->serializeBonus($bonus),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $bonus = BonusAdesao::query()->find($id);
        if (!$bonus) {
            return $this->bonusNaoEncontrado();
        }

        if (!$this->canAccessBonus(Auth::user(), $bonus)) {
            return $this->forbidden('Voce nao pode alterar este bonus.');
        }

        $validated = $request->validate([
            'titulo' => 'sometimes|string|max:80',
            'descricao' => 'nullable|string|max:280',
            'data_expiracao' => 'nullable|date',
            'ativo' => 'sometimes|boolean',
            'ordem' => 'nullable|integer|min:1|max:999',
            'termos' => 'nullable|string|max:500',
            'imagem' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'imagem_url' => 'nullable|string|max:2048',
            'remover_imagem' => 'sometimes|boolean',
            'tipo_desconto' => 'nullable|in:porcentagem,valor_fixo',
            'valor_desconto' => 'nullable|numeric|min:0',
        ]);

        $payload = $this->buildBonusPayload($request, $validated, $bonus);
        $updated = $this->bonusService->saveBonus($bonus->empresa, $payload, $bonus);

        return response()->json([
            'success' => true,
            'message' => 'Bonus de adesao atualizado com sucesso.',
            'data' => $this->bonusService->serializeBonus($updated),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $bonus = BonusAdesao::query()->find($id);
        if (!$bonus) {
            return $this->bonusNaoEncontrado();
        }

        if (!$this->canAccessBonus(Auth::user(), $bonus)) {
            return $this->forbidden('Voce nao pode remover este bonus.');
        }

        $this->deleteStoredImageIfNeeded($bonus->imagem);
        $bonus->delete();

        return response()->json([
            'success' => true,
            'message' => 'Bonus de adesao removido com sucesso.',
        ]);
    }

    public function toggle(Request $request, int $id): JsonResponse
    {
        $bonus = BonusAdesao::query()->find($id);
        if (!$bonus) {
            return $this->bonusNaoEncontrado();
        }

        if (!$this->canAccessBonus(Auth::user(), $bonus)) {
            return $this->forbidden('Voce nao pode alterar este bonus.');
        }

        $validated = $request->validate([
            'ativo' => 'sometimes|boolean',
        ]);

        $updated = $this->bonusService->saveBonus($bonus->empresa, [
            'ativo' => array_key_exists('ativo', $validated)
                ? (bool) $validated['ativo']
                : !(bool) $bonus->ativo,
        ], $bonus);

        return response()->json([
            'success' => true,
            'message' => $updated->ativo
                ? 'Bonus de adesao ativado com sucesso.'
                : 'Bonus de adesao desativado com sucesso.',
            'data' => $this->bonusService->serializeBonus($updated),
        ]);
    }

    public function bonusDisponivel(int $empresaId): JsonResponse
    {
        $user = Auth::user();
        if ($this->normalizePerfil($user->perfil ?? $user->role ?? $user->tipo ?? null) !== 'cliente') {
            return $this->forbidden('Apenas clientes podem consultar bonus de adesao.');
        }

        $empresa = Empresa::query()->publiclyVisible()->find($empresaId);
        if (!$empresa) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa indisponivel para consulta do bonus.',
            ], 404);
        }

        $status = $this->bonusService->evaluateCustomerBonus($empresa, $user);

        return response()->json([
            'success' => true,
            'message' => $status['message'],
            'data' => array_merge($status, [
                'empresa' => [
                    'id' => $empresa->id,
                    'nome' => $empresa->nome,
                    'status' => $empresa->operationalStatus(),
                    'ativo' => (bool) $empresa->ativo,
                ],
            ]),
        ]);
    }

    public function resgatar(int $empresaId): JsonResponse
    {
        $user = Auth::user();
        if ($this->normalizePerfil($user->perfil ?? $user->role ?? $user->tipo ?? null) !== 'cliente') {
            return $this->forbidden('Apenas clientes podem apresentar bonus de adesao.');
        }

        $empresa = Empresa::query()->publiclyVisible()->find($empresaId);
        if (!$empresa) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa indisponivel para apresentar bonus.',
            ], 404);
        }

        $status = $this->bonusService->evaluateCustomerBonus($empresa, $user);

        return response()->json([
            'success' => false,
            'message' => 'O bonus de adesao so pode ser validado pela empresa lendo o QR Code do cliente.',
            'data' => $status,
        ], 409);
    }

    public function consultarClienteQr(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'qrcode' => 'required|string|max:4096',
        ]);

        $empresa = $this->resolveOwnedEmpresa(Auth::user());
        if (!$empresa) {
            return $this->empresaNaoEncontrada();
        }

        try {
            $lookup = $this->bonusService->lookupCustomerByQr($empresa, (string) $validated['qrcode']);
            $lookup = $this->attachBirthdaySnapshot($empresa, $lookup);
            $lookup = $this->attachLoyaltySnapshot($empresa, $lookup);
            $lookup = $this->attachPromotionSnapshot($empresa, $lookup);
        } catch (DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Cliente consultado com sucesso.',
            'data' => $lookup,
        ]);
    }

    public function validar(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id' => 'required|integer|min:1',
        ]);

        $empresa = $this->resolveOwnedEmpresa(Auth::user());
        if (!$empresa) {
            return $this->empresaNaoEncontrada();
        }

        $bonus = BonusAdesao::query()->find($id);
        if (!$bonus) {
            return $this->bonusNaoEncontrado();
        }

        $customer = User::query()->find((int) $validated['cliente_id']);
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente nao encontrado.',
            ], 404);
        }

        if ($this->normalizePerfil($customer->perfil ?? $customer->role ?? $customer->tipo ?? null) !== 'cliente') {
            return response()->json([
                'success' => false,
                'message' => 'O bonus de adesao so pode ser validado para clientes.',
            ], 422);
        }

        try {
            $lookup = $this->bonusService->validateBonus($empresa, $bonus, $customer, Auth::user());
            $lookup = $this->attachBirthdaySnapshot($empresa, $lookup);
            $lookup = $this->attachLoyaltySnapshot($empresa, $lookup);
            $lookup = $this->attachPromotionSnapshot($empresa, $lookup);
        } catch (DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => 'Bonus de adesao validado com sucesso.',
            'data' => $lookup,
        ]);
    }

    private function canAccessBonus(User $user, BonusAdesao $bonus): bool
    {
        $perfil = $this->normalizePerfil($user->perfil ?? $user->role ?? $user->tipo ?? null);
        if ($perfil === 'admin') {
            return true;
        }

        if ($perfil !== 'empresa') {
            return false;
        }

        $empresa = $this->resolveOwnedEmpresa($user);

        return $empresa?->id === $bonus->empresa_id;
    }

    private function resolveOwnedEmpresa(User $user): ?Empresa
    {
        if (method_exists($user, 'empresa')) {
            $empresa = $user->empresa()->first();
            if ($empresa instanceof Empresa) {
                return $empresa;
            }
        }

        $query = Empresa::query();
        if (isset($user->empresa_id) && is_numeric($user->empresa_id)) {
            $empresa = (clone $query)->find((int) $user->empresa_id);
            if ($empresa instanceof Empresa) {
                return $empresa;
            }
        }

        if (Schema::hasColumn('empresas', 'owner_id')) {
            $empresa = (clone $query)->where('owner_id', $user->id)->first();
            if ($empresa instanceof Empresa) {
                return $empresa;
            }
        }

        if (Schema::hasColumn('empresas', 'user_id')) {
            $empresa = (clone $query)->where('user_id', $user->id)->first();
            if ($empresa instanceof Empresa) {
                return $empresa;
            }
        }

        $empresa = (clone $query)->whereKey($user->id)->first();
        if ($empresa instanceof Empresa) {
            return $empresa;
        }

        return null;
    }

    private function resolveAdminTargetEmpresa(Request $request): ?Empresa
    {
        $empresaId = (int) $request->input('empresa_id');
        if ($empresaId <= 0) {
            return null;
        }

        return Empresa::query()->find($empresaId);
    }

    private function buildBonusPayload(Request $request, array $validated, ?BonusAdesao $existing = null): array
    {
        $payload = $validated;

        if ($request->hasFile('imagem')) {
            $this->deleteStoredImageIfNeeded($existing?->imagem);
            $payload['imagem'] = $request->file('imagem')->store('bonus_adesao', 'public');
        } elseif ($request->has('imagem_url')) {
            $nextImage = trim((string) $request->input('imagem_url'));
            if ($nextImage === '') {
                $this->deleteStoredImageIfNeeded($existing?->imagem);
                $payload['imagem'] = null;
            } else {
                if ($existing?->imagem && $existing->imagem !== $nextImage) {
                    $this->deleteStoredImageIfNeeded($existing->imagem);
                }
                $payload['imagem'] = $nextImage;
            }
        } elseif ($request->boolean('remover_imagem')) {
            $this->deleteStoredImageIfNeeded($existing?->imagem);
            $payload['imagem'] = null;
        }

        unset($payload['imagem_url'], $payload['remover_imagem']);

        return $payload;
    }

    private function attachLoyaltySnapshot(Empresa $empresa, array $lookup): array
    {
        $customerId = (int) ($lookup['cliente']['id'] ?? 0);
        if ($customerId <= 0) {
            $lookup['cartao_fidelidade'] = [
                'status' => 'unavailable',
                'message' => 'Cliente nao identificado para consulta da fidelidade.',
                'card' => null,
                'progress' => null,
                'history_summary' => [],
                'can_add_point' => false,
                'can_redeem' => false,
            ];

            return $lookup;
        }

        $customer = User::query()->find($customerId);
        if (!$customer) {
            return $lookup;
        }

        $lookup['cartao_fidelidade'] = $this->cartaoService->customerCardSnapshot($empresa, $customer);

        return $lookup;
    }

    private function attachBirthdaySnapshot(Empresa $empresa, array $lookup): array
    {
        $customerId = (int) ($lookup['cliente']['id'] ?? 0);
        if ($customerId <= 0) {
            $lookup['bonus_aniversario'] = [
                'status' => 'unavailable',
                'message' => 'Cliente nao identificado para consulta do bonus aniversario.',
                'bonus' => null,
                'can_present_qr' => false,
                'can_validate' => false,
                'redeemed_at' => null,
                'valid_from' => null,
                'valid_until' => null,
                'eligibility_year' => (int) now()->year,
            ];

            return $lookup;
        }

        $customer = User::query()->find($customerId);
        if (!$customer) {
            $lookup['bonus_aniversario'] = [
                'status' => 'unavailable',
                'message' => 'Cliente nao encontrado para consulta do bonus aniversario.',
                'bonus' => null,
                'can_present_qr' => false,
                'can_validate' => false,
                'redeemed_at' => null,
                'valid_from' => null,
                'valid_until' => null,
                'eligibility_year' => (int) now()->year,
            ];

            return $lookup;
        }

        $lookup['bonus_aniversario'] = $this->bonusAniversarioService->customerBirthdaySnapshot($empresa, $customer);

        return $lookup;
    }

    private function attachPromotionSnapshot(Empresa $empresa, array $lookup): array
    {
        $customerId = (int) ($lookup['cliente']['id'] ?? 0);
        if ($customerId <= 0) {
            $lookup['promocoes'] = [
                'status' => 'unavailable',
                'message' => 'Cliente nao identificado para consulta das promocoes.',
                'items' => [],
                'available_count' => 0,
                'redeemed_count' => 0,
                'can_validate_any' => false,
            ];

            return $lookup;
        }

        $customer = User::query()->find($customerId);
        if (!$customer) {
            $lookup['promocoes'] = [
                'status' => 'unavailable',
                'message' => 'Cliente nao encontrado para consulta das promocoes.',
                'items' => [],
                'available_count' => 0,
                'redeemed_count' => 0,
                'can_validate_any' => false,
            ];

            return $lookup;
        }

        $lookup['promocoes'] = $this->promocaoService->customerPromotions($empresa, $customer);

        return $lookup;
    }

    private function deleteStoredImageIfNeeded(?string $path): void
    {
        $path = trim((string) $path);
        if ($path === '') {
            return;
        }

        if (
            str_starts_with($path, 'http://')
            || str_starts_with($path, 'https://')
            || str_starts_with($path, '/')
        ) {
            return;
        }

        Storage::disk('public')->delete($path);
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

    private function forbidden(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 403);
    }

    private function empresaNaoEncontrada(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Empresa nao encontrada.',
        ], 404);
    }

    private function bonusNaoEncontrado(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Bonus de adesao nao encontrado.',
        ], 404);
    }
}
