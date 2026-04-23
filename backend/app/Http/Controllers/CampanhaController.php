<?php

namespace App\Http\Controllers;

use App\Services\CampanhaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CampanhaController extends Controller
{
    public function __construct(
        private readonly CampanhaService $campanhaService
    ) {}

    /**
     * Lista todas as campanhas de uma empresa.
     */
    public function index(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id ?? $request->input('empresa_id');

        if (!$empresaId) {
            return response()->json(['error' => 'empresa_id obrigatório'], 400);
        }

        $campanhas = $this->campanhaService->listarTodasCampanhas($empresaId);

        return response()->json([
            'success' => true,
            'campanhas' => $campanhas,
            'estatisticas' => $this->campanhaService->getEstatisticas($empresaId),
        ]);
    }

    /**
     * Lista apenas campanhas ativas.
     */
    public function ativas(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id ?? $request->input('empresa_id');

        if (!$empresaId) {
            return response()->json(['error' => 'empresa_id obrigatório'], 400);
        }

        $campanhas = $this->campanhaService->listarCampanhasAtivas($empresaId);

        return response()->json([
            'success' => true,
            'campanhas' => $campanhas,
        ]);
    }

    /**
     * Cria uma nova campanha.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'empresa_id' => 'required|exists:empresas,id',
            'nome' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'multiplicador' => 'required|numeric|min:1|max:10',
            'data_inicio' => 'required|date',
            'data_fim' => 'required|date|after:data_inicio',
            'ativo' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $campanha = $this->campanhaService->criarCampanha($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Campanha criada com sucesso',
                'campanha' => $campanha,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erro ao criar campanha: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Atualiza uma campanha existente.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nome' => 'sometimes|string|max:255',
            'descricao' => 'nullable|string',
            'multiplicador' => 'sometimes|numeric|min:1|max:10',
            'data_inicio' => 'sometimes|date',
            'data_fim' => 'sometimes|date',
            'ativo' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $campanha = $this->campanhaService->atualizarCampanha($id, $validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Campanha atualizada com sucesso',
                'campanha' => $campanha,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erro ao atualizar campanha: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Desativa uma campanha.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->campanhaService->desativarCampanha($id);

            return response()->json([
                'success' => true,
                'message' => 'Campanha desativada com sucesso',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erro ao desativar campanha: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtem multiplicador ativo no momento.
     */
    public function multiplicadorAtivo(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id ?? $request->input('empresa_id');

        if (!$empresaId) {
            return response()->json(['error' => 'empresa_id obrigatório'], 400);
        }

        $multiplicador = $this->campanhaService->getMultiplicadorAtivo($empresaId);

        return response()->json([
            'success' => true,
            'multiplicador' => $multiplicador,
            'ativo' => $multiplicador > 1.0,
        ]);
    }
}
