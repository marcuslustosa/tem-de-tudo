<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class EmpresaAPIController extends Controller
{
    /**
     * Dashboard da empresa com estatísticas
     */
    public function dashboard()
    {
        $user = Auth::user();
        
        // Buscar empresa do usuário
        $empresa = DB::table('empresas')->where('owner_id', $user->id)->first();
        
        if (!$empresa) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa não encontrada'
            ], 404);
        }
        
        // Total de clientes
        $totalClientes = DB::table('pontos')
            ->where('empresa_id', $empresa->id)
            ->distinct('user_id')
            ->count('user_id');
        
        // Pontos distribuídos hoje
        $pontosHoje = DB::table('pontos')
            ->where('empresa_id', $empresa->id)
            ->where('tipo', 'ganho')
            ->whereDate('created_at', today())
            ->sum('pontos');
        
        // Pontos distribuídos este mês
        $pontosMes = DB::table('pontos')
            ->where('empresa_id', $empresa->id)
            ->where('tipo', 'ganho')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('pontos');
        
        // Scans de QR Code hoje
        $scansHoje = DB::table('pontos')
            ->where('empresa_id', $empresa->id)
            ->whereDate('created_at', today())
            ->where('descricao', 'LIKE', '%QR Code%')
            ->count();
        
        // Promoções ativas
        $promocoesAtivas = DB::table('promocoes')
            ->where('empresa_id', $empresa->id)
            ->where('ativo', true)
            ->count();
        
        // Top 5 clientes
        $topClientes = DB::table('pontos')
            ->join('users', 'pontos.user_id', '=', 'users.id')
            ->select('users.name', 'users.email', DB::raw('SUM(pontos.pontos) as total_pontos'))
            ->where('pontos.empresa_id', $empresa->id)
            ->where('pontos.tipo', 'ganho')
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('total_pontos')
            ->limit(5)
            ->get();
        
        // Últimas transações
        $ultimasTransacoes = DB::table('pontos')
            ->join('users', 'pontos.user_id', '=', 'users.id')
            ->select('pontos.*', 'users.name as cliente_nome')
            ->where('pontos.empresa_id', $empresa->id)
            ->orderByDesc('pontos.created_at')
            ->limit(10)
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => [
                'empresa' => $empresa,
                'estatisticas' => [
                    'total_clientes' => $totalClientes,
                    'pontos_hoje' => $pontosHoje,
                    'pontos_mes' => $pontosMes,
                    'scans_hoje' => $scansHoje,
                    'promocoes_ativas' => $promocoesAtivas
                ],
                'top_clientes' => $topClientes,
                'ultimas_transacoes' => $ultimasTransacoes
            ]
        ]);
    }
    
    /**
     * Listar clientes da empresa
     */
    public function clientes(Request $request)
    {
        $user = Auth::user();
        $empresa = DB::table('empresas')->where('owner_id', $user->id)->first();
        
        if (!$empresa) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa não encontrada'
            ], 404);
        }
        
        // Buscar todos os clientes que interagiram com a empresa
        $clientes = DB::table('pontos')
            ->join('users', 'pontos.user_id', '=', 'users.id')
            ->select(
                'users.id',
                'users.name',
                'users.email',
                'users.telefone',
                DB::raw('SUM(CASE WHEN pontos.tipo = \'ganho\' THEN pontos.pontos ELSE 0 END) as total_ganho'),
                DB::raw('SUM(CASE WHEN pontos.tipo = \'resgate\' THEN pontos.pontos ELSE 0 END) as total_gasto'),
                DB::raw('MAX(pontos.created_at) as ultima_visita')
            )
            ->where('pontos.empresa_id', $empresa->id)
            ->groupBy('users.id', 'users.name', 'users.email', 'users.telefone')
            ->orderByDesc('total_ganho')
            ->paginate(20);
        
        return response()->json([
            'success' => true,
            'data' => $clientes
        ]);
    }
    
    /**
     * Listar promoções da empresa
     */
    public function promocoes()
    {
        $user = Auth::user();
        $empresa = DB::table('empresas')->where('owner_id', $user->id)->first();
        
        if (!$empresa) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa não encontrada'
            ], 404);
        }
        
        $promocoes = DB::table('promocoes')
            ->where('empresa_id', $empresa->id)
            ->orderByDesc('created_at')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $promocoes
        ]);
    }
    
    /**
     * Criar promoção
     */
    public function criarPromocao(Request $request)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'descricao' => 'required|string',
            'desconto' => 'required|numeric|min:0|max:100',
            'imagem' => 'nullable|string'
        ]);
        
        $user = Auth::user();
        $empresa = DB::table('empresas')->where('owner_id', $user->id)->first();
        
        if (!$empresa) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa não encontrada'
            ], 404);
        }
        
        $promocaoId = DB::table('promocoes')->insertGetId([
            'empresa_id' => $empresa->id,
            'titulo' => $request->titulo,
            'descricao' => $request->descricao,
            'desconto' => $request->desconto,
            'imagem' => $request->imagem ?? 'promocao_default.jpg',
            'data_inicio' => now(),
            'ativo' => true,
            'status' => 'ativa',
            'visualizacoes' => 0,
            'resgates' => 0,
            'usos' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        $promocao = DB::table('promocoes')->where('id', $promocaoId)->first();
        
        return response()->json([
            'success' => true,
            'message' => 'Promoção criada com sucesso!',
            'data' => $promocao
        ], 201);
    }
    
    /**
     * Atualizar promoção
     */
    public function atualizarPromocao(Request $request, $id)
    {
        $request->validate([
            'titulo' => 'sometimes|string|max:255',
            'descricao' => 'sometimes|string',
            'desconto' => 'sometimes|numeric|min:0|max:100',
            'ativo' => 'sometimes|boolean'
        ]);
        
        $user = Auth::user();
        $empresa = DB::table('empresas')->where('owner_id', $user->id)->first();
        
        if (!$empresa) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa não encontrada'
            ], 404);
        }
        
        // Verificar se a promoção pertence à empresa
        $promocao = DB::table('promocoes')
            ->where('id', $id)
            ->where('empresa_id', $empresa->id)
            ->first();
        
        if (!$promocao) {
            return response()->json([
                'success' => false,
                'message' => 'Promoção não encontrada'
            ], 404);
        }
        
        $updateData = $request->only(['titulo', 'descricao', 'desconto', 'ativo']);
        $updateData['updated_at'] = now();
        
        DB::table('promocoes')
            ->where('id', $id)
            ->update($updateData);
        
        $promocaoAtualizada = DB::table('promocoes')->where('id', $id)->first();
        
        return response()->json([
            'success' => true,
            'message' => 'Promoção atualizada com sucesso!',
            'data' => $promocaoAtualizada
        ]);
    }
    
    /**
     * Deletar promoção
     */
    public function deletarPromocao($id)
    {
        $user = Auth::user();
        $empresa = DB::table('empresas')->where('owner_id', $user->id)->first();
        
        if (!$empresa) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa não encontrada'
            ], 404);
        }
        
        // Verificar se a promoção pertence à empresa
        $promocao = DB::table('promocoes')
            ->where('id', $id)
            ->where('empresa_id', $empresa->id)
            ->first();
        
        if (!$promocao) {
            return response()->json([
                'success' => false,
                'message' => 'Promoção não encontrada'
            ], 404);
        }
        
        DB::table('promocoes')->where('id', $id)->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Promoção deletada com sucesso!'
        ]);
    }
    
    /**
     * QR Codes da empresa
     */
    public function qrCodes()
    {
        $user = Auth::user();
        $empresa = DB::table('empresas')->where('owner_id', $user->id)->first();
        
        if (!$empresa) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa não encontrada'
            ], 404);
        }
        
        $qrCodes = DB::table('qr_codes')
            ->where('empresa_id', $empresa->id)
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $qrCodes
        ]);
    }
    
    /**
     * Avaliações da empresa
     */
    public function avaliacoes()
    {
        $user = Auth::user();
        $empresa = DB::table('empresas')->where('owner_id', $user->id)->first();
        
        if (!$empresa) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa não encontrada'
            ], 404);
        }
        
        $avaliacoes = DB::table('avaliacoes')
            ->join('users', 'avaliacoes.user_id', '=', 'users.id')
            ->select('avaliacoes.*', 'users.name as cliente_nome')
            ->where('avaliacoes.empresa_id', $empresa->id)
            ->orderByDesc('avaliacoes.created_at')
            ->get();
        
        $mediaAvaliacoes = DB::table('avaliacoes')
            ->where('empresa_id', $empresa->id)
            ->avg('estrelas');
        
        $distribuicao = DB::table('avaliacoes')
            ->select('estrelas', DB::raw('COUNT(*) as quantidade'))
            ->where('empresa_id', $empresa->id)
            ->groupBy('estrelas')
            ->orderBy('estrelas', 'desc')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => [
                'avaliacoes' => $avaliacoes,
                'media' => round($mediaAvaliacoes, 1),
                'total' => $avaliacoes->count(),
                'distribuicao' => $distribuicao
            ]
        ]);
    }
    
    /**
     * Relatório de pontos distribuídos
     */
    public function relatorioPontos(Request $request)
    {
        $user = Auth::user();
        $empresa = DB::table('empresas')->where('owner_id', $user->id)->first();
        
        if (!$empresa) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa não encontrada'
            ], 404);
        }
        
        // Período (padrão: últimos 30 dias)
        $dataInicio = $request->input('data_inicio', now()->subDays(30)->format('Y-m-d'));
        $dataFim = $request->input('data_fim', now()->format('Y-m-d'));
        
        // Pontos por dia
        $pontosPorDia = DB::table('pontos')
            ->select(
                DB::raw('DATE(created_at) as data'),
                DB::raw('SUM(CASE WHEN tipo = \'ganho\' THEN pontos ELSE 0 END) as pontos_distribuidos'),
                DB::raw('SUM(CASE WHEN tipo = \'resgate\' THEN pontos ELSE 0 END) as pontos_resgatados'),
                DB::raw('COUNT(DISTINCT user_id) as clientes_unicos')
            )
            ->where('empresa_id', $empresa->id)
            ->whereBetween('created_at', [$dataInicio, $dataFim])
            ->groupBy('data')
            ->orderBy('data')
            ->get();
        
        // Totais do período
        $totais = DB::table('pontos')
            ->select(
                DB::raw('SUM(CASE WHEN tipo = \'ganho\' THEN pontos ELSE 0 END) as total_distribuido'),
                DB::raw('SUM(CASE WHEN tipo = \'resgate\' THEN pontos ELSE 0 END) as total_resgatado'),
                DB::raw('COUNT(DISTINCT user_id) as total_clientes')
            )
            ->where('empresa_id', $empresa->id)
            ->whereBetween('created_at', [$dataInicio, $dataFim])
            ->first();
        
        return response()->json([
            'success' => true,
            'data' => [
                'periodo' => [
                    'inicio' => $dataInicio,
                    'fim' => $dataFim
                ],
                'totais' => $totais,
                'por_dia' => $pontosPorDia
            ]
        ]);
    }
}
