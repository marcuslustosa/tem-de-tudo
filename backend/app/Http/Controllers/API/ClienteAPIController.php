<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Jobs\SendWebPushJob;
use App\Models\Empresa;
use App\Models\InscricaoEmpresa;
use App\Models\Notification;
use App\Models\Promocao;
use App\Services\LoyaltyProgramService;
use App\Services\ClienteQrCodeService;
use App\Services\PromocaoInstantaneaService;
use App\Services\QRCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ClienteAPIController extends Controller
{
    /**
     * Gerar QR Code do cliente para empresa escanear
     */
    public function meuQRCode()
    {
        $user = Auth::user();
        
        // CÃ³digo Ãºnico do cliente
        $qrData = app(ClienteQrCodeService::class)->gerar($user);
        $codigo = $qrData['code'];
        
        // Gerar QR Code em SVG
        $qrCodeSvg = QrCode::size(300)
            ->format('svg')
            ->generate($codigo);
        
        return response()->json([
            'success' => true,
            'data' => [
                'codigo' => $codigo,
                'versao' => $qrData['version'],
                'expira_em' => $qrData['expires_at']->toIso8601String(),
                'qrcode_svg' => $qrCodeSvg,
                'usuario' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'pontos' => $user->pontos
                ]
            ]
        ]);
    }
    
    /**
     * Dashboard do cliente com todas as informaÃ§Ãµes
     */
    public function dashboard()
    {
        $user = Auth::user();

        $pontosTotais = 0;
        $pontosGastos = 0;
        $empresasFavoritas = collect();
        $ultimasTransacoes = collect();
        $promocoes = collect();

        if ($this->hasTable('pontos')) {
            try {
                $pontosTotais = (int) DB::table('pontos')
                    ->where('user_id', $user->id)
                    ->whereNotIn('tipo', ['resgate', 'redeem'])
                    ->sum('pontos');
            } catch (\Throwable $e) {
                Log::warning('Falha ao calcular pontos totais no dashboard do cliente', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            try {
                $pontosGastos = (int) DB::table('pontos')
                    ->where('user_id', $user->id)
                    ->whereIn('tipo', ['resgate', 'redeem'])
                    ->sum('pontos');
            } catch (\Throwable $e) {
                Log::warning('Falha ao calcular pontos gastos no dashboard do cliente', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($this->hasTable('pontos') && $this->hasTable('empresas') && $this->hasColumn('pontos', 'empresa_id')) {
            try {
                $favoriteColumns = [
                    'empresas.id',
                    'empresas.nome',
                ];
                $favoriteGroupBy = [
                    'empresas.id',
                    'empresas.nome',
                ];

                foreach (['categoria', 'ramo', 'logo', 'endereco', 'telefone', 'whatsapp', 'instagram', 'facebook', 'avaliacao_media', 'total_avaliacoes'] as $column) {
                    if ($this->hasColumn('empresas', $column)) {
                        $favoriteColumns[] = 'empresas.' . $column;
                        $favoriteGroupBy[] = 'empresas.' . $column;
                    }
                }

                $empresasFavoritas = DB::table('pontos')
                    ->join('empresas', 'pontos.empresa_id', '=', 'empresas.id')
                    ->select(array_merge($favoriteColumns, [DB::raw('SUM(pontos.pontos) as total_pontos')]))
                    ->where('pontos.user_id', $user->id)
                    ->whereNotIn('pontos.tipo', ['resgate', 'redeem'])
                    ->groupBy(...$favoriteGroupBy)
                    ->orderByDesc('total_pontos')
                    ->limit(3)
                    ->get()
                    ->map(function ($empresa) {
                        $card = new Empresa((array) $empresa);
                        $card->exists = true;

                        return $this->serializeCompanyCard($card, [
                            'total_pontos' => (int) ($empresa->total_pontos ?? 0),
                            'vinculada' => true,
                        ]);
                    })
                    ->values();
            } catch (\Throwable $e) {
                Log::warning('Falha ao carregar empresas favoritas no dashboard do cliente', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            try {
                $ultimasTransacoes = DB::table('pontos')
                    ->join('empresas', 'pontos.empresa_id', '=', 'empresas.id')
                    ->select('pontos.*', 'empresas.nome as empresa_nome')
                    ->where('pontos.user_id', $user->id)
                    ->orderByDesc('pontos.created_at')
                    ->limit(10)
                    ->get();
            } catch (\Throwable $e) {
                Log::warning('Falha ao carregar transacoes no dashboard do cliente', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($this->hasTable('promocoes')) {
            try {
                $query = DB::table('promocoes');

                if ($this->hasTable('empresas') && $this->hasColumn('promocoes', 'empresa_id')) {
                    $query->join('empresas', 'promocoes.empresa_id', '=', 'empresas.id')
                        ->select('promocoes.*', 'empresas.nome as empresa_nome');
                } else {
                    $query->select('promocoes.*');
                }

                if ($this->hasColumn('promocoes', 'ativo')) {
                    $query->where('promocoes.ativo', true);
                }
                if ($this->hasColumn('promocoes', 'status')) {
                    $query->where('promocoes.status', 'ativa');
                }
                if ($this->hasColumn('promocoes', 'created_at')) {
                    $query->orderByDesc('promocoes.created_at');
                }

                $promocoes = $query->limit(6)->get();
            } catch (\Throwable $e) {
                Log::warning('Falha ao carregar promocoes no dashboard do cliente', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $saldoPontos = max(0, $pontosTotais - $pontosGastos);
        $empresasVinculadas = $this->linkedCompaniesForUser((int) $user->id);
        $empresasDestaque = $this->featuredCompaniesForUser((int) $user->id, 4, $empresasVinculadas->pluck('id')->all());

        return response()->json([
            'success' => true,
            'data' => [
                'usuario' => [
                    'nome' => $this->cleanText($user->name),
                    'email' => $this->cleanText($user->email),
                    'saldo_pontos' => $saldoPontos,
                    'total_ganho' => $pontosTotais,
                    'total_gasto' => $pontosGastos
                ],
                'empresas_favoritas' => $empresasFavoritas,
                'empresas_vinculadas' => $empresasVinculadas->values(),
                'empresas_destaque' => $empresasDestaque->values(),
                'acoes_rapidas' => [
                    'ler_qr_empresa_url' => '/validar_resgate.html?modo=vinculo-empresa',
                    'meu_qr_url' => '/meus_pontos.html?mostrar=meu-qrcode',
                ],
                'ultimas_transacoes' => $ultimasTransacoes,
                'promocoes_disponiveis' => $promocoes
            ]
        ], 200, ['Content-Type' => 'application/json; charset=UTF-8'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    }

    /**
     * Listar todas as empresas disponÃ­veis
     */
    public function listarEmpresas(Request $request)
    {
        $query = Empresa::query()->select('empresas.*');

        if ($this->hasColumn('empresas', 'ativo')) {
            $query->where('ativo', true);
        }
        if ($this->hasColumn('empresas', 'status')) {
            $query->whereIn(
                DB::raw('LOWER(status)'),
                Empresa::normalizedStatusAliases(Empresa::STATUS_ACTIVE)
            );
        }
        
        // Filtro por ramo
        if ($request->has('ramo')) {
            $query->where('ramo', $request->ramo);
        }
        
        // Busca por nome
        if ($request->has('busca')) {
            $busca = strtolower($request->busca);
            $query->whereRaw('LOWER(nome) LIKE ?', ['%' . $busca . '%']);
        }
        
        $empresas = $query->orderBy('nome')->get();
        
        // Para cada empresa, calcular pontos do usuÃ¡rio
        $user = Auth::user();
        $linkedCompanyIds = $this->hasTable('inscricoes_empresa')
            ? InscricaoEmpresa::query()
                ->where('user_id', $user->id)
                ->pluck('empresa_id')
                ->map(fn ($id) => (int) $id)
                ->all()
            : [];
        $items = [];
        foreach ($empresas as $empresa) {
            $pontos = 0;
            if ($this->hasTable('pontos')) {
                $pontos = DB::table('pontos')
                    ->where('user_id', $user->id)
                    ->where('empresa_id', $empresa->id)
                    ->whereNotIn('tipo', ['resgate', 'redeem'])
                    ->sum('pontos');
            }
            
            $items[] = $this->serializeCompanyCard($empresa, [
                'meus_pontos' => $pontos,
                'vinculada' => in_array((int) $empresa->id, $linkedCompanyIds, true),
            ]);
        }
        
        return response()->json([
            'success' => true,
            'data' => $items
        ], 200, ['Content-Type' => 'application/json; charset=UTF-8'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    }
    
    /**
     * Ver detalhes de uma empresa
     */
    public function empresaDetalhes($id)
    {
        $empresaQuery = Empresa::query()->where('id', $id);

        if ($this->hasColumn('empresas', 'ativo')) {
            $empresaQuery->where('ativo', true);
        }
        if ($this->hasColumn('empresas', 'status')) {
            $empresaQuery->whereIn(
                DB::raw('LOWER(status)'),
                Empresa::normalizedStatusAliases(Empresa::STATUS_ACTIVE)
            );
        }

        $empresa = $empresaQuery->first();
        
        if (!$empresa) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa nÃ£o encontrada'
            ], 404);
        }
        
        $user = Auth::user();
        
        // Meus pontos nesta empresa
        $meusPontos = DB::table('pontos')
            ->where('user_id', $user->id)
            ->where('empresa_id', $id)
            ->whereNotIn('tipo', ['resgate', 'redeem'])
            ->sum('pontos');
        
        // PromoÃ§Ãµes ativas
        $promocoes = DB::table('promocoes')
            ->where('empresa_id', $id)
            ->where('ativo', true)
            ->where('status', 'ativa')
            ->get();
        
        // AvaliaÃ§Ãµes
        $avaliacoes = DB::table('avaliacoes')
            ->join('users', 'avaliacoes.user_id', '=', 'users.id')
            ->select('avaliacoes.*', 'users.name as cliente_nome')
            ->where('avaliacoes.empresa_id', $id)
            ->orderByDesc('avaliacoes.created_at')
            ->limit(10)
            ->get();
        
        // Minha avaliaÃ§Ã£o
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
                'message' => 'QR Code invÃ¡lido ou inativo'
            ], 404);
        }
        
        // Buscar empresa via Eloquent para que getPointsMultiplier() use campanhas ativas
        $empresa = Empresa::findOrFail($qrCode->empresa_id);

        // Verificar limite de uso diÃ¡rio (3 scans por dia por empresa)
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
                'message' => 'VocÃª jÃ¡ atingiu o limite de 3 scans por dia nesta empresa'
            ], 429);
        }

        /** @var LoyaltyProgramService $loyalty */
        $loyalty = app(LoyaltyProgramService::class);
        $pontosGanhos = $loyalty->calculateScanPoints($empresa);
        
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
        
        // Atualizar pontos do usuÃ¡rio
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

        // Notificacoes internas + push (cliente e empresa)
        $novoSaldo = (int) DB::table('users')->where('id', $user->id)->value('pontos');
        $empresaOwnerId = (int) ($empresa->owner_id ?? 0);

        try {
            Notification::create([
                'user_id' => $user->id,
                'title' => '+' . $pontosGanhos . ' pontos recebidos',
                'message' => "Check-in confirmado em {$empresa->nome}. Saldo atual: {$novoSaldo} pts.",
                'type' => 'transacao',
                'payload' => [
                    'kind' => 'checkin',
                    'empresa_id' => $empresa->id,
                    'empresa_nome' => $empresa->nome,
                    'pontos' => $pontosGanhos,
                    'saldo' => $novoSaldo,
                ],
            ]);

            if ($empresaOwnerId > 0) {
                Notification::create([
                    'user_id' => $empresaOwnerId,
                    'title' => 'Novo check-in registrado',
                    'message' => "{$user->name} ganhou {$pontosGanhos} pontos em {$empresa->nome}.",
                    'type' => 'transacao_empresa',
                    'payload' => [
                        'kind' => 'checkin_cliente',
                        'cliente_id' => $user->id,
                        'cliente_nome' => $user->name,
                        'empresa_id' => $empresa->id,
                        'empresa_nome' => $empresa->nome,
                        'pontos' => $pontosGanhos,
                    ],
                ]);
            }

            SendWebPushJob::dispatch(
                title: '+' . $pontosGanhos . ' pontos!',
                body: "QR Code escaneado em {$empresa->nome}. Saldo: {$novoSaldo} pts.",
                data: ['type' => 'qrcode', 'empresa' => $empresa->nome, 'url' => '/meus_pontos.html'],
                userIds: [$user->id]
            );

            if ($empresaOwnerId > 0) {
                SendWebPushJob::dispatch(
                    title: 'Novo check-in na sua loja',
                    body: "{$user->name} acumulou {$pontosGanhos} pontos.",
                    data: [
                        'type' => 'checkin_cliente',
                        'cliente' => $user->name,
                        'empresa' => $empresa->nome,
                        'url' => '/dashboard_parceiro.html',
                    ],
                    userIds: [$empresaOwnerId]
                );
            }
        } catch (\Throwable $notifyError) {
            \Log::warning('Falha ao disparar notificacoes de check-in do cliente', [
                'empresa_id' => $empresa->id,
                'cliente_id' => $user->id,
                'error' => $notifyError->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pontos adicionados com sucesso!',
            'data' => [
                'pontos_ganhos' => $pontosGanhos,
                'empresa' => $empresa->nome,
                'novo_saldo' => $novoSaldo
            ]
        ]);
    }
    
    /**
     * Resgatar promoÃ§Ã£o
     */
    public function vincularEmpresaViaQr(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:2048',
        ]);

        $user = Auth::user();
        $resolved = $this->resolveCompanyQr((string) $request->input('code'));

        if (!$resolved) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa indisponivel para vinculacao.',
            ], 404);
        }

        /** @var Empresa $empresa */
        $empresa = $resolved['empresa'];
        $qrCode = $resolved['qr_code'];

        $inscricao = InscricaoEmpresa::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'empresa_id' => $empresa->id,
            ],
            [
                'data_inscricao' => now(),
                'ultima_visita' => null,
                'bonus_adesao_resgatado' => false,
            ]
        );

        if (method_exists($qrCode, 'incrementarUso')) {
            $qrCode->incrementarUso();
        }

        return response()->json([
            'success' => true,
            'message' => $inscricao->wasRecentlyCreated
                ? 'Cliente vinculado a empresa com sucesso.'
                : 'Cliente ja estava vinculado a empresa.',
            'data' => [
                'empresa' => $this->serializeCompanyCard($empresa, [
                    'vinculada' => true,
                ]),
                'inscricao' => [
                    'id' => $inscricao->id,
                    'data_inscricao' => optional($inscricao->data_inscricao)->toIso8601String(),
                    'ultima_visita' => optional($inscricao->ultima_visita)->toIso8601String(),
                    'bonus_adesao_resgatado' => (bool) $inscricao->bonus_adesao_resgatado,
                ],
                'vinculo_criado' => $inscricao->wasRecentlyCreated,
                'public_page_url' => '/detalhe_do_parceiro.html?id=' . $empresa->id,
                'scan_url' => app(QRCodeService::class)->getCompanyScanUrl($qrCode),
            ],
        ], $inscricao->wasRecentlyCreated ? 201 : 200);
    }

    public function resgatarPromocao(Request $request, $promocaoId)
    {
        $user = Auth::user();

        $promocao = Promocao::query()->find((int) $promocaoId);
        if (!$promocao) {
            return response()->json([
                'success' => false,
                'message' => 'Promocao nao encontrada.',
            ], 404);
        }

        $empresa = Empresa::query()->publiclyVisible()->find((int) $promocao->empresa_id);
        if (!$empresa) {
            return response()->json([
                'success' => false,
                'message' => 'A empresa desta promocao nao esta disponivel publicamente.',
            ], 409);
        }

        /** @var PromocaoInstantaneaService $promocaoService */
        $promocaoService = app(PromocaoInstantaneaService::class);
        $snapshot = $promocaoService->customerPromotions($empresa, $user);
        $promotionItem = collect($snapshot['items'] ?? [])->firstWhere('id', (int) $promocao->id);

        return response()->json([
            'success' => false,
            'message' => 'A promocao instantanea so pode ser validada pela empresa lendo o QR Code do cliente.',
            'data' => [
                'promocao' => $promotionItem ?: $promocaoService->serializePromotion($promocao, [
                    'viewer_status' => 'not_linked',
                    'message' => 'A validacao acontece somente no estabelecimento.',
                    'can_self_redeem' => false,
                    'can_present_qr' => false,
                    'redeemed_at' => null,
                ]),
                'empresa' => [
                    'id' => $empresa->id,
                    'nome' => $empresa->nome,
                    'public_page_url' => '/detalhe_do_parceiro.html?id=' . $empresa->id,
                ],
                'promotions_snapshot' => $snapshot,
                'next_step' => 'Apresente seu QR Code no estabelecimento para validar esta promocao.',
            ],
        ], 409);
        
        // Buscar promoÃ§Ã£o
        $promocao = DB::table('promocoes')
            ->where('id', $promocaoId)
            ->where('ativo', true)
            ->first();
        
        if (!$promocao) {
            return response()->json([
                'success' => false,
                'message' => 'PromoÃ§Ã£o nÃ£o encontrada ou inativa'
            ], 404);
        }
        
        // Verificar se jÃ¡ resgatou hoje
        $hoje = now()->format('Y-m-d');
        $resgatadoHoje = DB::table('pontos')
            ->where('user_id', $user->id)
            ->where('empresa_id', $promocao->empresa_id)
            ->whereIn('tipo', ['resgate', 'redeem'])
            ->whereDate('created_at', $hoje)
            ->where('descricao', 'LIKE', '%' . $promocao->titulo . '%')
            ->exists();
        
        if ($resgatadoHoje) {
            return response()->json([
                'success' => false,
                'message' => 'VocÃª jÃ¡ resgatou esta promoÃ§Ã£o hoje'
            ], 429);
        }

        // Verificar estoque disponÃ­vel
        if ($promocao->qtd_disponivel !== null && $promocao->qtd_resgatada >= $promocao->qtd_disponivel) {
            return response()->json([
                'success' => false,
                'message' => 'Esta promoÃ§Ã£o nÃ£o possui mais unidades disponÃ­veis.'
            ], 400);
        }

        // Verificar limite por usuÃ¡rio
        $limiteUsuario = $promocao->limite_por_usuario ?? 1;
        $totalResgatadoUsuario = DB::table('pontos')
            ->where('user_id', $user->id)
            ->where('empresa_id', $promocao->empresa_id)
            ->whereIn('tipo', ['resgate', 'redeem'])
            ->where('descricao', 'LIKE', '%' . $promocao->titulo . '%')
            ->count();

        if ($totalResgatadoUsuario >= $limiteUsuario) {
            return response()->json([
                'success' => false,
                'message' => 'VocÃª jÃ¡ atingiu o limite de resgates para esta promoÃ§Ã£o.'
            ], 400);
        }

        /** @var LoyaltyProgramService $loyalty */
        $loyalty = app(LoyaltyProgramService::class);
        $empresaPromocao = Empresa::query()->find((int) $promocao->empresa_id);
        $pontosCusto = $loyalty->promotionCost($promocao, $empresaPromocao);
        
        // Verificar saldo
        if ($user->pontos < $pontosCusto) {
            return response()->json([
                'success' => false,
                'message' => 'Pontos insuficientes. VocÃª precisa de ' . $pontosCusto . ' pontos.'
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
        
        // Atualizar saldo do usuÃ¡rio
        DB::table('users')
            ->where('id', $user->id)
            ->decrement('pontos', $pontosCusto);
        
        // Incrementar contador de resgates e estoque utilizado
        DB::table('promocoes')
            ->where('id', $promocaoId)
            ->increment('resgates');
        DB::table('promocoes')
            ->where('id', $promocaoId)
            ->increment('qtd_resgatada');

        $empresaInfo = DB::table('empresas')
            ->select('id', 'nome', 'owner_id')
            ->where('id', $promocao->empresa_id)
            ->first();

        $empresaNome = $empresaInfo->nome ?? 'Empresa parceira';
        $empresaOwnerId = (int) ($empresaInfo->owner_id ?? 0);
        $novoSaldo = (int) DB::table('users')->where('id', $user->id)->value('pontos');

        try {
            Notification::create([
                'user_id' => $user->id,
                'title' => 'Resgate confirmado',
                'message' => "Voce resgatou \"{$promocao->titulo}\" em {$empresaNome}.",
                'type' => 'resgate',
                'payload' => [
                    'kind' => 'resgate_cliente',
                    'promocao_id' => (int) $promocaoId,
                    'promocao' => $promocao->titulo,
                    'empresa_id' => (int) $promocao->empresa_id,
                    'empresa_nome' => $empresaNome,
                    'pontos_gastos' => (int) $pontosCusto,
                    'saldo' => $novoSaldo,
                ],
            ]);

            if ($empresaOwnerId > 0) {
                Notification::create([
                    'user_id' => $empresaOwnerId,
                    'title' => 'Novo resgate recebido',
                    'message' => "{$user->name} resgatou \"{$promocao->titulo}\".",
                    'type' => 'resgate_empresa',
                    'payload' => [
                        'kind' => 'resgate_empresa',
                        'cliente_id' => $user->id,
                        'cliente_nome' => $user->name,
                        'promocao_id' => (int) $promocaoId,
                        'promocao' => $promocao->titulo,
                        'empresa_id' => (int) $promocao->empresa_id,
                        'empresa_nome' => $empresaNome,
                        'pontos_gastos' => (int) $pontosCusto,
                    ],
                ]);
            }

            SendWebPushJob::dispatch(
                title: 'Resgate realizado com sucesso',
                body: "Voce trocou {$pontosCusto} pontos por {$promocao->titulo}.",
                data: [
                    'type' => 'resgate',
                    'promocao' => $promocao->titulo,
                    'empresa' => $empresaNome,
                    'url' => '/recompensas.html',
                ],
                userIds: [$user->id]
            );

            if ($empresaOwnerId > 0) {
                SendWebPushJob::dispatch(
                    title: 'Novo resgate em sua empresa',
                    body: "{$user->name} resgatou {$promocao->titulo}.",
                    data: [
                        'type' => 'resgate_empresa',
                        'cliente' => $user->name,
                        'promocao' => $promocao->titulo,
                        'url' => '/minhas_campanhas_loja.html',
                    ],
                    userIds: [$empresaOwnerId]
                );
            }
        } catch (\Throwable $notifyError) {
            \Log::warning('Falha ao disparar notificacoes de resgate do cliente', [
                'promocao_id' => $promocaoId,
                'cliente_id' => $user->id,
                'error' => $notifyError->getMessage(),
            ]);
        }

        // Webhook de saÃ­da: evento resgate
        try {
            app(\App\Services\WebhookService::class)->disparar('resgate', [
                'user_id'    => $user->id,
                'empresa_id' => $promocao->empresa_id,
                'promocao_id' => $promocaoId,
                'promocao'   => $promocao->titulo,
                'pontos_gastos' => $pontosCusto,
            ], $promocao->empresa_id);
        } catch (\Throwable $e) {}

        // AvanÃ§ar desafios de tipo 'resgates'
        try {
            $desafiosResgate = \App\Models\Desafio::ativos()
                ->where('tipo', 'resgates')
                ->where(fn ($q) => $q->whereNull('empresa_id')->orWhere('empresa_id', $promocao->empresa_id))
                ->get();
            foreach ($desafiosResgate as $desafio) {
                $prog = \App\Models\DesafioProgresso::firstOrCreate(
                    ['user_id' => $user->id, 'desafio_id' => $desafio->id],
                    ['progresso_atual' => 0, 'concluido' => false]
                );
                if (!$prog->concluido) {
                    $prog->increment('progresso_atual');
                    $prog->refresh();
                    if ($prog->progresso_atual >= $desafio->meta) {
                        $prog->update(['concluido' => true, 'concluido_em' => now()]);
                        if (!$prog->recompensa_dada && $desafio->recompensa_pontos > 0) {
                            DB::table('users')->where('id', $user->id)->increment('pontos', $desafio->recompensa_pontos);
                            \App\Models\Ponto::create(['user_id' => $user->id, 'pontos' => $desafio->recompensa_pontos, 'tipo' => 'bonus_desafio', 'descricao' => "Desafio concluÃ­do: {$desafio->nome} ðŸ†", 'data' => now()]);
                            $prog->update(['recompensa_dada' => true]);
                        }
                    }
                }
            }
        } catch (\Throwable $e) {}

        $codigoResgate = strtoupper(substr(md5($user->id . $promocaoId . now()), 0, 8));

        return response()->json([
            'success' => true,
            'message' => 'PromoÃ§Ã£o resgatada com sucesso!',
            'data' => [
                'promocao' => $promocao->titulo,
                'pontos_gastos' => $pontosCusto,
                'novo_saldo' => $novoSaldo,
                'codigo_resgate' => $codigoResgate,
                'nps_solicitado' => true, // frontend deve exibir modal NPS
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
        
        // Verificar se jÃ¡ avaliou
        $jaAvaliou = DB::table('avaliacoes')
            ->where('user_id', $user->id)
            ->where('empresa_id', $request->empresa_id)
            ->exists();
        
        if ($jaAvaliou) {
            // Atualizar avaliaÃ§Ã£o existente
            DB::table('avaliacoes')
                ->where('user_id', $user->id)
                ->where('empresa_id', $request->empresa_id)
                ->update([
                    'estrelas' => $request->estrelas,
                    'comentario' => $request->comentario,
                    'updated_at' => now()
                ]);
                
            $message = 'AvaliaÃ§Ã£o atualizada com sucesso!';
        } else {
            // Criar nova avaliaÃ§Ã£o
            DB::table('avaliacoes')->insert([
                'user_id' => $user->id,
                'empresa_id' => $request->empresa_id,
                'estrelas' => $request->estrelas,
                'comentario' => $request->comentario,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            $message = 'AvaliaÃ§Ã£o criada com sucesso!';
        }
        
        // Recalcular mÃ©dia da empresa
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
     * HistÃ³rico de pontos
     */
    public function historicoPontos(Request $request)
    {
        $user = Auth::user();
        
        $query = DB::table('pontos')
            ->leftJoin('empresas', 'pontos.empresa_id', '=', 'empresas.id')
            ->select(
                'pontos.*',
                DB::raw("COALESCE(empresas.nome, 'Tem de Tudo') as empresa_nome")
            )
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
            'data' => [
                'data' => $historico->items(),
                'total' => $historico->total(),
                'current_page' => $historico->currentPage(),
                'per_page' => $historico->perPage(),
                'last_page' => $historico->lastPage()
            ]
        ]);
    }
    
    /**
     * Listar todas as promoÃ§Ãµes ativas
     */
    public function listarPromocoes(Request $request)
    {
        $user = Auth::user();

        /** @var PromocaoInstantaneaService $promocaoService */
        $promocaoService = app(PromocaoInstantaneaService::class);
        $promocoes = $promocaoService->listPromotionsForCustomer(
            $user,
            $request->filled('empresa_id') ? (int) $request->input('empresa_id') : null
        );

        return response()->json([
            'success' => true,
            'data' => $promocoes,
            'total' => count($promocoes),
        ]);

        $query = DB::table('promocoes')
            ->join('empresas', 'promocoes.empresa_id', '=', 'empresas.id')
            ->select(
                'promocoes.*',
                'empresas.nome as empresa_nome',
                'empresas.ramo as empresa_ramo',
                'empresas.logo as empresa_logo'
            )
            ->where('promocoes.ativo', true)
            ->where('promocoes.status', 'ativa')
            ->where('empresas.ativo', true);
        
        // Filtro por empresa
        if ($request->has('empresa_id')) {
            $query->where('promocoes.empresa_id', $request->empresa_id);
        }
        
        // Filtro por tipo de promoÃ§Ã£o
        if ($request->has('tipo')) {
            $query->where('promocoes.tipo', $request->tipo);
        }
        
        $promocoes = $query
            ->orderByDesc('promocoes.created_at')
            ->get();
        
        // Adicionar contagem de dias restantes (robusto a dados nulos)
        foreach ($promocoes as $promo) {
            $dataFim = $promo->data_fim ?? $promo->validade ?? $promo->validade_fim ?? null;
            if ($dataFim) {
                $diasRestantes = now()->diffInDays(\Carbon\Carbon::parse($dataFim), false);
                $promo->dias_restantes = $diasRestantes > 0 ? $diasRestantes : 0;
                $promo->expirada = $diasRestantes < 0;
            } else {
                $promo->dias_restantes = null;
                $promo->expirada = false;
            }
        }
        
        return response()->json([
            'success' => true,
            'data' => $promocoes,
            'total' => $promocoes->count()
        ]);
    }

    /**
     * Ranking de pontos: top 50 clientes + posiÃ§Ã£o do usuÃ¡rio autenticado.
     */
    public function rankingPontos(): \Illuminate\Http\JsonResponse
    {
        $user = Auth::user();

        $top = DB::table('users')
            ->where('perfil', 'cliente')
            ->whereNull('deleted_at')
            ->orderByDesc('pontos')
            ->limit(50)
            ->select('id', 'name', 'pontos', 'nivel', 'posicao_ranking')
            ->get()
            ->map(function ($u, $index) {
                $u->posicao = $u->posicao_ranking ?: ($index + 1);
                return $u;
            });

        $minhaPosicao = $user->posicao_ranking;
        if (!$minhaPosicao) {
            $minhaPosicao = DB::table('users')
                ->where('perfil', 'cliente')
                ->whereNull('deleted_at')
                ->where('pontos', '>', $user->pontos)
                ->count() + 1;
        }

        return response()->json([
            'success'       => true,
            'data'          => [
                'ranking'         => $top,
                'minha_posicao'   => $minhaPosicao,
                'meus_pontos'     => $user->pontos,
            ],
        ]);
    }

    private function resolveCompanyQr(string $code): ?array
    {
        $normalizedCode = $this->normalizeCompanyQrInput($code);
        if ($normalizedCode === '') {
            return null;
        }

        /** @var QRCodeService $service */
        $service = app(QRCodeService::class);
        $validation = $service->validarCodigo($normalizedCode);

        if (!($validation['valido'] ?? false) || ($validation['type'] ?? null) !== 'empresa') {
            return null;
        }

        $empresa = $validation['empresa'] ?? null;
        if (!$empresa instanceof Empresa || !$empresa->isPubliclyVisible()) {
            return null;
        }

        return [
            'empresa' => $empresa,
            'qr_code' => $validation['qr_code'],
        ];
    }

    private function normalizeCompanyQrInput(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (!str_contains($value, '://') && !str_contains($value, '?code=')) {
            return $value;
        }

        $query = parse_url($value, PHP_URL_QUERY);
        if (!is_string($query) || $query === '') {
            return $value;
        }

        parse_str($query, $params);
        $embeddedCode = trim((string) ($params['code'] ?? ''));

        return $embeddedCode !== '' ? $embeddedCode : $value;
    }

    private function linkedCompaniesForUser(int $userId)
    {
        if (!$this->hasTable('inscricoes_empresa')) {
            return collect();
        }

        return InscricaoEmpresa::query()
            ->with('empresa')
            ->where('user_id', $userId)
            ->orderByDesc('data_inscricao')
            ->get()
            ->filter(function (InscricaoEmpresa $inscricao) {
                return $inscricao->empresa instanceof Empresa
                    && $inscricao->empresa->isPubliclyVisible();
            })
            ->map(function (InscricaoEmpresa $inscricao) {
                return $this->serializeCompanyCard($inscricao->empresa, [
                    'vinculada' => true,
                    'data_inscricao' => optional($inscricao->data_inscricao)->toIso8601String(),
                    'ultima_visita' => optional($inscricao->ultima_visita)->toIso8601String(),
                ]);
            })
            ->values();
    }

    private function featuredCompaniesForUser(int $userId, int $limit = 4, array $excludeIds = [])
    {
        $query = Empresa::query()->select('empresas.*');

        if ($this->hasColumn('empresas', 'ativo')) {
            $query->where('ativo', true);
        }
        if ($this->hasColumn('empresas', 'status')) {
            $query->whereIn(
                DB::raw('LOWER(status)'),
                Empresa::normalizedStatusAliases(Empresa::STATUS_ACTIVE)
            );
        }
        if ($excludeIds !== []) {
            $query->whereNotIn('id', array_map('intval', $excludeIds));
        }
        if ($this->hasColumn('empresas', 'total_avaliacoes')) {
            $query->orderByDesc('total_avaliacoes');
        }

        return $query
            ->orderBy('nome')
            ->limit($limit)
            ->get()
            ->map(fn (Empresa $empresa) => $this->serializeCompanyCard($empresa, [
                'vinculada' => false,
            ]))
            ->values();
    }

    private function serializeCompanyCard(Empresa $empresa, array $extra = []): array
    {
        return array_merge([
            'id' => $empresa->id,
            'nome' => $this->cleanText($empresa->nome),
            'categoria' => $this->cleanText($empresa->categoria ?? $empresa->ramo ?? ''),
            'ramo' => $this->cleanText($empresa->ramo ?? $empresa->categoria ?? ''),
            'logo' => $this->cleanText($empresa->logo ?: '/assets/images/company1.jpg'),
            'endereco' => $this->cleanText($empresa->endereco ?? ''),
            'telefone' => $this->cleanText($empresa->telefone ?? ''),
            'whatsapp' => $this->cleanText($empresa->whatsapp ?? ''),
            'instagram' => $this->cleanText($empresa->instagram ?? ''),
            'facebook' => $this->cleanText($empresa->facebook ?? ''),
            'avaliacao_media' => (float) ($empresa->avaliacao_media ?? 0),
            'total_avaliacoes' => (int) ($empresa->total_avaliacoes ?? 0),
            'public_page_url' => '/detalhe_do_parceiro.html?id=' . $empresa->id,
            'status' => $empresa->operationalStatus(),
        ], $extra);
    }

    private function cleanText($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        $clean = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
        $clean = is_string($clean) ? $clean : $value;

        if (!preg_match('/[\x{00C3}\x{00E2}\x{FFFD}\x{251C}]/u', $clean)) {
            return $clean;
        }

        foreach (['Windows-1252', 'ISO-8859-1'] as $sourceEncoding) {
            $converted = @mb_convert_encoding($clean, 'UTF-8', $sourceEncoding);
            if (is_string($converted) && $converted !== '' && !preg_match('/[\x{00C3}\x{00E2}\x{FFFD}\x{251C}]/u', $converted)) {
                return $converted;
            }
        }

        return $clean;
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
}
