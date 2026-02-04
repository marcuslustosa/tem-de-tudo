<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Produto;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProdutoController extends Controller
{
    /**
     * Listar produtos de uma empresa
     */
    public function index(Request $request, $empresaId)
    {
        try {
            $empresa = Empresa::findOrFail($empresaId);
            
            $query = Produto::where('empresa_id', $empresaId)
                ->where('ativo', true);
            
            // Filtrar por categoria se especificado
            if ($request->has('categoria')) {
                $query->where('categoria', $request->categoria);
            }
            
            // Busca por nome
            if ($request->has('search')) {
                $query->where('nome', 'LIKE', '%' . $request->search . '%');
            }
            
            $produtos = $query->orderBy('nome')->get();
            
            // Adicionar URL da imagem e pontos calculados
            $produtos->each(function ($produto) use ($empresa) {
                $produto->imagem_url = $produto->imagem_url;
                $produto->pontos_calculados = $produto->calcularPontos($empresa->points_multiplier);
            });
            
            return response()->json([
                'success' => true,
                'produtos' => $produtos,
                'empresa' => [
                    'id' => $empresa->id,
                    'nome' => $empresa->nome,
                    'points_multiplier' => $empresa->points_multiplier
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar produtos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar um produto específico
     */
    public function show($empresaId, $id)
    {
        try {
            $produto = Produto::where('empresa_id', $empresaId)
                ->where('id', $id)
                ->where('ativo', true)
                ->with('empresa')
                ->firstOrFail();
            
            $produto->imagem_url = $produto->imagem_url;
            $produto->pontos_calculados = $produto->calcularPontos($produto->empresa->points_multiplier);
            
            return response()->json([
                'success' => true,
                'produto' => $produto
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Produto não encontrado'
            ], 404);
        }
    }

    /**
     * Criar novo produto (apenas donos da empresa)
     */
    public function store(Request $request, $empresaId)
    {
        try {
            $user = Auth::user();
            $empresa = Empresa::where('id', $empresaId)
                ->where('owner_id', $user->id)
                ->firstOrFail();
            
            $validated = $request->validate([
                'nome' => 'required|string|max:255',
                'descricao' => 'nullable|string',
                'preco' => 'required|numeric|min:0',
                'categoria' => 'nullable|string|max:100',
                'imagem' => 'nullable|string|url',
                'estoque' => 'nullable|integer|min:0',
                'pontos_gerados' => 'nullable|integer|min:0'
            ]);
            
            $validated['empresa_id'] = $empresaId;
            
            $produto = Produto::create($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Produto criado com sucesso',
                'produto' => $produto
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar produto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualizar produto (apenas donos da empresa)
     */
    public function update(Request $request, $empresaId, $id)
    {
        try {
            $user = Auth::user();
            $empresa = Empresa::where('id', $empresaId)
                ->where('owner_id', $user->id)
                ->firstOrFail();
            
            $produto = Produto::where('empresa_id', $empresaId)
                ->where('id', $id)
                ->firstOrFail();
            
            $validated = $request->validate([
                'nome' => 'string|max:255',
                'descricao' => 'nullable|string',
                'preco' => 'numeric|min:0',
                'categoria' => 'nullable|string|max:100',
                'imagem' => 'nullable|string|url',
                'ativo' => 'boolean',
                'estoque' => 'nullable|integer|min:0',
                'pontos_gerados' => 'nullable|integer|min:0'
            ]);
            
            $produto->update($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Produto atualizado com sucesso',
                'produto' => $produto
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar produto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deletar produto (apenas donos da empresa)
     */
    public function destroy($empresaId, $id)
    {
        try {
            $user = Auth::user();
            $empresa = Empresa::where('id', $empresaId)
                ->where('owner_id', $user->id)
                ->firstOrFail();
            
            $produto = Produto::where('empresa_id', $empresaId)
                ->where('id', $id)
                ->firstOrFail();
            
            $produto->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Produto removido com sucesso'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover produto: ' . $e->getMessage()
            ], 500);
        }
    }
}