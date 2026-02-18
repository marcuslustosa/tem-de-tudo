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
     * Listar empresas para cadastro de funcionários (público) + busca
     */
    public function listEmpresas(Request $request)
    {
        try {
            $query = Empresa::where('ativo', true);
            
            // Filtro por categoria
            if ($request->has('categoria') && $request->categoria !== 'todos') {
                $query->where('categoria', $request->categoria);
            }

            // Busca por nome ou descrição
            if ($request->has('busca')) {
                $busca = $request->busca;
                $query->where(function($q) use ($busca) {
                    $q->where('nome', 'LIKE', "%{$busca}%")
                      ->orWhere('descricao', 'LIKE', "%{$busca}%");
                });
            }
            
            $empresas = $query
                ->select('id', 'nome', 'endereco', 'telefone', 'categoria', 'descricao', 'logo', 'points_multiplier')
                ->orderBy('nome')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $empresas->map(function($empresa) {
                    return [
                        'id' => $empresa->id,
                        'nome' => $empresa->nome,
                        'descricao' => $empresa->descricao,
                        'categoria' => $empresa->categoria,
                        'endereco' => $empresa->endereco,
                        'telefone' => $empresa->telefone,
                        'logo' => $empresa->logo,
                        'points_multiplier' => $empresa->points_multiplier ?? 1,
                    ];
                })
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
     * Listar empresas próximas ao cliente (com geolocalização)
     */
    public function empresasProximas(Request $request)
    {
        try {
            $latitude = $request->input('latitude');
            $longitude = $request->input('longitude');
            $raio = $request->input('raio', 10); // Raio em km, padrão 10km

            // Buscar todas empresas ativas
            $query = DB::table('users')
                ->where('perfil', 'empresa')
                ->where('ativo', true)
                ->select('id', 'name as nome', 'razao_social', 'categoria', 'endereco', 'telefone', 'latitude', 'longitude');

            // Se temos lat/lon, calcular distância
            if ($latitude && $longitude) {
                $empresas = $query->get()->map(function ($empresa) use ($latitude, $longitude) {
                    if ($empresa->latitude && $empresa->longitude) {
                        $empresa->distancia = $this->calcularDistancia(
                            $latitude,
                            $longitude,
                            $empresa->latitude,
                            $empresa->longitude
                        );
                    } else {
                        $empresa->distancia = null;
                    }
                    return $empresa;
                })->filter(function ($empresa) use ($raio) {
                    return $empresa->distancia === null || $empresa->distancia <= $raio;
                })->sortBy('distancia')->values();
            } else {
                $empresas = $query->get();
            }

            return response()->json([
                'success' => true,
                'data' => $empresas
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar empresas próximas', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar empresas'
            ], 500);
        }
    }

    /**
     * Atualizar localização da empresa
     */
    public function atualizarLocalizacao(Request $request)
    {
        try {
            $user = $request->user();

            $validated = $request->validate([
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180'
            ]);

            $user->update([
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Localização atualizada com sucesso!'
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar localização', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar localização'
            ], 500);
        }
    }

    /**
     * Calcular distância entre dois pontos (fórmula de Haversine)
     */
    private function calcularDistancia($lat1, $lon1, $lat2, $lon2)
    {
        $R = 6371; // Raio da Terra em km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distance = $R * $c;
        
        return round($distance, 2);
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
