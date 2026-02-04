<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Empresa;

class EmpresaController extends Controller
{
    public function index()
    {
        try {
            $empresas = Empresa::where('ativo', true)
                ->select('id', 'nome', 'cnpj', 'telefone', 'endereco', 'logo', 'descricao', 'ativo', 'points_multiplier')
                ->orderBy('nome')
                ->get();

            return response()->json($empresas, 200, ['Content-Type' => 'application/json; charset=UTF-8'], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            Log::error('Erro ao listar empresas: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erro ao carregar empresas'], 500);
        }
    }

    /**
     * Listar empresas para cadastro de funcionários (público)
     */
    public function listEmpresas()
    {
        try {
            $empresas = Empresa::where('ativo', true)
                ->select('id', 'nome', 'endereco', 'telefone')
                ->orderBy('nome')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $empresas
            ], 200, ['Content-Type' => 'application/json; charset=UTF-8'], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            Log::error('Erro ao listar empresas: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar empresas'
            ], 500);
        }
    }

    public function show($id)
    {
        $empresa = Empresa::findOrFail($id);
        return response()->json($empresa);
    }

    /**
     * Dashboard stats para empresa
     */
    public function dashboardStats(Request $request)
    {
        try {
            $user = $request->user();

            // Buscar empresa do usuário
            $empresa = Empresa::where('owner_id', $user->id)->first();

            if (!$empresa) {
                return response()->json([
                    'success' => false,
                    'message' => 'Empresa não encontrada'
                ], 404);
            }

            // Estatísticas básicas
            $totalClientes = DB::table('pontos')
                ->where('empresa_id', $empresa->id)
                ->distinct('user_id')
                ->count('user_id');

            $pontosDistribuidos = DB::table('pontos')
                ->where('empresa_id', $empresa->id)
                ->where('tipo', 'earn')
                ->sum('pontos');

            $qrcodesAtivos = DB::table('qrcodes')
                ->where('empresa_id', $empresa->id)
                ->where('ativo', true)
                ->count();

            $checkinsHoje = DB::table('checkins')
                ->where('empresa_id', $empresa->id)
                ->whereDate('created_at', today())
                ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'empresa' => $empresa,
                    'total_clientes' => $totalClientes,
                    'pontos_distribuidos' => $pontosDistribuidos,
                    'qrcodes_ativos' => $qrcodesAtivos,
                    'checkins_hoje' => $checkinsHoje
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao carregar dashboard empresa', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar dados do dashboard'
            ], 500);
        }
    }

    /**
     * Check-ins recentes da empresa
     */
    public function recentCheckins(Request $request)
    {
        try {
            $user = $request->user();
            $empresa = Empresa::where('owner_id', $user->id)->first();

            if (!$empresa) {
                return response()->json([
                    'success' => false,
                    'message' => 'Empresa não encontrada'
                ], 404);
            }

            $checkins = DB::table('checkins')
                ->join('users', 'checkins.user_id', '=', 'users.id')
                ->join('qrcodes', 'checkins.qrcode_id', '=', 'qrcodes.id')
                ->where('checkins.empresa_id', $empresa->id)
                ->select(
                    'checkins.id',
                    'users.name as cliente_nome',
                    'qrcodes.nome as qr_nome',
                    'checkins.pontos',
                    'checkins.created_at'
                )
                ->orderBy('checkins.created_at', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $checkins
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao carregar check-ins recentes', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar check-ins recentes'
            ], 500);
        }
    }

    /**
     * Top clientes da empresa
     */
    public function topClients(Request $request)
    {
        try {
            $user = $request->user();
            $empresa = Empresa::where('owner_id', $user->id)->first();

            if (!$empresa) {
                return response()->json([
                    'success' => false,
                    'message' => 'Empresa não encontrada'
                ], 404);
            }

            $topClients = DB::table('pontos')
                ->join('users', 'pontos.user_id', '=', 'users.id')
                ->where('pontos.empresa_id', $empresa->id)
                ->select(
                    'users.id',
                    'users.name',
                    DB::raw('SUM(pontos.pontos) as total_pontos'),
                    DB::raw('COUNT(pontos.id) as total_checkins')
                )
                ->groupBy('users.id', 'users.name')
                ->orderBy('total_pontos', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($client) {
                    $client->nivel = $this->calcularNivel($client->total_pontos);
                    return $client;
                });

            return response()->json([
                'success' => true,
                'data' => $topClients
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao carregar top clientes', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar top clientes'
            ], 500);
        }
    }

    /**
     * Calcular nível baseado nos pontos
     */
    private function calcularNivel($pontos)
    {
        if ($pontos >= 10000) return 'Diamante';
        if ($pontos >= 5000) return 'Ouro';
        if ($pontos >= 1000) return 'Prata';
        return 'Bronze';
    }
}
