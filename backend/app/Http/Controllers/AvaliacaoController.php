<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\User;
use App\Services\AvaliacaoService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;

class AvaliacaoController extends Controller
{
    public function __construct(
        private readonly AvaliacaoService $avaliacaoService
    ) {
    }

    public function listarPorEmpresa(Request $request, int $empresaId): JsonResponse
    {
        $empresa = null;

        try {
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
        } catch (\Throwable $e) {
            Log::warning('Falha ao carregar avaliacoes publicas da empresa', [
                'empresa_id' => $empresaId,
                'error' => $e->getMessage(),
            ]);

            $fallback = $this->buildSafePublicPayload($empresa, $empresaId, (int) $request->input('limit', 10));
            if ($fallback === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Empresa nao encontrada para avaliacoes.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $fallback,
                'warning' => 'Falha parcial ao carregar avaliacoes.',
            ]);
        }
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

    private function buildSafePublicPayload(?Empresa $empresa, int $empresaId, int $limit = 10): ?array
    {
        try {
            $empresa ??= Empresa::query()->publiclyVisible()->find($empresaId);
        } catch (\Throwable) {
            $empresa = null;
        }

        if (!$empresa) {
            return null;
        }

        $average = round((float) ($empresa->avaliacao_media ?? 0), 1);
        $total = (int) ($empresa->total_avaliacoes ?? 0);
        $distribution = [];
        $items = [];

        if (
            Schema::hasTable('avaliacoes')
            && Schema::hasColumn('avaliacoes', 'empresa_id')
            && Schema::hasColumn('avaliacoes', 'estrelas')
        ) {
            try {
                $baseQuery = DB::table('avaliacoes')->where('empresa_id', $empresaId);

                $aggregate = (clone $baseQuery)
                    ->selectRaw('AVG(estrelas) as average, COUNT(*) as total')
                    ->first();

                $average = round((float) ($aggregate->average ?? $average), 1);
                $total = (int) ($aggregate->total ?? $total);

                $counts = (clone $baseQuery)
                    ->selectRaw('estrelas, COUNT(*) as total')
                    ->groupBy('estrelas')
                    ->pluck('total', 'estrelas');

                for ($star = 5; $star >= 1; $star -= 1) {
                    $distribution[] = [
                        'star' => $star,
                        'total' => (int) ($counts[$star] ?? 0),
                    ];
                }

                $reviewColumns = ['id', 'empresa_id', 'user_id', 'estrelas'];
                foreach (['comentario', 'created_at', 'updated_at'] as $optionalColumn) {
                    if (Schema::hasColumn('avaliacoes', $optionalColumn)) {
                        $reviewColumns[] = $optionalColumn;
                    }
                }

                $reviewRows = (clone $baseQuery)
                    ->select($reviewColumns)
                    ->orderByDesc('updated_at')
                    ->orderByDesc('created_at')
                    ->limit(max(1, $limit))
                    ->get();

                $usersById = $this->loadReviewUsers($reviewRows->pluck('user_id')->filter()->values()->all());

                $items = $reviewRows->map(function ($row) use ($usersById) {
                    $user = $usersById[(int) ($row->user_id ?? 0)] ?? null;

                    return [
                        'id' => (int) ($row->id ?? 0),
                        'empresa_id' => (int) ($row->empresa_id ?? 0),
                        'user_id' => (int) ($row->user_id ?? 0),
                        'nota' => (int) ($row->estrelas ?? 0),
                        'estrelas' => (int) ($row->estrelas ?? 0),
                        'comentario' => $this->safeString($row->comentario ?? null),
                        'created_at' => $this->formatTimestamp($row->created_at ?? null),
                        'updated_at' => $this->formatTimestamp($row->updated_at ?? null),
                        'cliente' => [
                            'id' => (int) ($row->user_id ?? 0),
                            'nome' => $this->safeString($user['nome'] ?? null),
                            'email' => $this->safeString($user['email'] ?? null),
                            'telefone' => $this->safeString($user['telefone'] ?? null),
                        ],
                    ];
                })->values()->all();
            } catch (\Throwable $nested) {
                Log::warning('Fallback de avaliacoes publicas reduziu payload para resumo vazio', [
                    'empresa_id' => $empresaId,
                    'error' => $nested->getMessage(),
                ]);
            }
        }

        return [
            'empresa' => [
                'id' => (int) $empresa->id,
                'nome' => $this->safeString($empresa->nome, 'Empresa'),
                'avaliacao_media' => $average,
                'total_avaliacoes' => $total,
                'status' => $empresa->operationalStatus(),
                'ativo' => (bool) $empresa->ativo,
            ],
            'summary' => [
                'average' => $average,
                'total' => $total,
                'distribution' => $distribution,
            ],
            'items' => $items,
        ];
    }

    private function loadReviewUsers(array $userIds): array
    {
        if (empty($userIds) || !Schema::hasTable('users')) {
            return [];
        }

        $available = array_values(array_filter([
            Schema::hasColumn('users', 'name') ? 'name' : null,
            Schema::hasColumn('users', 'nome') ? 'nome' : null,
            Schema::hasColumn('users', 'email') ? 'email' : null,
            Schema::hasColumn('users', 'telefone') ? 'telefone' : null,
            Schema::hasColumn('users', 'phone') ? 'phone' : null,
            Schema::hasColumn('users', 'celular') ? 'celular' : null,
        ]));

        $rows = DB::table('users')
            ->select(array_merge(['id'], $available))
            ->whereIn('id', $userIds)
            ->get();

        $users = [];
        foreach ($rows as $row) {
            $users[(int) $row->id] = [
                'nome' => $this->safeString($row->name ?? $row->nome ?? null),
                'email' => $this->safeString($row->email ?? null),
                'telefone' => $this->safeString($row->telefone ?? $row->phone ?? $row->celular ?? null),
            ];
        }

        return $users;
    }

    private function formatTimestamp($value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        $string = trim((string) ($value ?? ''));
        if ($string === '') {
            return null;
        }

        try {
            return Carbon::parse($string)->toIso8601String();
        } catch (\Throwable) {
            return $this->safeString($string);
        }
    }

    private function safeString($value, ?string $fallback = null): ?string
    {
        if ($value === null) {
            return $fallback;
        }

        $string = trim((string) $value);
        if ($string === '') {
            return $fallback;
        }

        if (function_exists('mb_check_encoding') && !mb_check_encoding($string, 'UTF-8')) {
            $string = @iconv('UTF-8', 'UTF-8//IGNORE', $string) ?: $fallback ?: '';
        }

        return $string !== '' ? $string : $fallback;
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
