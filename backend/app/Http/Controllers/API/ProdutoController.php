<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\Produto;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ProdutoController extends Controller
{
    /**
     * Listar produtos de uma empresa
     */
    public function index(Request $request, $empresaId)
    {
        try {
            if (!Schema::hasTable('empresas')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Catalogo indisponivel neste ambiente.',
                    'data' => [],
                    'produtos' => [],
                    'empresa' => null,
                ]);
            }

            $empresa = Empresa::find($empresaId);
            if (!$empresa) {
                return response()->json([
                    'success' => false,
                    'message' => 'Empresa nao encontrada.',
                ], 404);
            }

            if (!Schema::hasTable('produtos')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Catalogo indisponivel neste ambiente.',
                    'data' => [],
                    'produtos' => [],
                    'empresa' => [
                        'id' => $empresa->id,
                        'nome' => $empresa->nome,
                        'points_multiplier' => (float) ($empresa->points_multiplier ?? 1),
                    ],
                ]);
            }

            $query = Produto::query()->where('empresa_id', $empresaId);

            if (Schema::hasColumn('produtos', 'ativo')) {
                $query->where('ativo', true);
            }

            if ($request->filled('categoria') && Schema::hasColumn('produtos', 'categoria')) {
                $query->where('categoria', $request->input('categoria'));
            }

            if ($request->filled('search') && Schema::hasColumn('produtos', 'nome')) {
                $query->where('nome', 'LIKE', '%' . $request->input('search') . '%');
            }

            if (Schema::hasColumn('produtos', 'nome')) {
                $query->orderBy('nome');
            }

            $produtos = $query->get();
            $multiplicador = (float) ($empresa->points_multiplier ?? 1);

            $produtos->each(function ($produto) use ($multiplicador) {
                $produto->imagem_url = $produto->imagem_url;
                $produto->pontos_calculados = $produto->calcularPontos($multiplicador);
            });

            return response()->json([
                'success' => true,
                'data' => $produtos,
                'produtos' => $produtos,
                'empresa' => [
                    'id' => $empresa->id,
                    'nome' => $empresa->nome,
                    'points_multiplier' => $multiplicador,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Erro ao carregar produtos da empresa', [
                'empresa_id' => $empresaId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar produtos no momento.',
            ], 500);
        }
    }

    /**
     * Mostrar um produto especifico
     */
    public function show($empresaId, $id)
    {
        try {
            if (!Schema::hasTable('produtos')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Catalogo indisponivel neste ambiente.',
                ], 404);
            }

            $query = Produto::where('empresa_id', $empresaId)
                ->where('id', $id)
                ->with('empresa');

            if (Schema::hasColumn('produtos', 'ativo')) {
                $query->where('ativo', true);
            }

            $produto = $query->firstOrFail();

            $produto->imagem_url = $produto->imagem_url;
            $produto->pontos_calculados = $produto->calcularPontos((float) ($produto->empresa->points_multiplier ?? 1));

            return response()->json([
                'success' => true,
                'produto' => $produto,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Produto nao encontrado',
            ], 404);
        } catch (\Throwable $e) {
            Log::error('Erro ao carregar produto da empresa', [
                'empresa_id' => $empresaId,
                'produto_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar produto no momento.',
            ], 500);
        }
    }

    /**
     * Criar novo produto (apenas dono da empresa)
     */
    public function store(Request $request, $empresaId = null)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nao autenticado.',
                ], 401);
            }

            $resolvedEmpresaId = $this->resolveOwnedEmpresaId($empresaId, $user->id);
            if (!$resolvedEmpresaId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Empresa nao encontrada para este usuario.',
                ], 404);
            }

            Empresa::where('id', $resolvedEmpresaId)
                ->where('owner_id', $user->id)
                ->firstOrFail();

            $validated = $request->validate([
                'nome' => 'required|string|max:255',
                'descricao' => 'nullable|string',
                'preco' => 'required|numeric|min:0',
                'categoria' => 'nullable|string|max:100',
                'imagem' => 'nullable|string|url',
                'estoque' => 'nullable|integer|min:0',
                'pontos_gerados' => 'nullable|integer|min:0',
            ]);

            $validated['empresa_id'] = $resolvedEmpresaId;

            $produto = Produto::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Produto criado com sucesso',
                'produto' => $produto,
            ], 201);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa nao encontrada para este usuario.',
            ], 404);
        } catch (\Throwable $e) {
            Log::error('Erro ao criar produto', [
                'empresa_id' => $empresaId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar produto.',
            ], 500);
        }
    }

    /**
     * Atualizar produto (apenas dono da empresa)
     */
    public function update(Request $request, $empresaId, $id = null)
    {
        if ($id === null) {
            $id = $empresaId;
            $empresaId = null;
        }

        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nao autenticado.',
                ], 401);
            }

            $resolvedEmpresaId = $this->resolveOwnedEmpresaId($empresaId, $user->id);
            if (!$resolvedEmpresaId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Empresa nao encontrada para este usuario.',
                ], 404);
            }

            Empresa::where('id', $resolvedEmpresaId)
                ->where('owner_id', $user->id)
                ->firstOrFail();

            $produto = Produto::where('empresa_id', $resolvedEmpresaId)
                ->where('id', $id)
                ->firstOrFail();

            $validated = $request->validate([
                'nome' => 'sometimes|string|max:255',
                'descricao' => 'nullable|string',
                'preco' => 'sometimes|numeric|min:0',
                'categoria' => 'nullable|string|max:100',
                'imagem' => 'nullable|string|url',
                'ativo' => 'sometimes|boolean',
                'estoque' => 'nullable|integer|min:0',
                'pontos_gerados' => 'nullable|integer|min:0',
            ]);

            $produto->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Produto atualizado com sucesso',
                'produto' => $produto,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Produto ou empresa nao encontrado para este usuario.',
            ], 404);
        } catch (\Throwable $e) {
            Log::error('Erro ao atualizar produto', [
                'empresa_id' => $empresaId,
                'produto_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar produto.',
            ], 500);
        }
    }

    /**
     * Deletar produto (apenas dono da empresa)
     */
    public function destroy($empresaId, $id = null)
    {
        if ($id === null) {
            $id = $empresaId;
            $empresaId = null;
        }

        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nao autenticado.',
                ], 401);
            }

            $resolvedEmpresaId = $this->resolveOwnedEmpresaId($empresaId, $user->id);
            if (!$resolvedEmpresaId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Empresa nao encontrada para este usuario.',
                ], 404);
            }

            Empresa::where('id', $resolvedEmpresaId)
                ->where('owner_id', $user->id)
                ->firstOrFail();

            $produto = Produto::where('empresa_id', $resolvedEmpresaId)
                ->where('id', $id)
                ->firstOrFail();

            $produto->delete();

            return response()->json([
                'success' => true,
                'message' => 'Produto removido com sucesso',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Produto ou empresa nao encontrado para este usuario.',
            ], 404);
        } catch (\Throwable $e) {
            Log::error('Erro ao remover produto', [
                'empresa_id' => $empresaId,
                'produto_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover produto.',
            ], 500);
        }
    }

    private function resolveOwnedEmpresaId($empresaId, int $ownerId): ?int
    {
        if ($empresaId !== null) {
            return (int) $empresaId;
        }

        $ownedId = Empresa::where('owner_id', $ownerId)->value('id');

        return $ownedId ? (int) $ownedId : null;
    }
}
