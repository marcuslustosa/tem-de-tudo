<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ClienteAPIController extends Controller
{
    /**
     * Dashboard do cliente com todas as informações
     */
    public function dashboard()
    {
        $user = Auth::user();
        
        // Pontos totais
        $pontosTotais = DB::table('pontos')
            ->where('user_id', $user->id)
            ->where('tipo', 'ganho')
            ->sum('pontos');
        
        $pontosGastos = DB::table('pontos')
            ->where('user_id', $user->id)
            ->where('tipo', 'resgate')
            ->sum('pontos');
        
        $saldoPontos = $pontosTotais - $pontosGastos;
        
        // Empresas favoritas (onde tem mais pontos)
        $empresasFavoritas = DB::table('pontos')
            ->join('empresas', 'pontos.empresa_id', '=', 'empresas.id')
            ->select('empresas.*', DB::raw('SUM(pontos.pontos) as total_pontos'))
            ->where('pontos.user_id', $user->id)
            ->where('pontos.tipo', 'ganho')
            ->groupBy('empresas.id')
            ->orderByDesc('total_pontos')
            ->limit(3)
            ->get();
        
        // Últimas transações
        $ultimasTransacoes = DB::table('pontos')
            ->join('empresas', 'pontos.empresa_id', '=', 'empresas.id')
            ->select('pontos.*', 'empresas.nome as empresa_nome')
            ->where('pontos.user_id', $user->id)
            ->orderByDesc('pontos.created_at')
            ->limit(10)
            ->get();
        
        // Promoções disponíveis
        $promocoes = DB::table('promocoes')
            ->join('empresas', 'promocoes.empresa_id', '=', 'empresas.id')
            ->select('promocoes.*', 'empresas.nome as empresa_nome')
            ->where('promocoes.ativo', true)
            ->where('promocoes.status', 'ativa')
            ->orderByDesc('promocoes.created_at')
            ->limit(6)
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => [
                'usuario' => [
                    'nome' => $user->name,
                    'email' => $user->email,
                    'saldo_pontos' => $saldoPontos,
                    'total_ganho' => $pontosTotais,
                    'total_gasto' => $pontosGastos
                ],
                'empresas_favoritas' => $empresasFavoritas,
                'ultimas_transacoes' => $ultimasTransacoes,
                'promocoes_disponiveis' => $promocoes
            ]
        ]);
    }
    
    /**
     * Listar todas as empresas disponíveis
     */
    public function listarEmpresas(Request $request)
    {
        $query = DB::table('empresas')
            ->where('ativo', true)
            ->select('empresas.*');
        
        // Filtro por ramo
        if ($request->has('ramo')) {
            $query->where('ramo', $request->ramo);
        }
        
        // Busca por nome
        if ($request->has('busca')) {
            $query->where('nome', 'ILIKE', '%' . $request->busca . '%');
        }
        
        $empresas = $query->orderBy('nome')->get();
        
        // Para cada empresa, calcular pontos do usuário
        $user = Auth::user();
        foreach ($empresas as $empresa) {
            $pontos = DB::table('pontos')
                ->where('user_id', $user->id)
                ->where('empresa_id', $empresa->id)
                ->where('tipo', 'ganho')
                ->sum('pontos');
            
            $empresa->meus_pontos = $pontos;
        }
        
        return response()->json([
            'success' => true,
            'data' => $empresas
        ]);
    }
    
    /**
     * Ver detalhes de uma empresa
     */
    public function empresaDetalhes($id)
    {
        $empresa = DB::table('empresas')->where('id', $id)->first();
        
        if (!$empresa) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa não encontrada'
            ], 404);
        }
        
        $user = Auth::user();
        
        // Meus pontos nesta empresa
        $meusPontos = DB::table('pontos')
            ->where('user_id', $user->id)
            ->where('empresa_id', $id)
            ->where('tipo', 'ganho')
            ->sum('pontos');
        
        // Promoções ativas
        $promocoes = DB::table('promocoes')
            ->where('empresa_id', $id)
            ->where('ativo', true)
            ->where('status', 'ativa')
            ->get();
        
        // Avaliações
        $avaliacoes = DB::table('avaliacoes')
            ->join('users', 'avaliacoes.user_id', '=', 'users.id')
            ->select('avaliacoes.*', 'users.name as cliente_nome')
            ->where('avaliacoes.empresa_id', $id)
            ->orderByDesc('avaliacoes.created_at')
            ->limit(10)
            ->get();
        
        // Minha avaliação
        $minhaAvaliacao = DB::table('avaliacoes')
            ->where('empresa_id', $id)
            ->where('user_id', $user->id)
            ->first();
        
        return response()->json([
            'success' => true,
            'data' => [
                'empresa' => $empresa,
                'meus_pontos' => $meusPontos,
                'promocoes' => $promocoes,
                'avaliacoes' => $avaliacoes,
                'minha_avaliacao' => $minhaAvaliacao
            ]
        ]);
    }
    
    /**
     * Escanear QR Code e ganhar pontos
     */
    public function escanearQRCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string'
        ]);
        
        $user = Auth::user();
        
        // Buscar QR Code
        $qrCode = DB::table('qr_codes')
            ->where('code', $request->code)
            ->where('active', true)
            ->first();
        
        if (!$qrCode) {
            return response()->json([
                'success' => false,
                'message' => 'QR Code inválido ou inativo'
            ], 404);
        }
        
        // Buscar empresa
        $empresa = DB::table('empresas')->where('id', $qrCode->empresa_id)->first();
        
        // Verificar limite de uso diário (3 scans por dia por empresa)
        $hoje = now()->format('Y-m-d');
        $scansHoje = DB::table('pontos')
            ->where('user_id', $user->id)
            ->where('empresa_id', $qrCode->empresa_id)
            ->whereDate('created_at', $hoje)
            ->where('descricao', 'LIKE', '%QR Code%')
            ->count();
        
        if ($scansHoje >= 3) {
            return response()->json([
                'success' => false,
                'message' => 'Você já atingiu o limite de 3 scans por dia nesta empresa'
            ], 429);
        }
        
        // Calcular pontos (base 100 * multiplicador da empresa)
        $pontosBase = 100;
        $multiplicador = $empresa->points_multiplier ?? 1;
        $pontosGanhos = $pontosBase * $multiplicador;
        
        // Adicionar pontos
        DB::table('pontos')->insert([
            'user_id' => $user->id,
            'empresa_id' => $qrCode->empresa_id,
            'pontos' => $pontosGanhos,
            'tipo' => 'ganho',
            'descricao' => 'QR Code - ' . $qrCode->name,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        // Atualizar pontos do usuário
        DB::table('users')
            ->where('id', $user->id)
            ->increment('pontos', $pontosGanhos);
        
        // Atualizar contador do QR Code
        DB::table('qr_codes')
            ->where('id', $qrCode->id)
            ->update([
                'usage_count' => DB::raw('usage_count + 1'),
                'last_used_at' => now()
            ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Pontos adicionados com sucesso!',
            'data' => [
                'pontos_ganhos' => $pontosGanhos,
                'empresa' => $empresa->nome,
                'novo_saldo' => $user->pontos + $pontosGanhos
            ]
        ]);
    }
    
    /**
     * Resgatar promoção
     */
    public function resgatarPromocao(Request $request, $promocaoId)
    {
        $user = Auth::user();
        
        // Buscar promoção
        $promocao = DB::table('promocoes')
            ->where('id', $promocaoId)
            ->where('ativo', true)
            ->first();
        
        if (!$promocao) {
            return response()->json([
                'success' => false,
                'message' => 'Promoção não encontrada ou inativa'
            ], 404);
        }
        
        // Verificar se já resgatou hoje
        $hoje = now()->format('Y-m-d');
        $resgatadoHoje = DB::table('pontos')
            ->where('user_id', $user->id)
            ->where('empresa_id', $promocao->empresa_id)
            ->where('tipo', 'resgate')
            ->whereDate('created_at', $hoje)
            ->where('descricao', 'LIKE', '%' . $promocao->titulo . '%')
            ->exists();
        
        if ($resgatadoHoje) {
            return response()->json([
                'success' => false,
                'message' => 'Você já resgatou esta promoção hoje'
            ], 429);
        }
        
        // Pontoscusto (baseado no desconto)
        $pontosCusto = $promocao->desconto * 10; // 10 pontos por % de desconto
        
        // Verificar saldo
        if ($user->pontos < $pontosCusto) {
            return response()->json([
                'success' => false,
                'message' => 'Pontos insuficientes. Você precisa de ' . $pontosCusto . ' pontos.'
            ], 400);
        }
        
        // Descontar pontos
        DB::table('pontos')->insert([
            'user_id' => $user->id,
            'empresa_id' => $promocao->empresa_id,
            'pontos' => $pontosCusto,
            'tipo' => 'resgate',
            'descricao' => 'Resgate: ' . $promocao->titulo,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        // Atualizar saldo do usuário
        DB::table('users')
            ->where('id', $user->id)
            ->decrement('pontos', $pontosCusto);
        
        // Incrementar contador de resgates
        DB::table('promocoes')
            ->where('id', $promocaoId)
            ->increment('resgates');
        
        return response()->json([
            'success' => true,
            'message' => 'Promoção resgatada com sucesso!',
            'data' => [
                'promocao' => $promocao->titulo,
                'pontos_gastos' => $pontosCusto,
                'novo_saldo' => $user->pontos - $pontosCusto,
                'codigo_resgate' => strtoupper(substr(md5($user->id . $promocaoId . now()), 0, 8))
            ]
        ]);
    }
    
    /**
     * Avaliar empresa
     */
    public function avaliar(Request $request)
    {
        $request->validate([
            'empresa_id' => 'required|integer',
            'estrelas' => 'required|integer|min:1|max:5',
            'comentario' => 'nullable|string|max:500'
        ]);
        
        $user = Auth::user();
        
        // Verificar se já avaliou
        $jaAvaliou = DB::table('avaliacoes')
            ->where('user_id', $user->id)
            ->where('empresa_id', $request->empresa_id)
            ->exists();
        
        if ($jaAvaliou) {
            // Atualizar avaliação existente
            DB::table('avaliacoes')
                ->where('user_id', $user->id)
                ->where('empresa_id', $request->empresa_id)
                ->update([
                    'estrelas' => $request->estrelas,
                    'comentario' => $request->comentario,
                    'updated_at' => now()
                ]);
                
            $message = 'Avaliação atualizada com sucesso!';
        } else {
            // Criar nova avaliação
            DB::table('avaliacoes')->insert([
                'user_id' => $user->id,
                'empresa_id' => $request->empresa_id,
                'estrelas' => $request->estrelas,
                'comentario' => $request->comentario,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            $message = 'Avaliação criada com sucesso!';
        }
        
        // Recalcular média da empresa
        $media = DB::table('avaliacoes')
            ->where('empresa_id', $request->empresa_id)
            ->avg('estrelas');
        
        $total = DB::table('avaliacoes')
            ->where('empresa_id', $request->empresa_id)
            ->count();
        
        DB::table('empresas')
            ->where('id', $request->empresa_id)
            ->update([
                'avaliacao_media' => round($media, 1),
                'total_avaliacoes' => $total
            ]);
        
        return response()->json([
            'success' => true,
            'message' => $message
        ]);
    }
    
    /**
     * Histórico de pontos
     */
    public function historicoPontos(Request $request)
    {
        $user = Auth::user();
        
        $query = DB::table('pontos')
            ->join('empresas', 'pontos.empresa_id', '=', 'empresas.id')
            ->select('pontos.*', 'empresas.nome as empresa_nome')
            ->where('pontos.user_id', $user->id);
        
        // Filtro por tipo
        if ($request->has('tipo')) {
            $query->where('pontos.tipo', $request->tipo);
        }
        
        // Filtro por empresa
        if ($request->has('empresa_id')) {
            $query->where('pontos.empresa_id', $request->empresa_id);
        }
        
        $historico = $query
            ->orderByDesc('pontos.created_at')
            ->paginate(20);
        
        return response()->json([
            'success' => true,
            'data' => $historico
        ]);
    }
}
