<?php

namespace App\Http\Controllers;

use App\Models\Promocao;
use App\Models\InscricaoEmpresa;
use App\Models\NotificacaoPush;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PromocaoController extends Controller
{
    /**
     * Listar promoções da empresa
     */
    public function index()
    {
        $user = Auth::user();
        
        if ($user->perfil !== 'empresa') {
            return response()->json([
                'success' => false,
                'message' => 'Apenas empresas podem acessar promoções'
            ], 403);
        }

        $empresa = $user->empresa;
        if (!$empresa) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa não encontrada'
            ], 404);
        }

        $promocoes = Promocao::where('empresa_id', $empresa->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $promocoes
        ]);
    }

    /**
     * Criar nova promoção
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        if ($user->perfil !== 'empresa') {
            return response()->json([
                'success' => false,
                'message' => 'Apenas empresas podem criar promoções'
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
            'titulo' => 'required|string|max:100',
            'descricao' => 'required|string|max:500',
            'imagem' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
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

        // Upload obrigatório da imagem
        if ($request->hasFile('imagem')) {
            $path = $request->file('imagem')->store('promocoes', 'public');
            $data['imagem'] = $path;
        }

        $promocao = Promocao::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Promoção criada com sucesso',
            'data' => $promocao
        ], 201);
    }

    /**
     * Obter detalhes de uma promoção
     */
    public function show($id)
    {
        $promocao = Promocao::with('empresa')->find($id);
        
        if (!$promocao) {
            return response()->json([
                'success' => false,
                'message' => 'Promoção não encontrada'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $promocao
        ]);
    }

    /**
     * Atualizar promoção
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        
        if ($user->perfil !== 'empresa') {
            return response()->json([
                'success' => false,
                'message' => 'Apenas empresas podem atualizar promoções'
            ], 403);
        }

        $empresa = $user->empresa;
        $promocao = Promocao::find($id);
        
        if (!$promocao) {
            return response()->json([
                'success' => false,
                'message' => 'Promoção não encontrada'
            ], 404);
        }

        if ($promocao->empresa_id !== $empresa->id) {
            return response()->json([
                'success' => false,
                'message' => 'Você não tem permissão para atualizar esta promoção'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'titulo' => 'sometimes|string|max:100',
            'descricao' => 'sometimes|string|max:500',
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
            if ($promocao->imagem) {
                Storage::disk('public')->delete($promocao->imagem);
            }
            $path = $request->file('imagem')->store('promocoes', 'public');
            $data['imagem'] = $path;
        }

        $promocao->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Promoção atualizada com sucesso',
            'data' => $promocao
        ]);
    }

    /**
     * Deletar promoção
     */
    public function destroy($id)
    {
        $user = Auth::user();
        
        if ($user->perfil !== 'empresa') {
            return response()->json([
                'success' => false,
                'message' => 'Apenas empresas podem deletar promoções'
            ], 403);
        }

        $empresa = $user->empresa;
        $promocao = Promocao::find($id);
        
        if (!$promocao) {
            return response()->json([
                'success' => false,
                'message' => 'Promoção não encontrada'
            ], 404);
        }

        if ($promocao->empresa_id !== $empresa->id) {
            return response()->json([
                'success' => false,
                'message' => 'Você não tem permissão para deletar esta promoção'
            ], 403);
        }

        // Deletar imagem
        if ($promocao->imagem) {
            Storage::disk('public')->delete($promocao->imagem);
        }

        $promocao->delete();

        return response()->json([
            'success' => true,
            'message' => 'Promoção deletada com sucesso'
        ]);
    }

    /**
     * Enviar push notification de promoção para todos os clientes inscritos
     */
    public function enviarPush($id)
    {
        $user = Auth::user();
        
        if ($user->perfil !== 'empresa') {
            return response()->json([
                'success' => false,
                'message' => 'Apenas empresas podem enviar promoções'
            ], 403);
        }

        $empresa = $user->empresa;
        $promocao = Promocao::find($id);
        
        if (!$promocao) {
            return response()->json([
                'success' => false,
                'message' => 'Promoção não encontrada'
            ], 404);
        }

        if ($promocao->empresa_id !== $empresa->id) {
            return response()->json([
                'success' => false,
                'message' => 'Você não tem permissão para enviar esta promoção'
            ], 403);
        }

        // Buscar todos os clientes inscritos
        $inscricoes = InscricaoEmpresa::where('empresa_id', $empresa->id)->get();

        if ($inscricoes->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhum cliente inscrito para receber a promoção'
            ], 400);
        }

        $enviados = 0;

        foreach ($inscricoes as $inscricao) {
            // Criar notificação push
            NotificacaoPush::create([
                'user_id' => $inscricao->user_id,
                'empresa_id' => $empresa->id,
                'tipo' => 'promocao',
                'titulo' => $promocao->titulo,
                'mensagem' => $promocao->descricao,
                'imagem' => $promocao->imagem,
                'enviado' => false, // Será processado por job/cron
                'data_envio' => null
            ]);

            $enviados++;
        }

        // Atualizar promoção
        $promocao->update([
            'data_envio' => now(),
            'total_envios' => $promocao->total_envios + $enviados
        ]);

        // TODO: Aqui deve disparar o job de envio de push notifications via Firebase
        // Para agora, apenas criamos os registros na tabela

        return response()->json([
            'success' => true,
            'message' => "Promoção enviada para {$enviados} cliente(s)!",
            'data' => [
                'promocao' => $promocao,
                'total_envios' => $enviados
            ]
        ]);
    }

    /**
     * Listar promoções de uma empresa (para clientes)
     */
    public function listarPorEmpresa($empresa_id)
    {
        $user = Auth::user();
        
        if ($user->perfil !== 'cliente') {
            return response()->json([
                'success' => false,
                'message' => 'Apenas clientes podem listar promoções'
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

        // Buscar promoções ativas
        $promocoes = Promocao::where('empresa_id', $empresa_id)
            ->where('ativo', true)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $promocoes
        ]);
    }
}
