<?php

namespace App\Http\Controllers;

use App\Models\CartaoFidelidade;
use App\Models\Empresa;
use App\Models\User;
use App\Services\CartaoFidelidadeService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class CartaoFidelidadeController extends Controller
{
    public function __construct(
        private readonly CartaoFidelidadeService $cartaoService
    ) {
    }

    public function index(): JsonResponse
    {
        $empresa = $this->resolveOwnedEmpresa(Auth::user());
        if (!$empresa) {
            return $this->empresaNaoEncontrada();
        }

        $cards = $this->cartaoService->companyCards($empresa)
            ->get()
            ->map(fn (CartaoFidelidade $card) => $this->cartaoService->serializeCard($card))
            ->values();

        return response()->json([
            'success' => true,
            'data' => $cards,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $empresa = $this->resolveOwnedEmpresa(Auth::user());
        if (!$empresa) {
            return $this->empresaNaoEncontrada();
        }

        $payload = $this->validatePayload($request);
        $card = $this->cartaoService->saveCard($empresa, $payload);

        return response()->json([
            'success' => true,
            'message' => 'Cartao fidelidade salvo com sucesso.',
            'data' => $this->cartaoService->serializeCard($card),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $card = CartaoFidelidade::query()->find($id);
        if (!$card) {
            return $this->cartaoNaoEncontrado();
        }

        if (!$this->canAccessCard(Auth::user(), $card)) {
            return $this->forbidden('Voce nao pode visualizar este cartao fidelidade.');
        }

        return response()->json([
            'success' => true,
            'data' => $this->cartaoService->serializeCard($card),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $card = CartaoFidelidade::query()->find($id);
        if (!$card) {
            return $this->cartaoNaoEncontrado();
        }

        if (!$this->canAccessCard(Auth::user(), $card)) {
            return $this->forbidden('Voce nao pode alterar este cartao fidelidade.');
        }

        $payload = $this->validatePayload($request, true);
        $updated = $this->cartaoService->saveCard($card->empresa, $payload, $card);

        return response()->json([
            'success' => true,
            'message' => 'Cartao fidelidade atualizado com sucesso.',
            'data' => $this->cartaoService->serializeCard($updated),
        ]);
    }

    public function toggle(Request $request, int $id): JsonResponse
    {
        $card = CartaoFidelidade::query()->find($id);
        if (!$card) {
            return $this->cartaoNaoEncontrado();
        }

        if (!$this->canAccessCard(Auth::user(), $card)) {
            return $this->forbidden('Voce nao pode alterar este cartao fidelidade.');
        }

        $validated = $request->validate([
            'ativo' => 'sometimes|boolean',
        ]);

        $updated = $this->cartaoService->saveCard($card->empresa, [
            'ativo' => array_key_exists('ativo', $validated)
                ? (bool) $validated['ativo']
                : !(bool) $card->ativo,
        ], $card);

        return response()->json([
            'success' => true,
            'message' => $updated->ativo
                ? 'Cartao fidelidade ativado com sucesso.'
                : 'Cartao fidelidade desativado com sucesso.',
            'data' => $this->cartaoService->serializeCard($updated),
        ]);
    }

    public function progressoCliente(int $empresaId): JsonResponse
    {
        $user = Auth::user();
        if ($this->normalizePerfil($user->perfil ?? $user->role ?? $user->tipo ?? null) !== 'cliente') {
            return $this->forbidden('Apenas clientes podem consultar o progresso do cartao fidelidade.');
        }

        $empresa = Empresa::query()->publiclyVisible()->find($empresaId);
        if (!$empresa) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa indisponivel para consulta do cartao fidelidade.',
            ], 404);
        }

        $snapshot = $this->cartaoService->customerCardSnapshot($empresa, $user);

        return response()->json([
            'success' => true,
            'message' => $snapshot['message'],
            'data' => array_merge($snapshot, [
                'empresa' => [
                    'id' => $empresa->id,
                    'nome' => $empresa->nome,
                    'status' => $empresa->operationalStatus(),
                    'ativo' => (bool) $empresa->ativo,
                ],
            ]),
        ]);
    }

    public function adicionarPonto(Request $request, int $id, int $clienteId): JsonResponse
    {
        $empresa = $this->resolveOwnedEmpresa(Auth::user());
        if (!$empresa) {
            return $this->empresaNaoEncontrada();
        }

        $card = CartaoFidelidade::query()->find($id);
        if (!$card) {
            return $this->cartaoNaoEncontrado();
        }

        $customer = User::query()->find($clienteId);
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente nao encontrado.',
            ], 404);
        }

        if ($this->normalizePerfil($customer->perfil ?? $customer->role ?? $customer->tipo ?? null) !== 'cliente') {
            return response()->json([
                'success' => false,
                'message' => 'A fidelidade so pode ser operada para clientes.',
            ], 422);
        }

        try {
            $snapshot = $this->cartaoService->addVisitPoint($empresa, $card, $customer, Auth::user());
        } catch (DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => 'Ponto registrado com sucesso.',
            'data' => [
                'cliente' => [
                    'id' => $customer->id,
                    'nome' => $customer->name,
                    'telefone' => $customer->telefone,
                ],
                'cartao_fidelidade' => $snapshot,
            ],
        ]);
    }

    public function resgatar(Request $request, int $id, int $clienteId): JsonResponse
    {
        $empresa = $this->resolveOwnedEmpresa(Auth::user());
        if (!$empresa) {
            return $this->empresaNaoEncontrada();
        }

        $card = CartaoFidelidade::query()->find($id);
        if (!$card) {
            return $this->cartaoNaoEncontrado();
        }

        $customer = User::query()->find($clienteId);
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente nao encontrado.',
            ], 404);
        }

        if ($this->normalizePerfil($customer->perfil ?? $customer->role ?? $customer->tipo ?? null) !== 'cliente') {
            return response()->json([
                'success' => false,
                'message' => 'A fidelidade so pode ser operada para clientes.',
            ], 422);
        }

        try {
            $snapshot = $this->cartaoService->redeemReward($empresa, $card, $customer, Auth::user());
        } catch (DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => 'Recompensa validada com sucesso.',
            'data' => [
                'cliente' => [
                    'id' => $customer->id,
                    'nome' => $customer->name,
                    'telefone' => $customer->telefone,
                ],
                'cartao_fidelidade' => $snapshot,
            ],
        ]);
    }

    private function validatePayload(Request $request, bool $partial = false): array
    {
        $rules = [
            'titulo' => ($partial ? 'sometimes' : 'required') . '|string|max:80',
            'descricao' => 'nullable|string|max:280',
            'regra_ganho' => 'nullable|string|max:160',
            'pontos_por_visita' => 'nullable|integer|min:1|max:100',
            'pontos_necessarios' => 'nullable|integer|min:1|max:500',
            'recompensa_descricao' => 'nullable|string|max:280',
            'data_expiracao' => 'nullable|date',
            'ativo' => 'sometimes|boolean',
        ];

        return $request->validate($rules);
    }

    private function canAccessCard(User $user, CartaoFidelidade $card): bool
    {
        if ($this->normalizePerfil($user->perfil ?? $user->role ?? $user->tipo ?? null) !== 'empresa') {
            return false;
        }

        $empresa = $this->resolveOwnedEmpresa($user);

        return $empresa?->id === $card->empresa_id;
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

        return null;
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

        if (in_array($value, ['admin', 'administrador', 'master', 'admin_master', 'administrador_master'], true)) {
            return 'admin';
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

    private function cartaoNaoEncontrado(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Cartao fidelidade nao encontrado.',
        ], 404);
    }
}
