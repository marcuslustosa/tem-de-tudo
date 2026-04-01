<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Models\Empresa;

class EmpresaController extends Controller
{
    private function hasEmpresasTable(): bool
    {
        return Schema::hasTable('empresas');
    }

    private function hasColumn(string $table, string $column): bool
    {
        return Schema::hasTable($table) && Schema::hasColumn($table, $column);
    }

    private function isBooleanColumn(string $table, string $column): bool
    {
        if (!$this->hasColumn($table, $column)) {
            return false;
        }

        try {
            $type = strtolower((string) Schema::getColumnType($table, $column));
            return in_array($type, ['bool', 'boolean'], true);
        } catch (\Throwable) {
            return false;
        }
    }

    private function resolveUserPerfilColumn(): ?string
    {
        foreach (['perfil', 'role', 'tipo'] as $column) {
            if ($this->hasColumn('users', $column)) {
                return $column;
            }
        }

        return null;
    }

    private function applyEmpresaAtivoScope($query)
    {
        if (!$this->hasEmpresasTable()) {
            return $query;
        }

        if ($this->hasColumn('empresas', 'ativo')) {
            if ($this->isBooleanColumn('empresas', 'ativo')) {
                return $query->where('ativo', true);
            }

            return $query->whereIn('ativo', [1, '1', true, 'true', 'ativo']);
        }

        if ($this->hasColumn('empresas', 'status')) {
            return $query->whereIn(DB::raw('LOWER(status)'), ['ativo', 'active', '1', 'true']);
        }

        return $query;
    }

    private function demoEmpresasFromUsers(): array
    {
        if (!Schema::hasTable('users')) {
            return [
                [
                    'id' => 1,
                    'nome' => 'Parceiro Demo',
                    'descricao' => 'Conta empresarial ativa na plataforma.',
                    'categoria' => 'geral',
                    'ramo' => 'geral',
                    'endereco' => 'Endereco nao informado',
                    'telefone' => '-',
                    'email' => '-',
                    'logo' => '/assets/images/company1.jpg',
                    'points_multiplier' => 1,
                ],
            ];
        }
        $perfilCol = $this->resolveUserPerfilColumn();
        $nameCol = $this->hasColumn('users', 'name') ? 'name' : ($this->hasColumn('users', 'nome') ? 'nome' : null);
        $emailCol = $this->hasColumn('users', 'email') ? 'email' : null;
        $phoneCol = $this->hasColumn('users', 'telefone')
            ? 'telefone'
            : ($this->hasColumn('users', 'phone') ? 'phone' : ($this->hasColumn('users', 'celular') ? 'celular' : null));

        $query = DB::table('users');
        if ($perfilCol) {
            $query->whereIn(DB::raw("LOWER({$perfilCol})"), ['empresa', 'estabelecimento', 'parceiro', 'lojista']);
        }

        $select = ['id'];
        $select[] = $nameCol ? DB::raw("{$nameCol} as nome_usuario") : DB::raw("'Estabelecimento' as nome_usuario");
        $select[] = $emailCol ? DB::raw("{$emailCol} as email_usuario") : DB::raw("'-' as email_usuario");
        $select[] = $phoneCol ? DB::raw("{$phoneCol} as telefone_usuario") : DB::raw("'-' as telefone_usuario");
        $query->select($select)->orderBy('id')->limit(20);

        $empresaUsers = $query->get();

        $defaults = [
            '/assets/images/company1.jpg',
            '/assets/images/company2.jpg',
            '/assets/images/company3.jpg',
            '/assets/images/company4.jpg',
        ];

        return collect($empresaUsers)->values()->map(function ($u, $idx) use ($defaults) {
            return [
                'id' => $u->id,
                'nome' => $this->cleanUtf8($u->nome_usuario ?? 'Estabelecimento'),
                'descricao' => 'Conta empresarial ativa na plataforma.',
                'categoria' => 'geral',
                'ramo' => 'geral',
                'endereco' => 'Endereco nao informado',
                'telefone' => $this->cleanUtf8($u->telefone_usuario ?? '-'),
                'email' => $this->cleanUtf8($u->email_usuario ?? '-'),
                'logo' => $defaults[$idx % count($defaults)],
                'points_multiplier' => 1,
            ];
        })->toArray();
    }

    private function cleanUtf8($value)
    {
        if (!is_string($value)) {
            return $value;
        }
        $clean = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
        return $clean === false ? '' : $clean;
    }

    public function index()
    {
        try {
            if (!$this->hasEmpresasTable() || !$this->hasColumn('empresas', 'nome')) {
                return response()->json($this->demoEmpresasFromUsers(), 200, ['Content-Type' => 'application/json; charset=UTF-8']);
            }

            $query = Empresa::query();
            $this->applyEmpresaAtivoScope($query);
            $select = ['id', 'nome'];
            foreach (['cnpj', 'telefone', 'endereco', 'logo', 'descricao', 'ativo', 'points_multiplier'] as $column) {
                if (Schema::hasColumn('empresas', $column)) {
                    $select[] = $column;
                }
            }

            $empresas = $query
                ->select($select)
                ->orderBy('nome')
                ->get();

            return response()->json($empresas, 200, ['Content-Type' => 'application/json; charset=UTF-8'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        } catch (\Exception $e) {
            Log::error('Erro ao listar empresas: ' . $e->getMessage());
            return response()->json($this->demoEmpresasFromUsers(), 200, ['Content-Type' => 'application/json; charset=UTF-8']);
        }
    }

    /**
     * Listar empresas para cadastro de funcionários (público) + busca
     */
    public function listEmpresas(Request $request)
    {
        try {
            if (!$this->hasEmpresasTable() || !$this->hasColumn('empresas', 'nome')) {
                return response()->json([
                    'success' => true,
                    'data' => $this->demoEmpresasFromUsers(),
                ]);
            }

            $query = Empresa::query();
            $this->applyEmpresaAtivoScope($query);
            $hasCategoria = Schema::hasColumn('empresas', 'categoria');
            $hasRamo = Schema::hasColumn('empresas', 'ramo');
            $hasDescricao = Schema::hasColumn('empresas', 'descricao');
            $hasLogo = Schema::hasColumn('empresas', 'logo');
            $hasTelefone = Schema::hasColumn('empresas', 'telefone');
            $hasEndereco = Schema::hasColumn('empresas', 'endereco');
            $hasMultiplier = Schema::hasColumn('empresas', 'points_multiplier');
            
            // Filtro por categoria
            if ($request->has('categoria') && $request->categoria !== 'todos') {
                if ($hasCategoria) {
                    $query->where('categoria', $request->categoria);
                } elseif ($hasRamo) {
                    $query->where('ramo', $request->categoria);
                }
            }

            // Busca por nome ou descrição
            if ($request->has('busca')) {
                $busca = $request->busca;
                $query->where(function($q) use ($busca) {
                    $q->where('nome', 'LIKE', "%{$busca}%");
                    if (Schema::hasColumn('empresas', 'descricao')) {
                        $q->orWhere('descricao', 'LIKE', "%{$busca}%");
                    }
                });
            }

            $select = ['id', 'nome'];
            if ($hasEndereco) {
                $select[] = 'endereco';
            }
            if ($hasTelefone) {
                $select[] = 'telefone';
            }
            if ($hasDescricao) {
                $select[] = 'descricao';
            }
            if ($hasLogo) {
                $select[] = 'logo';
            }
            if ($hasMultiplier) {
                $select[] = 'points_multiplier';
            }

            $empresas = $query
                ->select($select)
                ->when($hasCategoria, fn ($q) => $q->addSelect('categoria'))
                ->when($hasRamo, fn ($q) => $q->addSelect('ramo'))
                ->orderBy('nome')
                ->get();

            $mapped = $empresas->map(function($empresa) {
                return [
                    'id' => $empresa->id,
                    'nome' => $this->cleanUtf8($empresa->nome),
                    'descricao' => $this->cleanUtf8($empresa->descricao),
                    'categoria' => $this->cleanUtf8($empresa->categoria ?? $empresa->ramo ?? null),
                    'ramo' => $this->cleanUtf8($empresa->ramo ?? $empresa->categoria ?? null),
                    'endereco' => $this->cleanUtf8($empresa->endereco ?? null),
                    'telefone' => $this->cleanUtf8($empresa->telefone ?? null),
                    'logo' => $this->cleanUtf8($empresa->logo ?? '/assets/images/company1.jpg'),
                    'points_multiplier' => $empresa->points_multiplier ?? 1,
                ];
            })->values();

            if ($mapped->isEmpty()) {
                $mapped = collect($this->demoEmpresasFromUsers());
            }

            return response()->json([
                'success' => true,
                'data' => $mapped
            ], 200, ['Content-Type' => 'application/json; charset=UTF-8'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        } catch (\Exception $e) {
            Log::error('Erro ao listar empresas: ' . $e->getMessage());

            return response()->json([
                'success' => true,
                'data' => $this->demoEmpresasFromUsers(),
                'warning' => 'Fallback aplicado por erro ao carregar empresas.',
            ], 200);
        }
    }

    public function getEmpresa($id)
    {
        try {
            if (!$this->hasEmpresasTable() || !$this->hasColumn('empresas', 'nome')) {
                $demo = collect($this->demoEmpresasFromUsers())->firstWhere('id', (int) $id);
                if (!$demo) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Estabelecimento nao encontrado.',
                    ], 404);
                }
                return response()->json([
                    'success' => true,
                    'data' => $demo,
                ]);
            }

            $empresa = Empresa::query()->find($id);
            if (!$empresa) {
                return response()->json([
                    'success' => false,
                    'message' => 'Estabelecimento nao encontrado.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $empresa->id,
                    'nome' => $this->cleanUtf8($empresa->nome),
                    'descricao' => $this->cleanUtf8($empresa->descricao ?? ''),
                    'categoria' => $this->cleanUtf8($empresa->categoria ?? $empresa->ramo ?? ''),
                    'ramo' => $this->cleanUtf8($empresa->ramo ?? $empresa->categoria ?? ''),
                    'endereco' => $this->cleanUtf8($empresa->endereco ?? ''),
                    'telefone' => $this->cleanUtf8($empresa->telefone ?? ''),
                    'email' => $this->cleanUtf8($empresa->email ?? ''),
                    'logo' => $this->cleanUtf8($empresa->logo ?? '/assets/images/company1.jpg'),
                    'points_multiplier' => $empresa->points_multiplier ?? 1,
                ],
            ], 200, ['Content-Type' => 'application/json; charset=UTF-8'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        } catch (\Throwable $e) {
            Log::error('Erro ao carregar empresa por id', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar estabelecimento.',
            ], 500);
        }
    }

    public function show($id)
    {
        if (!$this->hasEmpresasTable()) {
            return response()->json([
                'success' => false,
                'message' => 'Tabela de empresas indisponivel neste ambiente.',
            ], 503);
        }
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

            // Buscar todas empresas ativas (compatível com esquemas diferentes)
            $query = DB::table('users')->where('perfil', 'empresa');
            if (Schema::hasColumn('users', 'status')) {
                $query->where('status', 'ativo');
            } elseif (Schema::hasColumn('users', 'ativo')) {
                $query->where('ativo', true);
            }

            $select = ['id', 'name as nome'];
            $select[] = Schema::hasColumn('users', 'razao_social') ? 'razao_social' : DB::raw('NULL as razao_social');
            $select[] = Schema::hasColumn('users', 'categoria') ? 'categoria' : DB::raw('NULL as categoria');
            $select[] = Schema::hasColumn('users', 'endereco') ? 'endereco' : DB::raw('NULL as endereco');
            $select[] = Schema::hasColumn('users', 'telefone') ? 'telefone' : DB::raw('NULL as telefone');
            $select[] = Schema::hasColumn('users', 'latitude') ? 'latitude' : DB::raw('NULL as latitude');
            $select[] = Schema::hasColumn('users', 'longitude') ? 'longitude' : DB::raw('NULL as longitude');
            $query->select($select);

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
        if ($pontos >= 5000) return 'Platina';
        if ($pontos >= 1500) return 'Ouro';
        if ($pontos >= 500) return 'Prata';
        return 'Bronze';
    }
}
