<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\User;
use App\Services\AvaliacaoService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AvaliacaoController extends Controller
{
    public function __construct(
        private readonly AvaliacaoService $avaliacaoService
    ) {
    }

    public function listarPorEmpresa(Request $request, int $empresaId): JsonResponse
    {
        $empresa = Empresa::query()->publiclyVisible()->find($empresaId);
        if (!$empresa) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa nao encontrada para avaliacoes.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->avaliacaoService->publicPayload(
                $empresa,
                (int) $request->input('limit', 10)
            ),
        ]);
    }

    public function store(Request $request, ?int $empresaId = null): JsonResponse
    {
        $customer = Auth::user();
        if (!$customer instanceof User || $this->normalizePerfil($customer) !== 'cliente') {
            return $this->forbidden('Apenas clientes podem avaliar empresas.');
        }

        $validated = $request->validate([
            'empresa_id' => 'sometimes|integer|min:1',
            'estrelas' => 'required|integer|min:1|max:5',
            'comentario' => 'nullable|string|max:500',
        ]);

        $resolvedEmpresaId = $empresaId ?: (int) ($validated['empresa_id'] ?? 0);
        $empresa = Empresa::query()->find($resolvedEmpresaId);
        if (!$empresa) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa nao encontrada.',
            ], 404);
        }

        try {
            $avaliacao = $this->avaliacaoService->createCustomerReview($empresa, $customer, $validated);
        } catch (DomainException $e) {
            return $this->domainErrorResponse($e);
        }

        return response()->json([
            'success' => true,
            'message' => 'Avaliacao registrada com sucesso.',
            'data' => [
                'avaliacao' => $this->avaliacaoService->serializeReview($avaliacao, includeCompany: true),
                'summary' => $this->avaliacaoService->summary($empresa->fresh()),
            ],
        ], 201);
    }

    public function updateMinha(Request $request, int $empresaId): JsonResponse
    {
        $customer = Auth::user();
        if (!$customer instanceof User || $this->normalizePerfil($customer) !== 'cliente') {
            return $this->forbidden('Apenas clientes podem editar avaliacoes.');
        }

        $validated = $request->validate([
            'estrelas' => 'required|integer|min:1|max:5',
            'comentario' => 'nullable|string|max:500',
        ]);

        $empresa = Empresa::query()->find($empresaId);
        if (!$empresa) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa nao encontrada.',
            ], 404);
        }

        try {
            $avaliacao = $this->avaliacaoService->updateCustomerReview($empresa, $customer, $validated);
        } catch (DomainException $e) {
            return $this->domainErrorResponse($e);
        }

        return response()->json([
            'success' => true,
            'message' => 'Avaliacao atualizada com sucesso.',
            'data' => [
                'avaliacao' => $this->avaliacaoService->serializeReview($avaliacao, includeCompany: true),
                'summary' => $this->avaliacaoService->summary($empresa->fresh()),
            ],
        ]);
    }

    public function minhasAvaliacoes(Request $request): JsonResponse
    {
        $customer = Auth::user();
        if (!$customer instanceof User || $this->normalizePerfil($customer) !== 'cliente') {
            return $this->forbidden('Apenas clientes podem consultar suas avaliacoes.');
        }

        return response()->json([
            'success' => true,
            'data' => $this->avaliacaoService->customerPayload(
                $customer,
                $request->filled('empresa_id') ? (int) $request->input('empresa_id') : null
            ),
        ]);
    }

    public function empresaAvaliacoes(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user instanceof User || $this->normalizePerfil($user) !== 'empresa') {
            return $this->forbidden('Apenas empresas podem consultar avaliacoes recebidas.');
        }

        $empresa = $this->resolveOwnedEmpresa($user);
        if (!$empresa) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa nao encontrada.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->avaliacaoService->companyPayload(
                $empresa,
                (int) $request->input('limit', 50)
            ),
        ]);
    }

    public function minhaAvaliacao(int $empresaId): JsonResponse
    {
        $customer = Auth::user();
        if (!$customer instanceof User || $this->normalizePerfil($customer) !== 'cliente') {
            return $this->forbidden('Apenas clientes podem consultar sua avaliacao.');
        }

        $empresa = Empresa::query()->find($empresaId);
        if (!$empresa) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa nao encontrada.',
            ], 404);
        }

        $avaliacao = $this->avaliacaoService->findCustomerReview($empresa, $customer);

        return response()->json([
            'success' => true,
            'data' => $avaliacao
                ? $this->avaliacaoService->serializeReview($avaliacao, includeCompany: true)
                : null,
        ]);
    }

    public function estatisticas(int $empresaId): JsonResponse
    {
        $empresa = Empresa::query()->publiclyVisible()->find($empresaId);
        if (!$empresa) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa nao encontrada.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->avaliacaoService->summary($empresa),
        ]);
    }

    private function forbidden(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 403);
    }

    private function domainErrorResponse(DomainException $e): JsonResponse
    {
        $message = $e->getMessage();
        $normalized = strtolower(trim($message));
        $status = 422;

        if (str_contains($normalized, 'ja avaliou')) {
            $status = 409;
        } elseif (str_contains($normalized, 'ainda nao avaliou')) {
            $status = 404;
        } elseif (
            str_contains($normalized, 'apenas clientes') ||
            str_contains($normalized, 'precisa estar vinculado') ||
            str_contains($normalized, 'nao esta apta')
        ) {
            $status = 403;
        }

        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }

    private function resolveOwnedEmpresa(User $user): ?Empresa
    {
        if (method_exists($user, 'empresa')) {
            $empresa = $user->empresa()->first();
            if ($empresa instanceof Empresa) {
                return $empresa;
            }
        }

        return Empresa::query()->where('owner_id', $user->id)->first();
    }

    private function normalizePerfil(User $user): string
    {
        $perfil = strtolower(trim((string) ($user->perfil ?? $user->role ?? $user->tipo ?? '')));

        if (in_array($perfil, ['cliente', 'customer'], true)) {
            return 'cliente';
        }

        if (in_array($perfil, ['empresa', 'company'], true)) {
            return 'empresa';
        }

        if (in_array($perfil, ['admin', 'administrador'], true)) {
            return 'admin';
        }

        return $perfil;
    }
}
