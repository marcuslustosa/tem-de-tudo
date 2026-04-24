<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Jobs\SendWebPushJob;
use App\Models\Empresa;
use App\Models\Notification;
use App\Services\LoyaltyProgramService;
use App\Services\ClienteQrCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class EmpresaAPIController extends Controller
{
    /**
     * Perfil completo da empresa (dados do usuÃ¡rio + dados da empresa)
     */
    public function meuPerfil()
    {
        $user = Auth::user();
        $empresa = DB::table('empresas')->where('owner_id', $user->id)->first();

        if (!$empresa) {
            return response()->json(['success' => false, 'message' => 'Empresa nÃ£o encontrada'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'telefone' => $user->telefone,
                    'perfil' => $user->perfil,
                    'status' => $user->status,
                ],
                'empresa' => [
                    'id'        => $empresa->id,
                    'nome'      => $empresa->nome,
                    'ramo'      => $empresa->ramo ?? $empresa->categoria ?? '',
                    'cnpj'      => $empresa->cnpj ?? '',
                    'endereco'  => $empresa->endereco ?? '',
                    'telefone'  => $empresa->telefone ?? $user->telefone ?? '',
                    'logo'      => $empresa->logo ?? '',
                    'descricao' => $empresa->descricao ?? '',
                    'points_multiplier' => $empresa->points_multiplier ?? 1.0,
                    'ativo'     => $empresa->ativo ?? true,
                ],
            ],
        ]);
    }

    /**
     * Atualiza perfil do usuÃ¡rio + dados da empresa
     */
    public function atualizarPerfil(Request $request)
    {
        $user = Auth::user();
        $empresa = DB::table('empresas')->where('owner_id', $user->id)->first();

        if (!$empresa) {
            return response()->json(['success' => false, 'message' => 'Empresa nÃ£o encontrada'], 404);
        }

        $validated = $request->validate([
            'name'     => 'sometimes|string|max:255',
            'email'    => 'sometimes|email|unique:users,email,' . $user->id,
            'telefone' => 'sometimes|string|max:20',
            // empresa fields
            'empresa_nome'     => 'sometimes|string|max:255',
            'empresa_ramo'     => 'sometimes|string|max:100',
            'empresa_cnpj'     => 'sometimes|string|max:18',
            'empresa_endereco' => 'sometimes|string|max:500',
            'empresa_logo'     => 'sometimes|nullable|url|max:500',
        ]);

        // Atualizar users
        $userFields = array_filter([
            'name'     => $validated['name'] ?? null,
            'email'    => $validated['email'] ?? null,
            'telefone' => $validated['telefone'] ?? null,
        ]);
        if ($userFields) {
            DB::table('users')->where('id', $user->id)->update($userFields);
        }

        // Atualizar empresas
        $empresaFields = array_filter([
            'nome'      => $validated['empresa_nome'] ?? null,
            'ramo'      => $validated['empresa_ramo'] ?? null,
            'cnpj'      => $validated['empresa_cnpj'] ?? null,
            'endereco'  => $validated['empresa_endereco'] ?? null,
            'logo'      => $validated['empresa_logo'] ?? null,
            'updated_at' => now(),
        ], fn($v) => $v !== null);
        if ($empresaFields) {
            DB::table('empresas')->where('id', $empresa->id)->update($empresaFields);
        }

        return response()->json([
            'success' => true,
            'message' => 'Perfil atualizado com sucesso!',
        ]);
    }

    /**
     * Dashboard da empresa com estatÃ­sticas
     */
    public function dashboard()
    {
        $user = Auth::user();
        $empresa = $this->resolveEmpresaForUser((int) $user->id);

        if (!$empresa) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa nao encontrada'
            ], 404);
        }

        $totalClientes = 0;
        $pontosHoje = 0;
        $pontosMes = 0;
        $scansHoje = 0;
        $promocoesAtivas = 0;
        $topClientes = collect();
        $ultimasTransacoes = collect();

        if ($this->hasTable('pontos') && $this->hasColumn('pontos', 'empresa_id')) {
            try {
                $totalClientes = (int) DB::table('pontos')
                    ->where('empresa_id', $empresa->id)
                    ->distinct('user_id')
                    ->count('user_id');
            } catch (\Throwable $e) {
                Log::warning('Falha ao calcular total de clientes no dashboard da empresa', [
                    'empresa_id' => $empresa->id,
                    'error' => $e->getMessage(),
                ]);
            }

            try {
                $query = DB::table('pontos')->where('empresa_id', $empresa->id);
                if ($this->hasColumn('pontos', 'tipo')) {
                    $query->whereNotIn('tipo', ['resgate', 'redeem']);
                }
                if ($this->hasColumn('pontos', 'created_at')) {
                    $query->whereDate('created_at', today());
                }
                if ($this->hasColumn('pontos', 'pontos')) {
                    $pontosHoje = (int) $query->sum('pontos');
                }
            } catch (\Throwable $e) {
                Log::warning('Falha ao calcular pontos de hoje no dashboard da empresa', [
                    'empresa_id' => $empresa->id,
                    'error' => $e->getMessage(),
                ]);
            }

            try {
                $query = DB::table('pontos')->where('empresa_id', $empresa->id);
                if ($this->hasColumn('pontos', 'tipo')) {
                    $query->whereNotIn('tipo', ['resgate', 'redeem']);
                }
                if ($this->hasColumn('pontos', 'created_at')) {
                    $query->whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year);
                }
                if ($this->hasColumn('pontos', 'pontos')) {
                    $pontosMes = (int) $query->sum('pontos');
                }
            } catch (\Throwable $e) {
                Log::warning('Falha ao calcular pontos do mes no dashboard da empresa', [
                    'empresa_id' => $empresa->id,
                    'error' => $e->getMessage(),
                ]);
            }

            try {
                $query = DB::table('pontos')->where('empresa_id', $empresa->id);
                if ($this->hasColumn('pontos', 'created_at')) {
                    $query->whereDate('created_at', today());
                }
                if ($this->hasColumn('pontos', 'descricao')) {
                    $query->where('descricao', 'LIKE', '%QR Code%');
                }
                $scansHoje = (int) $query->count();
            } catch (\Throwable $e) {
                Log::warning('Falha ao calcular scans de hoje no dashboard da empresa', [
                    'empresa_id' => $empresa->id,
                    'error' => $e->getMessage(),
                ]);
            }

            if ($this->hasTable('users')) {
                try {
                    $topClientes = DB::table('pontos')
                        ->join('users', 'pontos.user_id', '=', 'users.id')
                        ->select('users.name', 'users.email', DB::raw('SUM(pontos.pontos) as total_pontos'))
                        ->where('pontos.empresa_id', $empresa->id)
                        ->when($this->hasColumn('pontos', 'tipo'), function ($q) {
                            $q->whereNotIn('pontos.tipo', ['resgate', 'redeem']);
                        })
                        ->groupBy('users.id', 'users.name', 'users.email')
                        ->orderByDesc('total_pontos')
                        ->limit(5)
                        ->get();
                } catch (\Throwable $e) {
                    Log::warning('Falha ao carregar top clientes no dashboard da empresa', [
                        'empresa_id' => $empresa->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                try {
                    $ultimasTransacoes = DB::table('pontos')
                        ->join('users', 'pontos.user_id', '=', 'users.id')
                        ->select('pontos.*', 'users.name as cliente_nome')
                        ->where('pontos.empresa_id', $empresa->id)
                        ->when($this->hasColumn('pontos', 'created_at'), function ($q) {
                            $q->orderByDesc('pontos.created_at');
                        })
                        ->limit(10)
                        ->get();
                } catch (\Throwable $e) {
                    Log::warning('Falha ao carregar ultimas transacoes no dashboard da empresa', [
                        'empresa_id' => $empresa->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        if ($this->hasTable('promocoes') && $this->hasColumn('promocoes', 'empresa_id')) {
            try {
                $query = DB::table('promocoes')->where('empresa_id', $empresa->id);
                if ($this->hasColumn('promocoes', 'ativo')) {
                    $query->where('ativo', true);
                }
                $promocoesAtivas = (int) $query->count();
            } catch (\Throwable $e) {
                Log::warning('Falha ao calcular promocoes ativas no dashboard da empresa', [
                    'empresa_id' => $empresa->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

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
        $empresa = $this->resolveEmpresaForUser((int) $user->id);

        if (!$empresa) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa nao encontrada'
            ], 404);
        }

        if (!$this->hasTable('pontos') || !$this->hasTable('users')) {
            return response()->json([
                'success' => true,
                'data' => [
                    'data' => [],
                    'total' => 0,
                    'current_page' => 1,
                    'per_page' => 20,
                    'last_page' => 1,
                ],
            ]);
        }

        try {
            $clientes = DB::table('pontos')
                ->join('users', 'pontos.user_id', '=', 'users.id')
                ->select(
                    'users.id',
                    'users.name',
                    'users.email',
                    'users.telefone',
                    DB::raw("SUM(CASE WHEN pontos.tipo NOT IN ('resgate', 'redeem') THEN pontos.pontos ELSE 0 END) as total_ganho"),
                    DB::raw("SUM(CASE WHEN pontos.tipo IN ('resgate', 'redeem') THEN pontos.pontos ELSE 0 END) as total_gasto"),
                    DB::raw('MAX(pontos.created_at) as ultima_visita')
                )
                ->where('pontos.empresa_id', $empresa->id)
                ->when($request->filled('busca'), function ($q) use ($request) {
                    $term = '%' . strtolower($request->busca) . '%';
                    $q->where(function ($sub) use ($term) {
                        $sub->whereRaw('LOWER(users.name) LIKE ?', [$term])
                            ->orWhereRaw('LOWER(users.email) LIKE ?', [$term]);
                    });
                })
                ->groupBy('users.id', 'users.name', 'users.email', 'users.telefone')
                ->orderByDesc('total_ganho')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $clientes->items(),
                    'total' => $clientes->total(),
                    'current_page' => $clientes->currentPage(),
                    'per_page' => $clientes->perPage(),
                    'last_page' => $clientes->lastPage()
                ]
            ]);
        } catch (\Throwable $e) {
            Log::warning('Falha ao listar clientes da empresa', [
                'empresa_id' => $empresa->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => [],
                    'total' => 0,
                    'current_page' => 1,
                    'per_page' => 20,
                    'last_page' => 1,
                ],
            ]);
        }
    }

    /**
     * Listar promoÃ§Ãµes da empresa
     */
    public function promocoes()
    {
        $user = Auth::user();
        $empresa = $this->resolveEmpresaForUser((int) $user->id);

        if (!$empresa) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa nao encontrada'
            ], 404);
        }

        if (!$this->hasTable('promocoes')) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        try {
            $query = DB::table('promocoes');
            if ($this->hasColumn('promocoes', 'empresa_id')) {
                $query->where('empresa_id', $empresa->id);
            }
            if ($this->hasColumn('promocoes', 'created_at')) {
                $query->orderByDesc('created_at');
            }

            $promocoes = $query->get();

            return response()->json([
                'success' => true,
                'data' => $promocoes
            ]);
        } catch (\Throwable $e) {
            Log::warning('Falha ao listar promocoes da empresa', [
                'empresa_id' => $empresa->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }
    }

    /**
     * Criar promoÃ§Ã£o
     */
    public function criarPromocao(Request $request)
    {
        $request->merge([
            'titulo' => $request->input('titulo', $request->input('nome')),
            'desconto' => $request->input('desconto', $request->input('preco', 0)),
        ]);

        $request->validate([
            'titulo' => 'required|string|max:255',
            'descricao' => 'required|string',
            'desconto' => 'required|numeric|min:0|max:100',
            'ativo' => 'boolean',
            'data_inicio' => 'nullable|date',
            'validade' => 'nullable|date|after_or_equal:data_inicio',
            'imagem' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'imagem_url' => 'nullable|string|max:2048'
        ]);
        
        $user = Auth::user();
        $empresa = DB::table('empresas')->where('owner_id', $user->id)->first();
        
        if (!$empresa) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa nÃ£o encontrada'
            ], 404);
        }
        
        $imagePath = 'promocao_default.jpg';
        if ($request->hasFile('imagem')) {
            $imagePath = '/storage/' . $request->file('imagem')->store('promocoes', 'public');
        } elseif ($request->filled('imagem_url')) {
            $imagePath = $request->imagem_url;
        }

        $promocaoId = DB::table('promocoes')->insertGetId([
            'empresa_id' => $empresa->id,
            'titulo' => $request->titulo,
            'descricao' => $request->descricao,
            'desconto' => $request->desconto,
            'imagem' => $imagePath,
            'data_inicio' => $request->input('data_inicio', now()),
            'validade' => $request->input('validade'),
            'ativo' => $request->boolean('ativo', true),
            'status' => $request->boolean('ativo', true) ? 'ativa' : 'pausada',
            'visualizacoes' => 0,
            'resgates' => 0,
            'usos' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        $promocao = DB::table('promocoes')->where('id', $promocaoId)->first();
        
        return response()->json([
            'success' => true,
            'message' => 'PromoÃ§Ã£o criada com sucesso!',
            'data' => $promocao
        ], 201);
    }
    
    /**
     * Atualizar promoÃ§Ã£o
     */
    public function atualizarPromocao(Request $request, $id)
    {
        $payload = [];
        if ($request->filled('nome') && !$request->filled('titulo')) {
            $payload['titulo'] = $request->input('nome');
        }
        if ($request->has('preco') && !$request->has('desconto')) {
            $payload['desconto'] = $request->input('preco');
        }
        if (!empty($payload)) {
            $request->merge($payload);
        }

        $request->validate([
            'titulo' => 'sometimes|string|max:255',
            'descricao' => 'sometimes|string',
            'desconto' => 'sometimes|numeric|min:0|max:100',
            'ativo' => 'sometimes|boolean',
            'data_inicio' => 'nullable|date',
            'validade' => 'nullable|date|after_or_equal:data_inicio',
            'imagem' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'imagem_url' => 'nullable|string|max:2048'
        ]);
        
        $user = Auth::user();
        $empresa = DB::table('empresas')->where('owner_id', $user->id)->first();
        
        if (!$empresa) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa nÃ£o encontrada'
            ], 404);
        }
        
        // Verificar se a promoÃ§Ã£o pertence Ã  empresa
        $promocao = DB::table('promocoes')
            ->where('id', $id)
            ->where('empresa_id', $empresa->id)
            ->first();
        
        if (!$promocao) {
            return response()->json([
                'success' => false,
                'message' => 'PromoÃ§Ã£o nÃ£o encontrada'
            ], 404);
        }
        
        $updateData = $request->only(['titulo', 'descricao', 'desconto', 'ativo', 'data_inicio', 'validade']);
        if ($request->hasFile('imagem')) {
            $path = '/storage/' . $request->file('imagem')->store('promocoes', 'public');
            $updateData['imagem'] = $path;
        } elseif ($request->filled('imagem_url')) {
            $updateData['imagem'] = $request->imagem_url;
        }
        $updateData['updated_at'] = now();
        
        DB::table('promocoes')
            ->where('id', $id)
            ->update($updateData);
        
        $promocaoAtualizada = DB::table('promocoes')->where('id', $id)->first();
        
        return response()->json([
            'success' => true,
            'message' => 'PromoÃ§Ã£o atualizada com sucesso!',
            'data' => $promocaoAtualizada
        ]);
    }

    /**
     * Pausar promoÃ§Ã£o
     */
    public function pausarPromocao($id)
    {
        $user = Auth::user();
        $empresa = DB::table('empresas')->where('owner_id', $user->id)->first();
        if (!$empresa) {
            return response()->json(['success' => false, 'message' => 'Empresa nÃ£o encontrada'], 404);
        }

        $promocao = DB::table('promocoes')
            ->where('id', $id)
            ->where('empresa_id', $empresa->id)
            ->first();

        if (!$promocao) {
            return response()->json(['success' => false, 'message' => 'PromoÃ§Ã£o nÃ£o encontrada'], 404);
        }

        DB::table('promocoes')
            ->where('id', $id)
            ->update([
                'ativo' => false,
                'status' => 'pausada',
                'updated_at' => now()
            ]);

        return response()->json(['success' => true, 'message' => 'PromoÃ§Ã£o pausada.']);
    }

    /**
     * Ativar promoÃ§Ã£o
     */
    public function ativarPromocao($id)
    {
        $user = Auth::user();
        $empresa = DB::table('empresas')->where('owner_id', $user->id)->first();
        if (!$empresa) {
            return response()->json(['success' => false, 'message' => 'Empresa nÃ£o encontrada'], 404);
        }

        $promocao = DB::table('promocoes')
            ->where('id', $id)
            ->where('empresa_id', $empresa->id)
            ->first();

        if (!$promocao) {
            return response()->json(['success' => false, 'message' => 'PromoÃ§Ã£o nÃ£o encontrada'], 404);
        }

        DB::table('promocoes')
            ->where('id', $id)
            ->update([
                'ativo' => true,
                'status' => 'ativa',
                'updated_at' => now()
            ]);

        return response()->json(['success' => true, 'message' => 'PromoÃ§Ã£o ativada.']);
    }
    
    /**
     * Deletar promoÃ§Ã£o
     */
    public function deletarPromocao($id)
    {
        $user = Auth::user();
        $empresa = DB::table('empresas')->where('owner_id', $user->id)->first();
        
        if (!$empresa) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa nÃ£o encontrada'
            ], 404);
        }
        
        // Verificar se a promoÃ§Ã£o pertence Ã  empresa
        $promocao = DB::table('promocoes')
            ->where('id', $id)
            ->where('empresa_id', $empresa->id)
            ->first();
        
        if (!$promocao) {
            return response()->json([
                'success' => false,
                'message' => 'PromoÃ§Ã£o nÃ£o encontrada'
            ], 404);
        }
        
        DB::table('promocoes')->where('id', $id)->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'PromoÃ§Ã£o deletada com sucesso!'
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
                'message' => 'Empresa nÃ£o encontrada'
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
     * AvaliaÃ§Ãµes da empresa
     */
    public function avaliacoes()
    {
        $user = Auth::user();
        $empresa = DB::table('empresas')->where('owner_id', $user->id)->first();
        
        if (!$empresa) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa nÃ£o encontrada'
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
     * RelatÃ³rio de pontos distribuÃ­dos
     */
    public function relatorioPontos(Request $request)
    {
        $user = Auth::user();
        $empresa = $this->resolveEmpresaForUser((int) $user->id);

        if (!$empresa) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa nao encontrada'
            ], 404);
        }

        $dataInicio = $request->input('data_inicio', now()->subDays(30)->format('Y-m-d'));
        $dataFim = $request->input('data_fim', now()->format('Y-m-d'));

        if (!$this->hasTable('pontos') || !$this->hasColumn('pontos', 'empresa_id')) {
            return response()->json([
                'success' => true,
                'data' => [
                    'periodo' => [
                        'inicio' => $dataInicio,
                        'fim' => $dataFim
                    ],
                    'totais' => [
                        'total_distribuido' => 0,
                        'total_resgatado' => 0,
                        'total_clientes' => 0,
                    ],
                    'por_dia' => [],
                ]
            ]);
        }

        try {
            $pontosPorDia = DB::table('pontos')
                ->select(
                    DB::raw('DATE(created_at) as data'),
                    DB::raw("SUM(CASE WHEN tipo NOT IN ('resgate', 'redeem') THEN pontos ELSE 0 END) as pontos_distribuidos"),
                    DB::raw("SUM(CASE WHEN tipo IN ('resgate', 'redeem') THEN pontos ELSE 0 END) as pontos_resgatados"),
                    DB::raw('COUNT(DISTINCT user_id) as clientes_unicos')
                )
                ->where('empresa_id', $empresa->id)
                ->whereBetween('created_at', [$dataInicio, $dataFim])
                ->groupBy('data')
                ->orderBy('data')
                ->get();

            $totais = DB::table('pontos')
                ->select(
                    DB::raw("SUM(CASE WHEN tipo NOT IN ('resgate', 'redeem') THEN pontos ELSE 0 END) as total_distribuido"),
                    DB::raw("SUM(CASE WHEN tipo IN ('resgate', 'redeem') THEN pontos ELSE 0 END) as total_resgatado"),
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
        } catch (\Throwable $e) {
            Log::warning('Falha ao gerar relatorio de pontos da empresa', [
                'empresa_id' => $empresa->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'periodo' => [
                        'inicio' => $dataInicio,
                        'fim' => $dataFim
                    ],
                    'totais' => [
                        'total_distribuido' => 0,
                        'total_resgatado' => 0,
                        'total_clientes' => 0,
                    ],
                    'por_dia' => [],
                ]
            ]);
        }
    }

    /**
     * Escanear QR Code do cliente e dar pontos
     */
    public function escanearCliente(Request $request)
    {
        $request->validate([
            'qrcode' => 'required|string'
        ]);
        
        $user = Auth::user();
        $empresa = DB::table('empresas')->where('owner_id', $user->id)->first();
        
        if (!$empresa) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa nÃ£o encontrada'
            ], 404);
        }
        
        // Extrair ID do cliente do QR Code
        // Formato: CLIENT_{id}_{hash}
        $decodedQr = app(ClienteQrCodeService::class)->decodificar($request->qrcode);
        if (!$decodedQr) {
            return response()->json([
                'success' => false,
                'message' => 'QR Code invÃ¡lido'
            ], 400);
        }
        
        $clienteId = (int) $decodedQr['user_id'];
        
        // Verificar se cliente existe
        $cliente = DB::table('users')
            ->where('id', $clienteId)
            ->where('perfil', 'cliente')
            ->first();
        
        if (!$cliente) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente nÃ£o encontrado'
            ], 404);
        }
        
        // Verificar limite de scans (3 por dia)
        $scansHoje = DB::table('pontos')
            ->where('user_id', $clienteId)
            ->where('empresa_id', $empresa->id)
            ->whereDate('created_at', today())
            ->where('tipo', 'ganho')
            ->where('descricao', 'LIKE', '%Check-in%')
            ->count();
        
        if ($scansHoje >= 3) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente jÃ¡ fez 3 check-ins hoje nesta empresa. Limite diÃ¡rio atingido.'
            ], 429);
        }
        
        /** @var LoyaltyProgramService $loyalty */
        $loyalty = app(LoyaltyProgramService::class);
        $empresaModel = Empresa::query()->find($empresa->id);
        $pontosGanhos = $loyalty->calculateScanPoints($empresaModel);
        
        DB::beginTransaction();
        try {
            // Inserir transaÃ§Ã£o de pontos
            DB::table('pontos')->insert([
                'user_id' => $clienteId,
                'empresa_id' => $empresa->id,
                'pontos' => $pontosGanhos,
                'tipo' => 'ganho',
                'descricao' => 'Check-in via QR Code - ' . $empresa->nome,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            // Atualizar saldo do cliente
            DB::table('users')
                ->where('id', $clienteId)
                ->increment('pontos', $pontosGanhos);
            
            DB::commit();
            
            // Buscar saldo atualizado
            $novoSaldo = DB::table('users')
                ->where('id', $clienteId)
                ->value('pontos');

            try {
                Notification::create([
                    'user_id' => $clienteId,
                    'title' => '+' . $pontosGanhos . ' pontos recebidos',
                    'message' => "Check-in confirmado na loja {$empresa->nome}. Saldo atual: {$novoSaldo} pts.",
                    'type' => 'transacao',
                    'payload' => [
                        'kind' => 'checkin',
                        'empresa_id' => (int) $empresa->id,
                        'empresa_nome' => $empresa->nome,
                        'pontos' => (int) $pontosGanhos,
                        'saldo' => (int) $novoSaldo,
                    ],
                ]);

                Notification::create([
                    'user_id' => $user->id,
                    'title' => 'Check-in validado',
                    'message' => "{$cliente->name} recebeu {$pontosGanhos} pontos.",
                    'type' => 'transacao_empresa',
                    'payload' => [
                        'kind' => 'checkin_cliente',
                        'cliente_id' => (int) $cliente->id,
                        'cliente_nome' => $cliente->name,
                        'empresa_id' => (int) $empresa->id,
                        'empresa_nome' => $empresa->nome,
                        'pontos' => (int) $pontosGanhos,
                    ],
                ]);

                SendWebPushJob::dispatch(
                    title: '+' . $pontosGanhos . ' pontos!',
                    body: "Check-in confirmado em {$empresa->nome}. Saldo: {$novoSaldo} pts.",
                    data: [
                        'type' => 'checkin',
                        'empresa' => $empresa->nome,
                        'url' => '/meus_pontos.html',
                    ],
                    userIds: [$clienteId]
                );

                SendWebPushJob::dispatch(
                    title: 'Novo check-in validado',
                    body: "{$cliente->name} recebeu {$pontosGanhos} pontos.",
                    data: [
                        'type' => 'checkin_cliente',
                        'cliente' => $cliente->name,
                        'empresa' => $empresa->nome,
                        'url' => '/dashboard_parceiro.html',
                    ],
                    userIds: [$user->id]
                );
            } catch (\Throwable $notifyError) {
                \Log::warning('Falha ao disparar notificacoes do check-in da empresa', [
                    'empresa_id' => $empresa->id,
                    'cliente_id' => $clienteId,
                    'error' => $notifyError->getMessage(),
                ]);
            }
            return response()->json([
                'success' => true,
                'message' => 'Check-in registrado com sucesso!',
                'data' => [
                    'cliente' => [
                        'id' => $cliente->id,
                        'nome' => $cliente->name,
                        'email' => $cliente->email
                    ],
                    'pontos_ganhos' => $pontosGanhos,
                    'saldo_atual' => $novoSaldo,
                    'scans_hoje' => $scansHoje + 1,
                    'scans_restantes' => 2 - $scansHoje
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar check-in',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Historico detalhado de resgates da empresa.
     */
    public function resgates(Request $request)
    {
        $user = Auth::user();
        $empresa = DB::table('empresas')->where('owner_id', $user->id)->first();
        if (!$empresa) {
            return response()->json(['success' => false, 'message' => 'Empresa nao encontrada'], 404);
        }

        $status = $request->input('status');
        $dataInicio = $request->input('data_inicio');
        $dataFim = $request->input('data_fim');

        if (Schema::hasTable('coupons')) {
            $query = DB::table('coupons as c')
                ->leftJoin('users as u', 'u.id', '=', 'c.user_id')
                ->select(
                    'c.id',
                    'c.codigo',
                    'c.status',
                    'c.created_at',
                    'c.usado_em as data_uso',
                    'u.name as cliente',
                    'u.email as cliente_email',
                    'c.descricao as promocao'
                )
                ->where('c.empresa_id', $empresa->id);

            if ($status) {
                $query->where('c.status', $status);
            }
            if ($dataInicio) {
                $query->whereDate('c.created_at', '>=', $dataInicio);
            }
            if ($dataFim) {
                $query->whereDate('c.created_at', '<=', $dataFim);
            }

            $resgates = $query->orderByDesc('c.created_at')->paginate(20);
        } else {
            $query = DB::table('pontos as p')
                ->leftJoin('users as u', 'u.id', '=', 'p.user_id')
                ->select(
                    'p.id',
                    DB::raw("NULL as codigo"),
                    DB::raw("'used' as status"),
                    'p.created_at',
                    'p.created_at as data_uso',
                    'u.name as cliente',
                    'u.email as cliente_email',
                    'p.descricao as promocao'
                )
                ->where('p.empresa_id', $empresa->id)
                ->where('p.tipo', 'resgate');

            if ($dataInicio) {
                $query->whereDate('p.created_at', '>=', $dataInicio);
            }
            if ($dataFim) {
                $query->whereDate('p.created_at', '<=', $dataFim);
            }

            $resgates = $query->orderByDesc('p.created_at')->paginate(20);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $resgates->items(),
                'total' => $resgates->total(),
                'current_page' => $resgates->currentPage(),
                'per_page' => $resgates->perPage(),
                'last_page' => $resgates->lastPage()
            ]
        ]);
    }

    private function hasTable(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }

    private function hasColumn(string $table, string $column): bool
    {
        try {
            return Schema::hasTable($table) && Schema::hasColumn($table, $column);
        } catch (\Throwable) {
            return false;
        }
    }

    private function resolveEmpresaForUser(int $userId): ?object
    {
        if (!$this->hasTable('empresas')) {
            return null;
        }

        try {
            $query = DB::table('empresas');

            if ($this->hasColumn('empresas', 'owner_id')) {
                $empresa = (clone $query)->where('owner_id', $userId)->first();
                if ($empresa) {
                    return $empresa;
                }
            }

            if ($this->hasColumn('empresas', 'user_id')) {
                $empresa = (clone $query)->where('user_id', $userId)->first();
                if ($empresa) {
                    return $empresa;
                }
            }

            return (clone $query)->where('id', $userId)->first();
        } catch (\Throwable $e) {
            Log::warning('Falha ao resolver empresa para usuario', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
