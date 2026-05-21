<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\LembreteAusencia;
use App\Services\LembreteRetornoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class LembreteRetornoController extends Controller
{
    public function __construct(
        private readonly LembreteRetornoService $lembreteService
    ) {
    }

    public function index(): JsonResponse
    {
        $empresa = $this->resolveOwnedEmpresa(Auth::user());
        if (!$empresa) {
            return $this->empresaNaoEncontrada();
        }

        $items = $this->lembreteService->companyReminders($empresa)
            ->get()
            ->map(fn (LembreteAusencia $reminder) => $this->lembreteService->serializeReminder($reminder))
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
            'dias_sem_visita' => 'required|integer|min:1|max:365',
            'titulo' => 'required|string|max:80',
            'mensagem' => 'required|string|max:300',
            'ativo' => 'sometimes|boolean',
        ]);

        $reminder = $this->lembreteService->saveReminder($empresa, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Lembrete de retorno salvo com sucesso.',
            'data' => $this->lembreteService->serializeReminder($reminder),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $reminder = LembreteAusencia::query()->find($id);
        if (!$reminder) {
            return $this->lembreteNaoEncontrado();
        }

        if (!$this->canAccessReminder(Auth::user(), $reminder)) {
            return $this->forbidden('Voce nao pode visualizar este lembrete.');
        }

        return response()->json([
            'success' => true,
            'data' => $this->lembreteService->serializeReminder($reminder),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $reminder = LembreteAusencia::query()->find($id);
        if (!$reminder) {
            return $this->lembreteNaoEncontrado();
        }

        if (!$this->canAccessReminder(Auth::user(), $reminder)) {
            return $this->forbidden('Voce nao pode alterar este lembrete.');
        }

        $validated = $request->validate([
            'dias_sem_visita' => 'sometimes|integer|min:1|max:365',
            'titulo' => 'sometimes|filled|string|max:80',
            'mensagem' => 'sometimes|filled|string|max:300',
            'ativo' => 'sometimes|boolean',
        ]);

        $updated = $this->lembreteService->saveReminder($reminder->empresa, $validated, $reminder);

        return response()->json([
            'success' => true,
            'message' => 'Lembrete de retorno atualizado com sucesso.',
            'data' => $this->lembreteService->serializeReminder($updated),
        ]);
    }

    public function toggle(Request $request, int $id): JsonResponse
    {
        $reminder = LembreteAusencia::query()->find($id);
        if (!$reminder) {
            return $this->lembreteNaoEncontrado();
        }

        if (!$this->canAccessReminder(Auth::user(), $reminder)) {
            return $this->forbidden('Voce nao pode alterar este lembrete.');
        }

        $validated = $request->validate([
            'ativo' => 'sometimes|boolean',
        ]);

        $updated = $this->lembreteService->saveReminder($reminder->empresa, [
            'ativo' => array_key_exists('ativo', $validated)
                ? (bool) $validated['ativo']
                : !(bool) $reminder->ativo,
        ], $reminder);

        return response()->json([
            'success' => true,
            'message' => $updated->ativo
                ? 'Lembrete de retorno ativado com sucesso.'
                : 'Lembrete de retorno desativado com sucesso.',
            'data' => $this->lembreteService->serializeReminder($updated),
        ]);
    }

    public function enviarElegiveis(Request $request): JsonResponse
    {
        $empresa = $this->resolveOwnedEmpresa(Auth::user());
        if (!$empresa) {
            return $this->empresaNaoEncontrada();
        }

        $validated = $request->validate([
            'lembrete_id' => 'nullable|integer|min:1',
        ]);

        $reminder = isset($validated['lembrete_id'])
            ? LembreteAusencia::query()->find((int) $validated['lembrete_id'])
            : ($this->lembreteService->activeCompanyReminder($empresa) ?? $this->lembreteService->latestCompanyReminder($empresa));

        if (!$reminder) {
            return $this->lembreteNaoEncontrado();
        }

        try {
            $result = $this->lembreteService->sendEligibleReminders($empresa, $reminder, Auth::user());
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 409);
        }

        if (($result['delivery']['status'] ?? null) === 'config_missing') {
            return response()->json([
                'success' => false,
                'error' => 'config_missing',
                'message' => $result['delivery']['message'] ?? 'Configuração de push pendente no servidor.',
                'data' => $result['lembrete'],
                'meta' => [
                    'delivery' => $result['delivery'],
                ],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lembretes de retorno processados para clientes vinculados elegiveis.',
            'data' => $result['lembrete'],
            'meta' => [
                'delivery' => $result['delivery'],
            ],
        ]);
    }

    private function canAccessReminder($user, LembreteAusencia $reminder): bool
    {
        if ($this->normalizePerfil($user->perfil ?? $user->role ?? $user->tipo ?? null) !== 'empresa') {
            return false;
        }

        $empresa = $this->resolveOwnedEmpresa($user);

        return $empresa?->id === $reminder->empresa_id;
    }

    private function resolveOwnedEmpresa($user): ?Empresa
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

    private function normalizePerfil(?string $perfil): ?string
    {
        if (!$perfil) {
            return null;
        }

        $value = strtolower(trim($perfil));
        if (in_array($value, ['empresa', 'estabelecimento', 'parceiro', 'lojista'], true)) {
            return 'empresa';
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

    private function lembreteNaoEncontrado(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Lembrete de retorno nao encontrado.',
        ], 404);
    }
}
