<?php

namespace App\Http\Controllers;

use App\Models\CartaoFidelidade;
use App\Models\CartaoFidelidadeProgresso;
use App\Models\InscricaoEmpresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CartaoFidelidadeController extends Controller
{
    /**
     * Listar cartões de fidelidade da empresa
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

        $cartoes = CartaoFidelidade::where('empresa_id', $empresa->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $cartoes
        ]);
    }

    /**
     * Criar novo cartão de fidelidade
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        if ($user->perfil !== 'empresa') {
            return response()->json([
                'success' => false,
                'message' => 'Apenas empresas podem criar cartões de fidelidade'
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
            'descricao' => 'required|string',
            'meta_pontos' => 'required|integer|min:1',
            'recompensa' => 'required|string|max:255',
            'ativo' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        // ✅ REGRA: Apenas 1 cartão ativo por empresa
        $cartaoAtivoExistente = CartaoFidelidade::where('empresa_id', $empresa->id)
            ->where('ativo', true)
            ->first();
        
        if ($cartaoAtivoExistente && ($request->input('ativo', true) === true)) {
            return response()->json([
                'success' => false,
                'message' => 'Você já possui um cartão de fidelidade ativo. Desative o cartão atual antes de criar um novo.',
                'cartao_ativo' => $cartaoAtivoExistente
            ], 422);
        }

        $data = $validator->validated();
        $data['empresa_id'] = $empresa->id;

        $cartao = CartaoFidelidade::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Cartão de fidelidade criado com sucesso',
            'data' => $cartao
        ], 201);
    }

    /**
     * Obter detalhes de um cartão
     */
    public function show($id)
    {
        $cartao = CartaoFidelidade::with('empresa')->find($id);
        
        if (!$cartao) {
            return response()->json([
                'success' => false,
                'message' => 'Cartão não encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $cartao
        ]);
    }

    /**
     * Atualizar cartão de fidelidade
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        
        if ($user->perfil !== 'empresa') {
            return response()->json([
                'success' => false,
                'message' => 'Apenas empresas podem atualizar cartões'
            ], 403);
        }

        $empresa = $user->empresa;
        $cartao = CartaoFidelidade::find($id);
        
        if (!$cartao) {
            return response()->json([
                'success' => false,
                'message' => 'Cartão não encontrado'
            ], 404);
        }

        if ($cartao->empresa_id !== $empresa->id) {
            return response()->json([
                'success' => false,
                'message' => 'Você não tem permissão para atualizar este cartão'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'titulo' => 'sometimes|string|max:255',
            'descricao' => 'sometimes|string',
            'meta_pontos' => 'sometimes|integer|min:1',
            'recompensa' => 'sometimes|string|max:255',
            'ativo' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        // ✅ REGRA: Apenas 1 cartão ativo por empresa
        if ($request->has('ativo') && $request->input('ativo') === true) {
            $cartaoAtivoExistente = CartaoFidelidade::where('empresa_id', $empresa->id)
                ->where('ativo', true)
                ->where('id', '!=', $id)
                ->first();
            
            if ($cartaoAtivoExistente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você já possui outro cartão de fidelidade ativo. Desative o cartão atual antes de ativar este.',
                    'cartao_ativo' => $cartaoAtivoExistente
                ], 422);
            }
        }

        $cartao->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Cartão atualizado com sucesso',
            'data' => $cartao
        ]);
    }

    /**
     * Deletar cartão de fidelidade
     */
    public function destroy($id)
    {
        $user = Auth::user();
        
        if ($user->perfil !== 'empresa') {
            return response()->json([
                'success' => false,
                'message' => 'Apenas empresas podem deletar cartões'
            ], 403);
        }

        $empresa = $user->empresa;
        $cartao = CartaoFidelidade::find($id);
        
        if (!$cartao) {
            return response()->json([
                'success' => false,
                'message' => 'Cartão não encontrado'
            ], 404);
        }

        if ($cartao->empresa_id !== $empresa->id) {
            return response()->json([
                'success' => false,
                'message' => 'Você não tem permissão para deletar este cartão'
            ], 403);
        }

        $cartao->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cartão deletado com sucesso'
        ]);
    }

    /**
     * Adicionar ponto ao cartão do cliente
     */
    public function adicionarPonto(Request $request)
    {
        $user = Auth::user();
        
        if ($user->perfil !== 'empresa') {
            return response()->json([
                'success' => false,
                'message' => 'Apenas empresas podem adicionar pontos'
            ], 403);
        }

        $empresa = $user->empresa;

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'cartao_fidelidade_id' => 'required|exists:cartoes_fidelidade,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verificar se o cartão pertence à empresa
        $cartao = CartaoFidelidade::find($request->cartao_fidelidade_id);
        if ($cartao->empresa_id !== $empresa->id) {
            return response()->json([
                'success' => false,
                'message' => 'Este cartão não pertence à sua empresa'
            ], 403);
        }

        // Verificar se o cliente está inscrito
        $inscricao = InscricaoEmpresa::where('user_id', $request->user_id)
            ->where('empresa_id', $empresa->id)
            ->first();

        if (!$inscricao) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente não está inscrito na empresa'
            ], 404);
        }

        // Buscar ou criar progresso
        $progresso = CartaoFidelidadeProgresso::firstOrCreate(
            [
                'user_id' => $request->user_id,
                'cartao_fidelidade_id' => $request->cartao_fidelidade_id
            ],
            [
                'pontos_atuais' => 0,
                'vezes_resgatado' => 0
            ]
        );

        // Adicionar ponto
        $progresso->pontos_atuais += 1;
        $progresso->ultimo_ponto = now();

        // Verificar se completou o cartão
        if ($progresso->pontos_atuais >= $cartao->meta_pontos) {
            $progresso->vezes_resgatado += 1;
            $progresso->pontos_atuais = 0; // Reset
            
            $mensagem = 'Parabéns! Cliente completou o cartão de fidelidade!';
            $completou = true;
        } else {
            $mensagem = 'Ponto adicionado com sucesso!';
            $completou = false;
        }

        $progresso->save();

        // Atualizar ultima_visita
        $inscricao->update(['ultima_visita' => now()]);

        return response()->json([
            'success' => true,
            'message' => $mensagem,
            'data' => [
                'progresso' => $progresso,
                'cartao' => $cartao,
                'completou' => $completou,
                'pontos_atuais' => $progresso->pontos_atuais,
                'meta_pontos' => $cartao->meta_pontos,
                'vezes_resgatado' => $progresso->vezes_resgatado
            ]
        ]);
    }

    /**
     * Listar progresso do cliente em todos os cartões
     */
    public function meuProgresso()
    {
        $user = Auth::user();
        
        if ($user->perfil !== 'cliente') {
            return response()->json([
                'success' => false,
                'message' => 'Apenas clientes podem ver progresso'
            ], 403);
        }

        $progressos = CartaoFidelidadeProgresso::with(['cartaoFidelidade.empresa'])
            ->where('user_id', $user->id)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $progressos
        ]);
    }

    /**
     * Ver progresso do cliente em uma empresa específica
     */
    public function progressoPorEmpresa($empresa_id)
    {
        $user = Auth::user();
        
        if ($user->perfil !== 'cliente') {
            return response()->json([
                'success' => false,
                'message' => 'Apenas clientes podem ver progresso'
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

        // Buscar cartões ativos da empresa
        $cartoes = CartaoFidelidade::where('empresa_id', $empresa_id)
            ->where('ativo', true)
            ->get();

        // Buscar progresso do cliente
        $progressos = CartaoFidelidadeProgresso::where('user_id', $user->id)
            ->whereIn('cartao_fidelidade_id', $cartoes->pluck('id'))
            ->get()
            ->keyBy('cartao_fidelidade_id');

        // Combinar cartões com progresso
        $resultado = $cartoes->map(function ($cartao) use ($progressos) {
            $progresso = $progressos->get($cartao->id);
            
            return [
                'cartao' => $cartao,
                'progresso' => $progresso ? [
                    'pontos_atuais' => $progresso->pontos_atuais,
                    'vezes_resgatado' => $progresso->vezes_resgatado,
                    'ultimo_ponto' => $progresso->ultimo_ponto,
                    'percentual' => round(($progresso->pontos_atuais / $cartao->meta_pontos) * 100, 2)
                ] : [
                    'pontos_atuais' => 0,
                    'vezes_resgatado' => 0,
                    'ultimo_ponto' => null,
                    'percentual' => 0
                ]
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $resultado
        ]);
    }
}
