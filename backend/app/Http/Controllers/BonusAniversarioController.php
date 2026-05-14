<?php

namespace App\Http\Controllers;

use App\Models\BonusAniversario;
use App\Models\Empresa;
use App\Models\User;
use App\Services\BonusAniversarioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class BonusAniversarioController extends Controller
{
    public function __construct(
        private readonly BonusAniversarioService $bonusService
    ) {
    }

    public function index(): JsonResponse
    {
        $empresa = $this->resolveOwnedEmpresa(Auth::user());
        if (!$empresa) {
            return $this->empresaNaoEncontrada();
        }

        $items = $this->bonusService->companyBonuses($empresa)
            ->get()
            ->map(fn (BonusAniversario $bonus) => $this->bonusService->serializeBonus($bonus))
            ->values();

        return response()->json([
            'success' => true,
            'data' => $items,
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
            'descricao' => 'required|string|max:280',
            'dias_validade' => 'nullable|integer|min:1|max:90',
            'notification_title' => 'nullable|string|max:80',
            'notification_body' => 'nullable|string|max:120',
            'ativo' => 'sometimes|boolean',
            'imagem' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072',
            'imagem_url' => 'nullable|string|max:2048',
        ]);

        $payload = $this->buildPayload($request, $validated);
        $bonus = $this->bonusService->saveBonus($empresa, $payload);

        return response()->json([
            'success' => true,
            'message' => 'Bonus aniversario salvo com sucesso.',
            'data' => $this->bonusService->serializeBonus($bonus),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $bonus = BonusAniversario::query()->find($id);
        if (!$bonus) {
            return $this->bonusNaoEncontrado();
        }

        if (!$this->canAccessBonus(Auth::user(), $bonus)) {
            return $this->forbidden('Voce nao pode visualizar este bonus aniversario.');
        }

        return response()->json([
            'success' => true,
            'data' => $this->bonusService->serializeBonus($bonus),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $bonus = BonusAniversario::query()->find($id);
        if (!$bonus) {
            return $this->bonusNaoEncontrado();
        }

        if (!$this->canAccessBonus(Auth::user(), $bonus)) {
            return $this->forbidden('Voce nao pode alterar este bonus aniversario.');
        }

        $validated = $request->validate([
            'titulo' => 'sometimes|filled|string|max:80',
            'descricao' => 'sometimes|filled|string|max:280',
            'dias_validade' => 'nullable|integer|min:1|max:90',
            'notification_title' => 'nullable|string|max:80',
            'notification_body' => 'nullable|string|max:120',
            'ativo' => 'sometimes|boolean',
            'imagem' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072',
            'imagem_url' => 'nullable|string|max:2048',
            'remover_imagem' => 'sometimes|boolean',
        ]);

        $payload = $this->buildPayload($request, $validated, $bonus);
        $updated = $this->bonusService->saveBonus($bonus->empresa, $payload, $bonus);

        return response()->json([
            'success' => true,
            'message' => 'Bonus aniversario atualizado com sucesso.',
            'data' => $this->bonusService->serializeBonus($updated),
        ]);
    }

    public function toggle(Request $request, int $id): JsonResponse
    {
        $bonus = BonusAniversario::query()->find($id);
        if (!$bonus) {
            return $this->bonusNaoEncontrado();
        }

        if (!$this->canAccessBonus(Auth::user(), $bonus)) {
            return $this->forbidden('Voce nao pode alterar este bonus aniversario.');
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
                ? 'Bonus aniversario ativado com sucesso.'
                : 'Bonus aniversario desativado com sucesso.',
            'data' => $this->bonusService->serializeBonus($updated),
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

        $bonus = BonusAniversario::query()->find($id);
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
                'message' => 'O bonus aniversario so pode ser validado para clientes.',
            ], 422);
        }

        try {
            $snapshot = $this->bonusService->validateBonus($empresa, $bonus, $customer, Auth::user());
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => 'Bonus aniversario validado com sucesso.',
            'data' => [
                'bonus_aniversario' => $snapshot,
            ],
        ]);
    }

    public function enviarElegiveis(int $id): JsonResponse
    {
        $empresa = $this->resolveOwnedEmpresa(Auth::user());
        if (!$empresa) {
            return $this->empresaNaoEncontrada();
        }

        $bonus = BonusAniversario::query()->find($id);
        if (!$bonus) {
            return $this->bonusNaoEncontrado();
        }

        try {
            $result = $this->bonusService->sendToEligibleCustomers($empresa, $bonus, Auth::user());
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => 'Envio de aniversario processado para clientes vinculados elegiveis.',
            'data' => $result['bonus'],
            'meta' => [
                'delivery' => $result['delivery'],
            ],
        ]);
    }

    public function disponiveis(Request $request): JsonResponse
    {
        $user = Auth::user();
        if ($this->normalizePerfil($user->perfil ?? $user->role ?? $user->tipo ?? null) !== 'cliente') {
            return $this->forbidden('Apenas clientes podem consultar bonus aniversario.');
        }

        $validated = $request->validate([
            'empresa_id' => 'nullable|integer|min:1',
        ]);

        $items = $this->bonusService->listBonusesForCustomer(
            $user,
            isset($validated['empresa_id']) ? (int) $validated['empresa_id'] : null
        );

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $items,
                'total' => count($items),
                'available_count' => collect($items)->where('status', 'available')->count(),
            ],
        ]);
    }

    private function buildPayload(Request $request, array $validated, ?BonusAniversario $existing = null): array
    {
        $payload = $validated;

        if ($request->hasFile('imagem')) {
            $this->deleteStoredImageIfNeeded($existing?->imagem);
            $payload['imagem'] = $request->file('imagem')->store('bonus_aniversario', 'public');
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

    private function canAccessBonus(User $user, BonusAniversario $bonus): bool
    {
        if ($this->normalizePerfil($user->perfil ?? $user->role ?? $user->tipo ?? null) !== 'empresa') {
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

        return (clone $query)->whereKey($user->id)->first();
    }

    private function deleteStoredImageIfNeeded(?string $path): void
    {
        $path = trim((string) $path);
        if (
            $path === ''
            || str_starts_with($path, 'http://')
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
            'message' => 'Bonus aniversario nao encontrado.',
        ], 404);
    }
}
