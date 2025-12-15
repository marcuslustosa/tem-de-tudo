<?php

namespace App\Http\Controllers;

use App\Models\Avaliacao;
use App\Models\Empresa;
use App\Models\InscricaoEmpresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AvaliacaoController extends Controller
{
    /**
     * Criar avaliação para uma empresa
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        if ($user->perfil !== 'cliente') {
            return response()->json([
                'success' => false,
                'message' => 'Apenas clientes podem avaliar empresas'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'empresa_id' => 'required|exists:empresas,id',
            'estrelas' => 'required|integer|min:1|max:5',
            'comentario' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verificar se está inscrito
        $inscricao = InscricaoEmpresa::where('user_id', $user->id)
            ->where('empresa_id', $request->empresa_id)
            ->first();

        if (!$inscricao) {
            return response()->json([
                'success' => false,
                'message' => 'Você precisa estar inscrito na empresa para avaliá-la'
            ], 403);
        }

        // Verificar se já avaliou
        $avaliacaoExistente = Avaliacao::where('user_id', $user->id)
            ->where('empresa_id', $request->empresa_id)
            ->first();

        if ($avaliacaoExistente) {
            // Atualizar avaliação existente
            $avaliacaoExistente->update([
                'estrelas' => $request->estrelas,
                'comentario' => $request->comentario
            ]);

            // Recalcular média
            $this->atualizarMediaEmpresa($request->empresa_id);

            return response()->json([
                'success' => true,
                'message' => 'Avaliação atualizada com sucesso',
                'data' => $avaliacaoExistente
            ]);
        }

        // Criar nova avaliação
        $avaliacao = Avaliacao::create([
            'user_id' => $user->id,
            'empresa_id' => $request->empresa_id,
            'estrelas' => $request->estrelas,
            'comentario' => $request->comentario
        ]);

        // Atualizar média da empresa
        $this->atualizarMediaEmpresa($request->empresa_id);

        return response()->json([
            'success' => true,
            'message' => 'Avaliação criada com sucesso',
            'data' => $avaliacao
        ], 201);
    }

    /**
     * Listar avaliações de uma empresa
     */
    public function listarPorEmpresa($empresa_id)
    {
        $empresa = Empresa::find($empresa_id);
        
        if (!$empresa) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa não encontrada'
            ], 404);
        }

        $avaliacoes = Avaliacao::with('user:id,name')
            ->where('empresa_id', $empresa_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'empresa' => [
                    'id' => $empresa->id,
                    'nome' => $empresa->nome,
                    'avaliacao_media' => $empresa->avaliacao_media,
                    'total_avaliacoes' => $empresa->total_avaliacoes
                ],
                'avaliacoes' => $avaliacoes
            ]
        ]);
    }

    /**
     * Obter minha avaliação de uma empresa
     */
    public function minhaAvaliacao($empresa_id)
    {
        $user = Auth::user();
        
        if ($user->perfil !== 'cliente') {
            return response()->json([
                'success' => false,
                'message' => 'Apenas clientes podem consultar avaliações'
            ], 403);
        }

        $avaliacao = Avaliacao::where('user_id', $user->id)
            ->where('empresa_id', $empresa_id)
            ->first();

        return response()->json([
            'success' => true,
            'data' => $avaliacao
        ]);
    }

    /**
     * Deletar minha avaliação
     */
    public function destroy($empresa_id)
    {
        $user = Auth::user();
        
        if ($user->perfil !== 'cliente') {
            return response()->json([
                'success' => false,
                'message' => 'Apenas clientes podem deletar avaliações'
            ], 403);
        }

        $avaliacao = Avaliacao::where('user_id', $user->id)
            ->where('empresa_id', $empresa_id)
            ->first();

        if (!$avaliacao) {
            return response()->json([
                'success' => false,
                'message' => 'Avaliação não encontrada'
            ], 404);
        }

        $avaliacao->delete();

        // Recalcular média
        $this->atualizarMediaEmpresa($empresa_id);

        return response()->json([
            'success' => true,
            'message' => 'Avaliação deletada com sucesso'
        ]);
    }

    /**
     * Atualizar média de avaliações da empresa
     */
    private function atualizarMediaEmpresa($empresa_id)
    {
        $empresa = Empresa::find($empresa_id);
        
        if ($empresa) {
            $empresa->atualizarAvaliacaoMedia();
        }
    }

    /**
     * Estatísticas de avaliações da empresa
     */
    public function estatisticas($empresa_id)
    {
        $empresa = Empresa::find($empresa_id);
        
        if (!$empresa) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa não encontrada'
            ], 404);
        }

        // Contar avaliações por estrelas
        $distribuicao = Avaliacao::where('empresa_id', $empresa_id)
            ->select('estrelas', DB::raw('count(*) as total'))
            ->groupBy('estrelas')
            ->orderBy('estrelas', 'desc')
            ->get()
            ->pluck('total', 'estrelas');

        // Preencher estrelas faltantes com zero
        $distribuicaoCompleta = [];
        for ($i = 5; $i >= 1; $i--) {
            $distribuicaoCompleta[$i] = $distribuicao->get($i, 0);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'avaliacao_media' => $empresa->avaliacao_media,
                'total_avaliacoes' => $empresa->total_avaliacoes,
                'distribuicao' => $distribuicaoCompleta
            ]
        ]);
    }
}
