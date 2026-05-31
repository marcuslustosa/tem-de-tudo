<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CampanhaMultiplicador;
use App\Models\Empresa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CampanhaMultiplicadorController extends Controller
{
    /**
     * Lista campanhas da empresa autenticada.
     */
    public function index(Request $request): JsonResponse
    {
        $empresaId = $this->resolveEmpresaId();

        if (!$empresaId) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa nao encontrada para este login.',
            ], 404);
        }

        if (!Schema::hasTable('campanhas_multiplicador')) {
            return response()->json([
                'success' => true,
                'data' => [],
                'total' => 0,
                'message' => 'Campanhas temporarias nao estao disponiveis neste ambiente.',
                'legacy_disabled' => true,
            ]);
        }

        try {
            $campanhas = CampanhaMultiplicador::where('empresa_id', $empresaId)
                ->orderByDesc('created_at')
                ->get();

            return response()->json([
                'success' => true,
                'data'    => $campanhas,
                'total'   => $campanhas->count(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Falha ao listar campanhas de multiplicador da empresa.', [
                'empresa_id' => $empresaId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Nao foi possivel carregar campanhas temporarias agora.',
            ], 500);
        }
    }

    /**
     * Cria uma nova campanha de multiplicador.
     */
    public function store(Request $request): JsonResponse
    {
        if (!Schema::hasTable('campanhas_multiplicador')) {
            return response()->json([
                'success' => false,
                'message' => 'Use a Gestao de Ofertas para operar campanhas desta empresa.',
                'error' => 'legacy_disabled',
            ], 422);
        }

        $data = $request->validate([
            'nome'         => 'required|string|max:120',
            'descricao'    => 'nullable|string|max:500',
            'multiplicador'=> 'required|numeric|min:1.1|max:20',
            'data_inicio'  => 'required|date',
            'data_fim'     => 'required|date|after:data_inicio',
            'ativo'        => 'boolean',
        ]);

        $empresaId = $this->resolveEmpresaId();
        if (!$empresaId) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa nao encontrada para este login.',
            ], 404);
        }

        $data['empresa_id'] = $empresaId;
        $data['ativo'] = $data['ativo'] ?? true;

        $campanha = CampanhaMultiplicador::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Campanha criada com sucesso.',
            'data'    => $campanha,
        ], 201);
    }

    /**
     * Atualiza uma campanha existente da empresa autenticada.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        if (!Schema::hasTable('campanhas_multiplicador')) {
            return response()->json([
                'success' => false,
                'message' => 'Use a Gestao de Ofertas para operar campanhas desta empresa.',
                'error' => 'legacy_disabled',
            ], 422);
        }

        $empresaId = $this->resolveEmpresaId();
        if (!$empresaId) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa nao encontrada para este login.',
            ], 404);
        }

        $campanha = CampanhaMultiplicador::where('id', $id)
            ->where('empresa_id', $empresaId)
            ->firstOrFail();

        $data = $request->validate([
            'nome'         => 'sometimes|string|max:120',
            'descricao'    => 'nullable|string|max:500',
            'multiplicador'=> 'sometimes|numeric|min:1.1|max:20',
            'data_inicio'  => 'sometimes|date',
            'data_fim'     => 'sometimes|date|after:data_inicio',
            'ativo'        => 'boolean',
        ]);

        $campanha->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Campanha atualizada.',
            'data'    => $campanha->fresh(),
        ]);
    }

    /**
     * Remove uma campanha da empresa autenticada.
     */
    public function destroy(int $id): JsonResponse
    {
        if (!Schema::hasTable('campanhas_multiplicador')) {
            return response()->json([
                'success' => false,
                'message' => 'Use a Gestao de Ofertas para operar campanhas desta empresa.',
                'error' => 'legacy_disabled',
            ], 422);
        }

        $empresaId = $this->resolveEmpresaId();
        if (!$empresaId) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa nao encontrada para este login.',
            ], 404);
        }

        $campanha = CampanhaMultiplicador::where('id', $id)
            ->where('empresa_id', $empresaId)
            ->firstOrFail();

        $campanha->delete();

        return response()->json([
            'success' => true,
            'message' => 'Campanha removida.',
        ]);
    }

    /**
     * Admin: lista todas as campanhas de todas as empresas.
     */
    public function adminIndex(Request $request): JsonResponse
    {
        if (!Schema::hasTable('campanhas_multiplicador')) {
            return response()->json([
                'success' => true,
                'data' => [],
                'total' => 0,
                'current_page' => 1,
                'last_page' => 1,
                'legacy_disabled' => true,
            ]);
        }

        $query = CampanhaMultiplicador::with('empresa:id,nome')
            ->orderByDesc('created_at');

        if ($request->filled('empresa_id')) {
            $query->where('empresa_id', $request->integer('empresa_id'));
        }

        $campanhas = $query->paginate(30);

        return response()->json([
            'success' => true,
            'data'    => $campanhas->items(),
            'total'   => $campanhas->total(),
            'current_page' => $campanhas->currentPage(),
            'last_page'    => $campanhas->lastPage(),
        ]);
    }

    private function resolveEmpresaId(): ?int
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        if (isset($user->empresa_id) && is_numeric($user->empresa_id) && (int) $user->empresa_id > 0) {
            return (int) $user->empresa_id;
        }

        $query = Empresa::query();

        if (Schema::hasColumn('empresas', 'owner_id')) {
            $empresa = (clone $query)->where('owner_id', $user->id)->first();
            if ($empresa) {
                return (int) $empresa->id;
            }
        }

        if (Schema::hasColumn('empresas', 'user_id')) {
            $empresa = (clone $query)->where('user_id', $user->id)->first();
            if ($empresa) {
                return (int) $empresa->id;
            }
        }

        $empresa = (clone $query)->find($user->id);

        return $empresa ? (int) $empresa->id : null;
    }
}
