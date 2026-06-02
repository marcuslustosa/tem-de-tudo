<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Promocao;
use App\Models\User;
use App\Services\BonusAdesaoService;
use App\Services\CartaoFidelidadeService;
use App\Services\PromocaoInstantaneaService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class PromocaoController extends Controller
{
    public function __construct(
        private readonly PromocaoInstantaneaService $promocaoService,
        private readonly BonusAdesaoService $bonusService,
        private readonly CartaoFidelidadeService $cartaoService
    ) {
    }

    public function index(): JsonResponse
    {
        $empresa = $this->resolveOwnedEmpresa(Auth::user());
        if (!$empresa) {
            return $this->empresaNaoEncontrada();
        }

        $promocoes = $this->promocaoService->companyPromotions($empresa)
            ->get()
            ->map(fn (Promocao $promocao) => $this->promocaoService->serializePromotion($promocao))
            ->values();

        return response()->json([
            'success' => true,
            'data' => $promocoes,
            'meta' => [
                'weekly_limit' => $this->promocaoService->weeklySendStatus($empresa),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $empresa = $this->resolveOwnedEmpresa(Auth::user());
        if (!$empresa) {
            return $this->empresaNaoEncontrada();
        }

        $validated = $request->validate([
            'titulo' => 'required|string|max:80',
            'descricao' => 'required|string|max:240',
            'validade' => 'nullable|date',
            'notification_title' => 'nullable|string|max:80',
            'notification_body' => 'nullable|string|max:120',
            'ativo' => 'sometimes|boolean',
            'desconto' => 'nullable|numeric|min:0|max:100',
            'tipo_recompensa' => 'nullable|string|max:60',
            'tipo' => 'nullable|string|max:60',
            'imagem' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072',
            'imagem_url' => 'required_without:imagem|string|max:2048',
        ]);

        $payload = $this->buildPromotionPayload($request, $validated);

        try {
            $promocao = $this->promocaoService->savePromotion($empresa, $payload);
        } catch (DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            $this->logPromotionFailure('store', $e, [
                'empresa_id' => $empresa->id,
                'payload_keys' => array_keys($payload),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Nao foi possivel salvar a promocao agora.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Promocao instantanea salva com sucesso.',
            'data' => $this->promocaoService->serializePromotion($promocao),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $promocao = Promocao::query()->find($id);
        if (!$promocao) {
            return $this->promocaoNaoEncontrada();
        }

        if (!$this->canAccessPromotion(Auth::user(), $promocao)) {
            return $this->forbidden('Voce nao pode visualizar esta promocao.');
        }

        return response()->json([
            'success' => true,
            'data' => $this->promocaoService->serializePromotion($promocao),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $promocao = Promocao::query()->find($id);
        if (!$promocao) {
            return $this->promocaoNaoEncontrada();
        }

        if (!$this->canAccessPromotion(Auth::user(), $promocao)) {
            return $this->forbidden('Voce nao pode alterar esta promocao.');
        }

        $validated = $request->validate([
            'titulo' => 'sometimes|string|max:80',
            'descricao' => 'sometimes|string|max:240',
            'validade' => 'nullable|date',
            'notification_title' => 'nullable|string|max:80',
            'notification_body' => 'nullable|string|max:120',
            'ativo' => 'sometimes|boolean',
            'desconto' => 'nullable|numeric|min:0|max:100',
            'tipo_recompensa' => 'nullable|string|max:60',
            'tipo' => 'nullable|string|max:60',
            'imagem' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072',
            'imagem_url' => 'nullable|string|max:2048',
            'remover_imagem' => 'sometimes|boolean',
        ]);

        $oldImage = $promocao->imagem;
        $payload = $this->buildPromotionPayload($request, $validated, $promocao);

        try {
            $updated = $this->promocaoService->savePromotion($promocao->empresa, $payload, $promocao);
        } catch (DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            $this->logPromotionFailure('update', $e, [
                'empresa_id' => $promocao->empresa_id,
                'promocao_id' => $promocao->id,
                'payload_keys' => array_keys($payload),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Nao foi possivel atualizar a promocao agora.',
            ], 500);
        }

        if (($payload['imagem'] ?? null) && $oldImage && $oldImage !== $updated->imagem) {
            $this->deleteStoredImageIfNeeded($oldImage);
        }

        return response()->json([
            'success' => true,
            'message' => 'Promocao instantanea atualizada com sucesso.',
            'data' => $this->promocaoService->serializePromotion($updated),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $promocao = Promocao::query()->find($id);
        if (!$promocao) {
            return $this->promocaoNaoEncontrada();
        }

        if (!$this->canAccessPromotion(Auth::user(), $promocao)) {
            return $this->forbidden('Voce nao pode remover esta promocao.');
        }

        $this->promocaoService->deletePromotion($promocao);

        return response()->json([
            'success' => true,
            'message' => 'Promocao removida com sucesso.',
        ]);
    }

    public function toggle(Request $request, int $id): JsonResponse
    {
        $promocao = Promocao::query()->find($id);
        if (!$promocao) {
            return $this->promocaoNaoEncontrada();
        }

        if (!$this->canAccessPromotion(Auth::user(), $promocao)) {
            return $this->forbidden('Voce nao pode alterar esta promocao.');
        }

        $validated = $request->validate([
            'ativo' => 'sometimes|boolean',
        ]);

        $payload = [
            'ativo' => array_key_exists('ativo', $validated)
                ? (bool) $validated['ativo']
                : !(bool) $promocao->ativo,
        ];

        try {
            $updated = $this->promocaoService->savePromotion($promocao->empresa, $payload, $promocao);
        } catch (DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            $this->logPromotionFailure('toggle', $e, [
                'empresa_id' => $promocao->empresa_id,
                'promocao_id' => $promocao->id,
                'target_active' => $active,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Nao foi possivel alterar a promocao agora.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => $updated->ativo
                ? 'Promocao ativada com sucesso.'
                : 'Promocao pausada com sucesso.',
            'data' => $this->promocaoService->serializePromotion($updated),
        ]);
    }

    public function ativar(int $id): JsonResponse
    {
        return $this->toggleWithState($id, true);
    }

    public function pausar(int $id): JsonResponse
    {
        return $this->toggleWithState($id, false);
    }

    public function enviar(int $id): JsonResponse
    {
        $empresa = $this->resolveOwnedEmpresa(Auth::user());
        if (!$empresa) {
            return $this->empresaNaoEncontrada();
        }

        $promocao = Promocao::query()->find($id);
        if (!$promocao) {
            return $this->promocaoNaoEncontrada();
        }

        try {
            $result = $this->promocaoService->sendPromotion($empresa, $promocao, Auth::user());
        } catch (DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 409);
        }

        $deliveryStatus = $result['delivery']['status'] ?? null;

        if ($deliveryStatus === 'config_missing') {
            return response()->json([
                'success' => false,
                'error' => 'config_missing',
                'message' => $result['delivery']['message'] ?? 'Configuracao de push pendente no servidor.',
                'data' => $result['promocao'],
                'meta' => [
                    'weekly_limit' => $result['weekly_limit'],
                    'delivery' => $result['delivery'],
                ],
            ], 422);
        }

        if ($deliveryStatus === 'failed') {
            return response()->json([
                'success' => false,
                'error' => 'delivery_failed',
                'message' => $result['delivery']['message'] ?? 'Nao foi possivel entregar a promocao agora.',
                'data' => $result['promocao'],
                'meta' => [
                    'weekly_limit' => $result['weekly_limit'],
                    'delivery' => $result['delivery'],
                ],
            ], 422);
        }

        $message = $deliveryStatus === 'no_subscription'
            ? ($result['delivery']['message'] ?? 'A promocao esta pronta, mas nenhum cliente vinculado ativou notificacoes neste dispositivo ainda.')
            : 'Promocao enviada para clientes vinculados com processamento individual por subscription.';

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $result['promocao'],
            'meta' => [
                'weekly_limit' => $result['weekly_limit'],
                'delivery' => $result['delivery'],
            ],
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

        $promocao = Promocao::query()->find($id);
        if (!$promocao) {
            return $this->promocaoNaoEncontrada();
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
                'message' => 'A promocao so pode ser validada para clientes.',
            ], 422);
        }

        try {
            $this->promocaoService->validatePromotion($empresa, $promocao, $customer, Auth::user());
            $lookup = $this->bonusService->lookupCustomer($empresa, $customer);
            $lookup = $this->attachLoyaltySnapshot($empresa, $lookup);
            $lookup = $this->attachPromotionSnapshot($empresa, $customer, $lookup);
        } catch (DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => 'Promocao validada com sucesso.',
            'data' => $lookup,
        ]);
    }

    private function toggleWithState(int $id, bool $active): JsonResponse
    {
        $promocao = Promocao::query()->find($id);
        if (!$promocao) {
            return $this->promocaoNaoEncontrada();
        }

        if (!$this->canAccessPromotion(Auth::user(), $promocao)) {
            return $this->forbidden('Voce nao pode alterar esta promocao.');
        }

        try {
            $updated = $this->promocaoService->savePromotion($promocao->empresa, [
                'ativo' => $active,
            ], $promocao);
        } catch (DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $active ? 'Promocao ativada.' : 'Promocao pausada.',
            'data' => $this->promocaoService->serializePromotion($updated),
        ]);
    }

    private function buildPromotionPayload(Request $request, array $validated, ?Promocao $promocao = null): array
    {
        $payload = $validated;

        if ($request->boolean('remover_imagem') && !$request->hasFile('imagem') && !$request->filled('imagem_url')) {
            $payload['imagem'] = null;
        } elseif ($request->hasFile('imagem')) {
            $payload['imagem'] = $request->file('imagem')->store('promocoes', 'public');
        } elseif ($request->filled('imagem_url')) {
            $payload['imagem'] = trim((string) $request->input('imagem_url'));
        } elseif ($promocao) {
            $payload['imagem'] = $promocao->imagem;
        }

        return $payload;
    }

    private function canAccessPromotion(User $user, Promocao $promocao): bool
    {
        if ($this->normalizePerfil($user->perfil ?? $user->role ?? $user->tipo ?? null) !== 'empresa') {
            return false;
        }

        $empresa = $this->resolveOwnedEmpresa($user);

        return $empresa?->id === $promocao->empresa_id;
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

        if (Empresa::query()->whereKey($user->id)->exists()) {
            return Empresa::query()->find((int) $user->id);
        }

        return null;
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
            $lookup['cartao_fidelidade'] = [
                'status' => 'unavailable',
                'message' => 'Cliente nao encontrado para consulta da fidelidade.',
                'card' => null,
                'progress' => null,
                'history_summary' => [],
                'can_add_point' => false,
                'can_redeem' => false,
            ];

            return $lookup;
        }

        $lookup['cartao_fidelidade'] = $this->cartaoService->customerCardSnapshot($empresa, $customer);

        return $lookup;
    }

    private function attachPromotionSnapshot(Empresa $empresa, User $customer, array $lookup): array
    {
        $lookup['promocoes'] = $this->promocaoService->customerPromotions($empresa, $customer);

        return $lookup;
    }

    private function deleteStoredImageIfNeeded(?string $path): void
    {
        $path = trim((string) $path);
        if ($path === '' || str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/storage/')) {
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

    private function logPromotionFailure(string $action, \Throwable $e, array $context = []): void
    {
        $payload = array_merge($context, [
            'action' => $action,
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        try {
            Log::error('promotion.persistence_failed', $payload);
        } catch (\Throwable) {
            error_log('promotion.persistence_failed ' . json_encode($payload));
        }
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
            'message' => 'Empresa nao encontrada para este usuario.',
        ], 404);
    }

    private function promocaoNaoEncontrada(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Promocao nao encontrada.',
        ], 404);
    }
}
