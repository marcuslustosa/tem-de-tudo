<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CampanhaMultiplicador;
use App\Models\Empresa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CampanhaMultiplicadorController extends Controller
{
    /**
     * Lista campanhas da empresa autenticada.
     */
    public function index(Request $request): JsonResponse
    {
        $empresaId = Auth::user()->empresa_id;

        $campanhas = CampanhaMultiplicador::where('empresa_id', $empresaId)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $campanhas,
            'total'   => $campanhas->count(),
        ]);
    }

    /**
     * Cria uma nova campanha de multiplicador.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nome'         => 'required|string|max:120',
            'descricao'    => 'nullable|string|max:500',
            'multiplicador'=> 'required|numeric|min:1.1|max:20',
            'data_inicio'  => 'required|date',
            'data_fim'     => 'required|date|after:data_inicio',
            'ativo'        => 'boolean',
        ]);

        $data['empresa_id'] = Auth::user()->empresa_id;
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
        $campanha = CampanhaMultiplicador::where('id', $id)
            ->where('empresa_id', Auth::user()->empresa_id)
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
        $campanha = CampanhaMultiplicador::where('id', $id)
            ->where('empresa_id', Auth::user()->empresa_id)
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
}
