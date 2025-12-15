<?php

namespace App\Http\Controllers;

use App\Models\BonusAdesao;
use App\Models\Empresa;
use App\Models\InscricaoEmpresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BonusAdesaoController extends Controller
{
    /**
     * Listar bônus de adesão da empresa autenticada
     */
    public function index()
    {
        $user = Auth::user();
        
        if ($user->perfil !== 'empresa') {
            return response()->json([
                'success' => false,
                'message' => 'Apenas empresas podem acessar esta funcionalidade'
            ], 403);
        }

        $empresa = $user->empresa;
        if (!$empresa) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa não encontrada'
            ], 404);
        }

        $bonus = BonusAdesao::where('empresa_id', $empresa->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $bonus
        ]);
    }

    /**
     * Criar novo bônus de adesão
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        if ($user->perfil !== 'empresa') {
            return response()->json([
                'success' => false,
                'message' => 'Apenas empresas podem criar bônus'
            ], 403);
        }

        $empresa = $user->empresa;
        if (!$empresa) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa não encontrada'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'titulo' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'tipo_desconto' => 'required|in:porcentagem,valor_fixo',
            'valor_desconto' => 'required|numeric|min:0',
            'imagem' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'ativo' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $data['empresa_id'] = $empresa->id;

        // Upload da imagem
        if ($request->hasFile('imagem')) {
            $path = $request->file('imagem')->store('bonus_adesao', 'public');
            $data['imagem'] = $path;
        }

        $bonus = BonusAdesao::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Bônus de adesão criado com sucesso',
            'data' => $bonus
        ], 201);
    }

    /**
     * Obter detalhes de um bônus específico
     */
    public function show($id)
    {
        $user = Auth::user();
        
        $bonus = BonusAdesao::find($id);
        if (!$bonus) {
            return response()->json([
                'success' => false,
                'message' => 'Bônus não encontrado'
            ], 404);
        }

        // Verificar permissão
        if ($user->perfil === 'empresa') {
            $empresa = $user->empresa;
            if (!$empresa || $bonus->empresa_id !== $empresa->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para visualizar este bônus'
                ], 403);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $bonus
        ]);
    }

    /**
     * Atualizar bônus de adesão
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        
        if ($user->perfil !== 'empresa') {
            return response()->json([
                'success' => false,
                'message' => 'Apenas empresas podem atualizar bônus'
            ], 403);
        }

        $empresa = $user->empresa;
        if (!$empresa) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa não encontrada'
            ], 404);
        }

        $bonus = BonusAdesao::find($id);
        if (!$bonus) {
            return response()->json([
                'success' => false,
                'message' => 'Bônus não encontrado'
            ], 404);
        }

        if ($bonus->empresa_id !== $empresa->id) {
            return response()->json([
                'success' => false,
                'message' => 'Você não tem permissão para atualizar este bônus'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'titulo' => 'sometimes|string|max:255',
            'descricao' => 'nullable|string',
            'tipo_desconto' => 'sometimes|in:porcentagem,valor_fixo',
            'valor_desconto' => 'sometimes|numeric|min:0',
            'imagem' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'ativo' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        // Upload de nova imagem
        if ($request->hasFile('imagem')) {
            // Deletar imagem antiga
            if ($bonus->imagem) {
                Storage::disk('public')->delete($bonus->imagem);
            }
            $path = $request->file('imagem')->store('bonus_adesao', 'public');
            $data['imagem'] = $path;
        }

        $bonus->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Bônus atualizado com sucesso',
            'data' => $bonus
        ]);
    }

    /**
     * Deletar bônus de adesão
     */
    public function destroy($id)
    {
        $user = Auth::user();
        
        if ($user->perfil !== 'empresa') {
            return response()->json([
                'success' => false,
                'message' => 'Apenas empresas podem deletar bônus'
            ], 403);
        }

        $empresa = $user->empresa;
        if (!$empresa) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa não encontrada'
            ], 404);
        }

        $bonus = BonusAdesao::find($id);
        if (!$bonus) {
            return response()->json([
                'success' => false,
                'message' => 'Bônus não encontrado'
            ], 404);
        }

        if ($bonus->empresa_id !== $empresa->id) {
            return response()->json([
                'success' => false,
                'message' => 'Você não tem permissão para deletar este bônus'
            ], 403);
        }

        // Deletar imagem
        if ($bonus->imagem) {
            Storage::disk('public')->delete($bonus->imagem);
        }

        $bonus->delete();

        return response()->json([
            'success' => true,
            'message' => 'Bônus deletado com sucesso'
        ]);
    }

    /**
     * Obter bônus disponível para o cliente na empresa
     */
    public function bonusDisponivel($empresa_id)
    {
        $user = Auth::user();
        
        if ($user->perfil !== 'cliente') {
            return response()->json([
                'success' => false,
                'message' => 'Apenas clientes podem consultar bônus'
            ], 403);
        }

        // Verificar se está inscrito
        $inscricao = InscricaoEmpresa::where('user_id', $user->id)
            ->where('empresa_id', $empresa_id)
            ->first();

        if (!$inscricao) {
            return response()->json([
                'success' => false,
                'message' => 'Você não está inscrito nesta empresa'
            ], 404);
        }

        // Verificar se já resgatou o bônus
        if ($inscricao->bonus_adesao_resgatado) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'Bônus já foi resgatado'
            ]);
        }

        // Buscar bônus ativo da empresa
        $bonus = BonusAdesao::where('empresa_id', $empresa_id)
            ->where('ativo', true)
            ->first();

        return response()->json([
            'success' => true,
            'data' => $bonus
        ]);
    }

    /**
     * Resgatar bônus de adesão
     */
    public function resgatar($empresa_id)
    {
        $user = Auth::user();
        
        if ($user->perfil !== 'cliente') {
            return response()->json([
                'success' => false,
                'message' => 'Apenas clientes podem resgatar bônus'
            ], 403);
        }

        // Verificar inscrição
        $inscricao = InscricaoEmpresa::where('user_id', $user->id)
            ->where('empresa_id', $empresa_id)
            ->first();

        if (!$inscricao) {
            return response()->json([
                'success' => false,
                'message' => 'Você não está inscrito nesta empresa'
            ], 404);
        }

        // Verificar se já resgatou
        if ($inscricao->bonus_adesao_resgatado) {
            return response()->json([
                'success' => false,
                'message' => 'Você já resgatou o bônus de adesão desta empresa'
            ], 400);
        }

        // Buscar bônus ativo
        $bonus = BonusAdesao::where('empresa_id', $empresa_id)
            ->where('ativo', true)
            ->first();

        if (!$bonus) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhum bônus de adesão disponível'
            ], 404);
        }

        // Marcar como resgatado
        $inscricao->update(['bonus_adesao_resgatado' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Bônus resgatado com sucesso!',
            'data' => [
                'bonus' => $bonus,
                'inscricao' => $inscricao
            ]
        ]);
    }
}
