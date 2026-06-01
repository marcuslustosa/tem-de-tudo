<?php

namespace App\Services;

use App\Models\Avaliacao;
use App\Models\BonusAdesaoResgate;
use App\Models\BonusAniversarioResgate;
use App\Models\CartaoFidelidadeMovimento;
use App\Models\Empresa;
use App\Models\InscricaoEmpresa;
use App\Models\LembreteAusencia;
use App\Models\NotificacaoPush;
use App\Models\Promocao;
use App\Models\PromocaoResgate;
use App\Models\PushSubscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class RelatorioOperacionalService
{
    private const DEFAULT_INACTIVITY_DAYS = 30;

    public function companySummary(Empresa $empresa): array
    {
        $linkedCustomerIds = rescue(function () use ($empresa) {
            return InscricaoEmpresa::query()
                ->where('empresa_id', $empresa->id)
                ->pluck('user_id')
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values();
        }, collect(), false);

        $thresholdDays = (int) rescue(fn () => $this->activeReminderThreshold($empresa), self::DEFAULT_INACTIVITY_DAYS, false);
        $inactiveCount = (int) rescue(fn () => $this->countInactiveLinkedCustomers($empresa, $linkedCustomerIds, $thresholdDays), 0, false);
        $customersWithPush = (int) rescue(fn () => $this->countLinkedCustomersWithPush($linkedCustomerIds), 0, false);
        $lastNotificationAt = rescue(fn () => $this->latestNotificationSentAt($empresa->id), null, false);

        $recentClients = rescue(fn () => $this->safeRecentClients($empresa, 5), [], false);
        $latestLoyaltyMovements = rescue(fn () => $this->latestLoyaltyMovements($empresa, 10), [], false);
        $latestRedemptions = rescue(fn () => $this->latestRedemptions($empresa, 10), [], false);

        return [
            'empresa' => [
                'id' => (int) $empresa->id,
                'nome' => $empresa->nome,
                'status' => $empresa->operationalStatus(),
                'ativo' => (bool) $empresa->ativo,
            ],
            'cards' => [
                'total_clientes_vinculados' => (int) $linkedCustomerIds->count(),
                'clientes_com_push_ativo' => $customersWithPush,
                'novos_clientes_mes' => (int) rescue(function () use ($empresa) {
                    return InscricaoEmpresa::query()
                        ->where('empresa_id', $empresa->id)
                        ->whereBetween('data_inscricao', [now()->startOfMonth(), now()->endOfMonth()])
                        ->count();
                }, 0, false),
                'clientes_aniversariantes_mes' => (int) rescue(fn () => $this->countBirthdayCustomersForMonth($linkedCustomerIds), 0, false),
                'clientes_inativos' => $inactiveCount,
                'total_bonus_adesao_resgatados' => (int) rescue(fn () => $this->countBonusAdesaoResgates($empresa->id), 0, false),
                'total_pontos_distribuidos' => (int) rescue(fn () => $this->sumLoyaltyPoints($empresa->id, CartaoFidelidadeMovimento::TYPE_EARNED), 0, false),
                'total_recompensas_resgatadas' => (int) rescue(fn () => $this->countLoyaltyRedemptions($empresa->id), 0, false),
                'total_promocoes_criadas' => (int) rescue(fn () => $this->countPromotions($empresa->id), 0, false),
                'total_promocoes_resgatadas' => (int) rescue(fn () => $this->countPromotionRedemptions($empresa->id), 0, false),
                'total_notificacoes_enviadas' => (int) rescue(fn () => $this->countSentNotifications($empresa->id), 0, false),
                'ultimo_envio_notificacao' => $lastNotificationAt,
                'total_avaliacoes' => (int) rescue(fn () => $this->countReviews($empresa->id), 0, false),
                'media_avaliacao' => (float) rescue(fn () => $this->averageReviews($empresa->id), 0.0, false),
            ],
            'push' => [
                'clientes_vinculados' => (int) $linkedCustomerIds->count(),
                'clientes_com_push_ativo' => $customersWithPush,
                'clientes_sem_push_ativo' => max(0, (int) $linkedCustomerIds->count() - $customersWithPush),
                'total_notificacoes_enviadas' => (int) rescue(fn () => $this->countSentNotifications($empresa->id), 0, false),
                'ultimo_envio_notificacao' => $lastNotificationAt,
            ],
            'config' => [
                'lembrete_dias_sem_visita' => $thresholdDays,
            ],
            'clientes_recentes' => $recentClients,
            'ultimos_resgates' => $latestRedemptions,
            'ultimos_movimentos_fidelidade' => $latestLoyaltyMovements,
        ];
    }

    public function companyClients(Empresa $empresa, ?string $search = null, int $perPage = 20): array
    {
        $query = InscricaoEmpresa::query()
            ->with('user:' . implode(',', $this->companyClientUserColumns()))
            ->where('empresa_id', $empresa->id)
            ->orderByDesc('data_inscricao');

        $normalizedSearch = strtolower(trim((string) $search));
        if ($normalizedSearch !== '') {
            $query->whereHas('user', function ($userQuery) use ($normalizedSearch) {
                $userQuery->where(function ($builder) use ($normalizedSearch) {
                    $builder->whereRaw('LOWER(name) LIKE ?', ["%{$normalizedSearch}%"])
                        ->orWhereRaw('LOWER(email) LIKE ?', ["%{$normalizedSearch}%"]);

                    if (Schema::hasColumn('users', 'telefone')) {
                        $builder->orWhereRaw('LOWER(telefone) LIKE ?', ["%{$normalizedSearch}%"]);
                    }
                });
            });
        }

        $paginator = $query->paginate(max(1, min($perPage, 50)));
        $inscricoes = collect($paginator->items());
        $customerIds = $inscricoes->pluck('user_id')->map(fn ($id) => (int) $id)->all();
        $allCustomerIds = InscricaoEmpresa::query()
            ->where('empresa_id', $empresa->id)
            ->pluck('user_id');
        $thresholdDays = $this->activeReminderThreshold($empresa);

        $aggregates = $this->customerAggregateMaps($empresa->id, $customerIds);
        $aggregates['push'] = $this->linkedCustomerPushMap($customerIds);
        $items = $inscricoes->map(function (InscricaoEmpresa $inscricao) use ($aggregates, $thresholdDays) {
            return $this->serializeLinkedCustomer($inscricao, $aggregates, $thresholdDays);
        })->values()->all();

        $linkedCount = (int) $allCustomerIds->count();
        $customersWithPush = $this->countLinkedCustomersWithPush(collect($allCustomerIds));

        return [
            'data' => $items,
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'last_page' => $paginator->lastPage(),
            'summary' => [
                'threshold_days' => $thresholdDays,
                'clientes_inativos' => $this->countInactiveLinkedCustomers($empresa, $allCustomerIds, $thresholdDays),
                'clientes_com_push_ativo' => $customersWithPush,
                'clientes_sem_push_ativo' => max(0, $linkedCount - $customersWithPush),
            ],
        ];
    }

    public function adminSummary(): array
    {
        $companies = $this->hasTable('empresas')
            ? Empresa::query()->with('owner:id,name,email')->orderByDesc('created_at')->get()
            : collect();

        $statusSummary = [
            'total_empresas' => 0,
            'empresas_pending' => 0,
            'empresas_active' => 0,
            'empresas_suspended' => 0,
            'empresas_rejected' => 0,
        ];

        foreach ($companies as $empresa) {
            $statusSummary['total_empresas']++;
            $statusKey = 'empresas_' . $empresa->operationalStatus();
            if (array_key_exists($statusKey, $statusSummary)) {
                $statusSummary[$statusKey]++;
            }
        }

        $companiesWithCampaigns = $this->companyIdsWithCommercialTools();
        $companiesWithLinkedClients = $this->companyIdsWithLinkedCustomers();

        $topCompaniesByClients = $this->hasTable('inscricoes_empresa')
            ? DB::table('inscricoes_empresa')
                ->join('empresas', 'inscricoes_empresa.empresa_id', '=', 'empresas.id')
                ->select('empresas.id', 'empresas.nome', DB::raw('COUNT(inscricoes_empresa.id) as total_clientes'))
                ->groupBy('empresas.id', 'empresas.nome')
                ->orderByDesc('total_clientes')
                ->limit(5)
                ->get()
                ->map(fn ($row) => [
                    'empresa_id' => (int) $row->id,
                    'nome' => $row->nome,
                    'total_clientes' => (int) $row->total_clientes,
                ])
                ->values()
                ->all()
            : [];

        $redemptionsByCompany = [];
        foreach ($this->aggregateRedemptionsByCompany() as $companyId => $total) {
            $redemptionsByCompany[(int) $companyId] = (int) $total;
        }

        $topCompaniesByRedemptions = $companies
            ->map(function (Empresa $empresa) use ($redemptionsByCompany) {
                return [
                    'empresa_id' => (int) $empresa->id,
                    'nome' => $empresa->nome,
                    'status' => $empresa->operationalStatus(),
                    'total_resgates' => (int) ($redemptionsByCompany[$empresa->id] ?? 0),
                ];
            })
            ->sortByDesc('total_resgates')
            ->take(5)
            ->values()
            ->all();

        return [
            'cards' => [
                ...$statusSummary,
                'total_clientes' => $this->countUsersByPerfil('cliente'),
                'total_vinculos_cliente_empresa' => $this->hasTable('inscricoes_empresa')
                    ? (int) DB::table('inscricoes_empresa')->count()
                    : 0,
                'empresas_com_campanhas' => count($companiesWithCampaigns),
                'empresas_sem_campanhas' => max(0, $statusSummary['total_empresas'] - count($companiesWithCampaigns)),
                'empresas_com_clientes_vinculados' => count($companiesWithLinkedClients),
                'empresas_sem_clientes_vinculados' => max(0, $statusSummary['total_empresas'] - count($companiesWithLinkedClients)),
                'total_promocoes' => $this->hasTable('promocoes')
                    ? (int) DB::table('promocoes')->count()
                    : 0,
                'total_resgates' => array_sum($redemptionsByCompany),
                'total_notificacoes' => $this->hasTable('notificacoes_push')
                    ? (int) NotificacaoPush::query()->count()
                    : 0,
                'media_geral_avaliacoes' => $this->hasTable('avaliacoes')
                    ? round((float) Avaliacao::query()->avg('estrelas'), 1)
                    : 0.0,
            ],
            'empresas_com_mais_clientes' => $topCompaniesByClients,
            'empresas_com_mais_resgates' => $topCompaniesByRedemptions,
            'empresas_recentes' => $companies
                ->take(8)
                ->map(function (Empresa $empresa) {
                    return [
                        'empresa_id' => (int) $empresa->id,
                        'nome' => $empresa->nome,
                        'status' => $empresa->operationalStatus(),
                        'responsavel' => $empresa->owner?->name,
                        'email' => $empresa->owner?->email,
                        'created_at' => optional($empresa->created_at)->toIso8601String(),
                    ];
                })
                ->values()
                ->all(),
        ];
    }

    private function companyIdsWithCommercialTools(): array
    {
        $tables = [
            'promocoes',
            'bonus_adesao',
            'cartoes_fidelidade',
            'bonus_aniversario',
            'lembretes_ausencia',
        ];

        $ids = collect();

        foreach ($tables as $table) {
            if (!$this->hasTable($table) || !$this->hasColumn($table, 'empresa_id')) {
                continue;
            }

            $ids = $ids->merge(
                DB::table($table)
                    ->whereNotNull('empresa_id')
                    ->distinct()
                    ->pluck('empresa_id')
                    ->map(fn ($id) => (int) $id)
            );
        }

        return $ids
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function companyIdsWithLinkedCustomers(): array
    {
        if (!$this->hasTable('inscricoes_empresa') || !$this->hasColumn('inscricoes_empresa', 'empresa_id')) {
            return [];
        }

        return DB::table('inscricoes_empresa')
            ->whereNotNull('empresa_id')
            ->distinct()
            ->pluck('empresa_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();
    }

    private function customerAggregateMaps(int $empresaId, array $customerIds): array
    {
        if ($customerIds === []) {
            return [
                'loyalty' => [],
                'promotions' => [],
                'bonus_adesao' => [],
                'bonus_aniversario' => [],
                'points' => [],
            ];
        }

        $loyalty = $this->hasTable('cartoes_fidelidade_movimentos')
            ? CartaoFidelidadeMovimento::query()
                ->selectRaw("
                    user_id,
                    SUM(CASE WHEN tipo = 'earned' THEN pontos ELSE 0 END) as total_ganho,
                    SUM(CASE WHEN tipo = 'redeemed' THEN pontos ELSE 0 END) as total_resgatado_pontos,
                    SUM(CASE WHEN tipo = 'redeemed' THEN 1 ELSE 0 END) as total_recompensas_resgatadas,
                    MAX(created_at) as ultima_movimentacao
                ")
                ->where('empresa_id', $empresaId)
                ->whereIn('user_id', $customerIds)
                ->groupBy('user_id')
                ->get()
                ->keyBy('user_id')
            : collect();

        $promotions = $this->hasTable('promocao_resgates')
            ? PromocaoResgate::query()
                ->selectRaw('user_id, COUNT(*) as total_promocoes_resgatadas, MAX(redeemed_at) as ultimo_resgate_promocao')
                ->where('empresa_id', $empresaId)
                ->whereIn('user_id', $customerIds)
                ->groupBy('user_id')
                ->get()
                ->keyBy('user_id')
            : collect();

        $bonusAdesao = $this->bonusAdesaoUserAggregates($empresaId, $customerIds);
        $bonusAniversario = $this->hasTable('bonus_aniversario_resgates')
            ? BonusAniversarioResgate::query()
                ->selectRaw('user_id, COUNT(*) as total_bonus_aniversario_resgatados, MAX(redeemed_at) as ultimo_bonus_aniversario')
                ->where('empresa_id', $empresaId)
                ->whereIn('user_id', $customerIds)
                ->groupBy('user_id')
                ->get()
                ->keyBy('user_id')
            : collect();

        $points = $this->hasTable('pontos')
            ? DB::table('pontos')
                ->selectRaw('user_id, MAX(created_at) as ultimo_checkin')
                ->where('empresa_id', $empresaId)
                ->whereIn('user_id', $customerIds)
                ->groupBy('user_id')
                ->get()
                ->keyBy('user_id')
            : collect();

        return [
            'loyalty' => $loyalty,
            'promotions' => $promotions,
            'bonus_adesao' => $bonusAdesao,
            'bonus_aniversario' => $bonusAniversario,
            'points' => $points,
        ];
    }

    private function serializeLinkedCustomer(
        InscricaoEmpresa $inscricao,
        array $aggregates,
        ?int $thresholdDays = null
    ): array {
        $aggregates = array_merge([
            'loyalty' => [],
            'promotions' => [],
            'bonus_adesao' => [],
            'bonus_aniversario' => [],
            'points' => [],
            'push' => [],
        ], $aggregates);

        /** @var User|null $customer */
        $customer = $inscricao->user;
        $customerId = (int) ($customer?->id ?? $inscricao->user_id);
        $loyalty = $aggregates['loyalty'][$customerId] ?? null;
        $promotions = $aggregates['promotions'][$customerId] ?? null;
        $bonusAdesao = $aggregates['bonus_adesao'][$customerId] ?? null;
        $bonusAniversario = $aggregates['bonus_aniversario'][$customerId] ?? null;
        $points = $aggregates['points'][$customerId] ?? null;
        $push = $aggregates['push'][$customerId] ?? [
            'has_active_subscription' => false,
            'total_subscriptions' => 0,
            'last_seen_at' => null,
        ];

        $lastVisit = $this->resolveLastVisit(
            $inscricao->ultima_visita,
            $loyalty->ultima_movimentacao ?? null,
            $points->ultimo_checkin ?? null,
            $inscricao->data_inscricao
        );
        $daysInactive = $this->daysInactive($lastVisit, $inscricao->data_inscricao);
        $currentPoints = max(
            0,
            (int) ($loyalty->total_ganho ?? 0) - (int) ($loyalty->total_resgatado_pontos ?? 0)
        );

        return [
            'id' => $customerId,
            'nome' => $customer?->name,
            'email' => $customer?->email,
            'telefone' => $customer?->telefone,
            'data_nascimento' => optional($customer?->data_nascimento)->toDateString(),
            'data_vinculo' => optional($inscricao->data_inscricao)->toIso8601String(),
            'ultima_visita' => $lastVisit?->toIso8601String(),
            'dias_inatividade' => $daysInactive,
            'status_inatividade' => $thresholdDays !== null && $daysInactive >= $thresholdDays ? 'inactive' : 'active',
            'pontos_atuais' => $currentPoints,
            'total_pontos_distribuidos' => (int) ($loyalty->total_ganho ?? 0),
            'total_recompensas_resgatadas' => (int) ($loyalty->total_recompensas_resgatadas ?? 0),
            'total_promocoes_resgatadas' => (int) ($promotions->total_promocoes_resgatadas ?? 0),
            'total_bonus_adesao_resgatados' => (int) ($bonusAdesao->total_bonus_adesao_resgatados ?? 0),
            'total_bonus_aniversario_resgatados' => (int) ($bonusAniversario->total_bonus_aniversario_resgatados ?? 0),
            'push_ativo' => (bool) ($push['has_active_subscription'] ?? false),
            'push_total_dispositivos' => (int) ($push['total_subscriptions'] ?? 0),
            'push_ultima_atividade' => $push['last_seen_at'] ?? null,
        ];
    }

    private function latestLoyaltyMovements(Empresa $empresa, int $limit): array
    {
        if (!$this->hasTable('cartoes_fidelidade_movimentos')) {
            return [];
        }

        try {
            $query = CartaoFidelidadeMovimento::query()
                ->with(['cliente:id,name', 'cartao:id,titulo'])
                ->where('empresa_id', $empresa->id);

            if ($this->hasColumn('cartoes_fidelidade_movimentos', 'created_at')) {
                $query->orderByDesc('created_at');
            } else {
                $query->orderByDesc('id');
            }

            return $query
                ->limit($limit)
                ->get()
                ->map(function (CartaoFidelidadeMovimento $movimento) {
                    return [
                        'id' => (int) $movimento->id,
                        'cliente_id' => (int) $movimento->user_id,
                        'cliente_nome' => $movimento->cliente?->name,
                        'cartao_id' => (int) $movimento->cartao_fidelidade_id,
                        'cartao_titulo' => $movimento->cartao?->titulo,
                        'tipo' => $movimento->tipo,
                        'pontos' => (int) $movimento->pontos,
                        'descricao' => $movimento->descricao,
                        'created_at' => optional($movimento->created_at)->toIso8601String(),
                    ];
                })
                ->values()
                ->all();
        } catch (\Throwable $e) {
            Log::warning('Falha ao carregar ultimos movimentos de fidelidade', [
                'empresa_id' => $empresa->id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    private function latestRedemptions(Empresa $empresa, int $limit): array
    {
        $items = [];

        try {
            if ($this->hasTable('promocao_resgates')) {
                $promotionRedemptions = PromocaoResgate::query()
                    ->with(['user:id,name', 'promocao:id,titulo'])
                    ->where('empresa_id', $empresa->id);

                if ($this->hasColumn('promocao_resgates', 'redeemed_at')) {
                    $promotionRedemptions->orderByDesc('redeemed_at');
                } elseif ($this->hasColumn('promocao_resgates', 'created_at')) {
                    $promotionRedemptions->orderByDesc('created_at');
                } else {
                    $promotionRedemptions->orderByDesc('id');
                }

                $items = array_merge($items, $promotionRedemptions
                    ->limit($limit)
                    ->get()
                    ->map(fn (PromocaoResgate $row) => [
                        'tipo' => 'promocao',
                        'cliente_nome' => $row->user?->name,
                        'titulo' => $row->promocao?->titulo,
                        'status' => $row->status,
                        'data' => optional($row->redeemed_at ?: $row->created_at)->toIso8601String(),
                    ])
                    ->all());
            }

            if ($this->hasTable('bonus_aniversario_resgates')) {
                $birthdayRedemptions = BonusAniversarioResgate::query()
                    ->with(['user:id,name', 'bonus:id,titulo'])
                    ->where('empresa_id', $empresa->id);

                if ($this->hasColumn('bonus_aniversario_resgates', 'redeemed_at')) {
                    $birthdayRedemptions->orderByDesc('redeemed_at');
                } elseif ($this->hasColumn('bonus_aniversario_resgates', 'created_at')) {
                    $birthdayRedemptions->orderByDesc('created_at');
                } else {
                    $birthdayRedemptions->orderByDesc('id');
                }

                $items = array_merge($items, $birthdayRedemptions
                    ->limit($limit)
                    ->get()
                    ->map(fn (BonusAniversarioResgate $row) => [
                        'tipo' => 'bonus_aniversario',
                        'cliente_nome' => $row->user?->name,
                        'titulo' => $row->bonus?->titulo,
                        'status' => $row->status,
                        'data' => optional($row->redeemed_at ?: $row->created_at)->toIso8601String(),
                    ])
                    ->all());
            }

            if ($this->hasTable('cartoes_fidelidade_movimentos')) {
                $loyaltyRedemptions = CartaoFidelidadeMovimento::query()
                    ->with(['cliente:id,name', 'cartao:id,titulo'])
                    ->where('empresa_id', $empresa->id)
                    ->where('tipo', CartaoFidelidadeMovimento::TYPE_REDEEMED);

                if ($this->hasColumn('cartoes_fidelidade_movimentos', 'created_at')) {
                    $loyaltyRedemptions->orderByDesc('created_at');
                } else {
                    $loyaltyRedemptions->orderByDesc('id');
                }

                $items = array_merge($items, $loyaltyRedemptions
                    ->limit($limit)
                    ->get()
                    ->map(fn (CartaoFidelidadeMovimento $row) => [
                        'tipo' => 'fidelidade',
                        'cliente_nome' => $row->cliente?->name,
                        'titulo' => $row->cartao?->titulo,
                        'status' => $row->tipo,
                        'data' => optional($row->created_at)->toIso8601String(),
                    ])
                    ->all());
            }

            foreach ($this->bonusAdesaoRedemptions($empresa->id, $limit) as $row) {
                $items[] = $row;
            }
        } catch (\Throwable $e) {
            Log::warning('Falha ao carregar ultimos resgates operacionais', [
                'empresa_id' => $empresa->id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }

        usort($items, function (array $left, array $right) {
            return strcmp((string) ($right['data'] ?? ''), (string) ($left['data'] ?? ''));
        });

        return array_slice($items, 0, $limit);
    }

    private function safeRecentClients(Empresa $empresa, int $limit): array
    {
        try {
            return InscricaoEmpresa::query()
                ->with('user:' . implode(',', $this->companyClientUserColumns()))
                ->where('empresa_id', $empresa->id)
                ->orderByDesc('data_inscricao')
                ->limit($limit)
                ->get()
                ->map(fn (InscricaoEmpresa $inscricao) => $this->serializeLinkedCustomer($inscricao, []))
                ->values()
                ->all();
        } catch (\Throwable $e) {
            Log::warning('Falha ao carregar clientes recentes do painel da empresa', [
                'empresa_id' => $empresa->id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    private function companyClientUserColumns(): array
    {
        $columns = ['id', 'name', 'email'];

        foreach (['telefone', 'data_nascimento'] as $column) {
            if ($this->hasColumn('users', $column)) {
                $columns[] = $column;
            }
        }

        return $columns;
    }

    private function bonusAdesaoRedemptions(int $empresaId, int $limit): array
    {
        $table = $this->bonusAdesaoTable();
        if ($table === null) {
            return [];
        }

        $query = DB::table("{$table} as bar")
            ->leftJoin('users as u', 'u.id', '=', 'bar.user_id')
            ->leftJoin('bonus_adesao as ba', 'ba.id', '=', 'bar.bonus_id')
            ->select(
                'u.name as cliente_nome',
                'ba.titulo as titulo',
                'bar.status',
                $this->hasColumn($table, 'redeemed_at')
                    ? DB::raw('bar.redeemed_at as data')
                    : DB::raw('bar.data_resgate as data')
            )
            ->where('bar.empresa_id', $empresaId)
            ->orderByDesc('data')
            ->limit($limit);

        if ($this->hasColumn($table, 'status')) {
            $query->where('bar.status', 'redeemed');
        } elseif ($this->hasColumn($table, 'resgatado')) {
            $this->applyTruthyFilter($query, $table, 'bar.resgatado', 'resgatado');
        }

        return $query->get()->map(function ($row) {
            return [
                'tipo' => 'bonus_adesao',
                'cliente_nome' => $row->cliente_nome,
                'titulo' => $row->titulo,
                'status' => $row->status ?? 'redeemed',
                'data' => $row->data ? Carbon::parse($row->data)->toIso8601String() : null,
            ];
        })->values()->all();
    }

    private function countInactiveLinkedCustomers(Empresa $empresa, Collection $linkedCustomerIds, int $thresholdDays): int
    {
        if ($linkedCustomerIds->isEmpty()) {
            return 0;
        }

        $aggregates = $this->customerAggregateMaps($empresa->id, $linkedCustomerIds->map(fn ($id) => (int) $id)->all());
        $inscricoes = InscricaoEmpresa::query()
            ->with('user:id,name')
            ->where('empresa_id', $empresa->id)
            ->whereIn('user_id', $linkedCustomerIds->all())
            ->get();

        return $inscricoes->filter(function (InscricaoEmpresa $inscricao) use ($aggregates, $thresholdDays) {
            $snapshot = $this->serializeLinkedCustomer($inscricao, $aggregates, $thresholdDays);

            return $snapshot['status_inatividade'] === 'inactive';
        })->count();
    }

    private function activeReminderThreshold(Empresa $empresa): int
    {
        if (!$this->hasTable('lembretes_ausencia')) {
            return self::DEFAULT_INACTIVITY_DAYS;
        }

        $reminder = LembreteAusencia::query()
            ->where('empresa_id', $empresa->id)
            ->orderByDesc('ativo')
            ->orderByDesc('updated_at')
            ->first();

        if (!$reminder instanceof LembreteAusencia) {
            return self::DEFAULT_INACTIVITY_DAYS;
        }

        return max(1, (int) ($reminder->daysWithoutVisit() ?: self::DEFAULT_INACTIVITY_DAYS));
    }

    private function countBirthdayCustomersForMonth(Collection $linkedCustomerIds): int
    {
        if ($linkedCustomerIds->isEmpty() || !$this->hasColumn('users', 'data_nascimento')) {
            return 0;
        }

        return User::query()
            ->whereIn('id', $linkedCustomerIds->all())
            ->whereMonth('data_nascimento', now()->month)
            ->count();
    }

    private function countBonusAdesaoResgates(int $empresaId): int
    {
        $table = $this->bonusAdesaoTable();
        if ($table === null) {
            return 0;
        }

        $query = DB::table($table)->where('empresa_id', $empresaId);
        if ($this->hasColumn($table, 'status')) {
            $query->where('status', 'redeemed');
        } elseif ($this->hasColumn($table, 'resgatado')) {
            $this->applyTruthyFilter($query, $table, 'resgatado', 'resgatado');
        }

        return (int) $query->count();
    }

    private function sumLoyaltyPoints(int $empresaId, string $type): int
    {
        if (!$this->hasTable('cartoes_fidelidade_movimentos')) {
            return 0;
        }

        return (int) CartaoFidelidadeMovimento::query()
            ->where('empresa_id', $empresaId)
            ->where('tipo', $type)
            ->sum('pontos');
    }

    private function countLoyaltyRedemptions(int $empresaId): int
    {
        if (!$this->hasTable('cartoes_fidelidade_movimentos')) {
            return 0;
        }

        return (int) CartaoFidelidadeMovimento::query()
            ->where('empresa_id', $empresaId)
            ->where('tipo', CartaoFidelidadeMovimento::TYPE_REDEEMED)
            ->count();
    }

    private function countPromotions(int $empresaId): int
    {
        if (!$this->hasTable('promocoes')) {
            return 0;
        }

        return (int) Promocao::query()->where('empresa_id', $empresaId)->count();
    }

    private function countPromotionRedemptions(int $empresaId): int
    {
        if (!$this->hasTable('promocao_resgates')) {
            return 0;
        }

        return (int) PromocaoResgate::query()->where('empresa_id', $empresaId)->count();
    }

    private function countSentNotifications(int $empresaId): int
    {
        if (!$this->hasTable('notificacoes_push')) {
            return 0;
        }

        $query = NotificacaoPush::query()->where('empresa_id', $empresaId);

        $hasStatus = $this->hasColumn('notificacoes_push', 'status');
        $hasEnviado = $this->hasColumn('notificacoes_push', 'enviado');

        if ($hasStatus && $hasEnviado) {
            $query->where(function ($builder) {
                $builder->where('status', 'sent')
                    ->orWhere(function ($statusless) {
                        $this->applyTruthyFilter($statusless, 'notificacoes_push', 'enviado', 'enviado');
                    });
            });
        } elseif ($hasStatus) {
            $query->where('status', 'sent');
        } elseif ($hasEnviado) {
            $this->applyTruthyFilter($query, 'notificacoes_push', 'enviado', 'enviado');
        }

        return (int) $query->count();
    }

    private function countLinkedCustomersWithPush(Collection $linkedCustomerIds): int
    {
        if ($linkedCustomerIds->isEmpty() || !$this->hasTable('push_subscriptions')) {
            return 0;
        }

        $query = PushSubscription::query()
            ->whereIn('user_id', $linkedCustomerIds->all());

        if ($this->hasColumn('push_subscriptions', 'revoked_at')) {
            $query->whereNull('revoked_at');
        }

        return (int) $query
            ->distinct()
            ->count('user_id');
    }

    private function linkedCustomerPushMap(iterable $customerIds): array
    {
        $ids = collect($customerIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty() || !$this->hasTable('push_subscriptions')) {
            return [];
        }

        $updatedAtColumn = $this->hasColumn('push_subscriptions', 'updated_at')
            ? 'updated_at'
            : ($this->hasColumn('push_subscriptions', 'created_at') ? 'created_at' : null);

        $query = PushSubscription::query()
            ->whereIn('user_id', $ids->all());

        if ($this->hasColumn('push_subscriptions', 'revoked_at')) {
            $query->whereNull('revoked_at');
        }

        return $query
            ->select('user_id')
            ->selectRaw('COUNT(*) as total_subscriptions')
            ->when($updatedAtColumn, fn ($builder) => $builder->selectRaw("MAX({$updatedAtColumn}) as last_seen_at"))
            ->groupBy('user_id')
            ->get()
            ->mapWithKeys(function ($row) {
                return [
                    (int) $row->user_id => [
                        'has_active_subscription' => (int) ($row->total_subscriptions ?? 0) > 0,
                        'total_subscriptions' => (int) ($row->total_subscriptions ?? 0),
                        'last_seen_at' => !empty($row->last_seen_at)
                            ? Carbon::parse((string) $row->last_seen_at)->toIso8601String()
                            : null,
                    ],
                ];
            })
            ->all();
    }

    private function latestNotificationSentAt(int $empresaId): ?string
    {
        if (!$this->hasTable('notificacoes_push')) {
            return null;
        }

        $dateColumn = $this->hasColumn('notificacoes_push', 'data_envio')
            ? 'data_envio'
            : ($this->hasColumn('notificacoes_push', 'created_at') ? 'created_at' : null);

        if ($dateColumn === null) {
            return null;
        }

        $query = NotificacaoPush::query()->where('empresa_id', $empresaId);

        $hasStatus = $this->hasColumn('notificacoes_push', 'status');
        $hasEnviado = $this->hasColumn('notificacoes_push', 'enviado');

        if ($hasStatus && $hasEnviado) {
            $query->where(function ($builder) {
                $builder->where('status', 'sent')
                    ->orWhere(function ($statusless) {
                        $this->applyTruthyFilter($statusless, 'notificacoes_push', 'enviado', 'enviado');
                    });
            });
        } elseif ($hasStatus) {
            $query->where('status', 'sent');
        } elseif ($hasEnviado) {
            $this->applyTruthyFilter($query, 'notificacoes_push', 'enviado', 'enviado');
        }

        $sentAt = $query->max($dateColumn);

        return $sentAt ? Carbon::parse((string) $sentAt)->toIso8601String() : null;
    }

    private function countReviews(int $empresaId): int
    {
        if (!$this->hasTable('avaliacoes')) {
            return 0;
        }

        return (int) Avaliacao::query()->where('empresa_id', $empresaId)->count();
    }

    private function averageReviews(int $empresaId): float
    {
        if (!$this->hasTable('avaliacoes')) {
            return 0.0;
        }

        return round((float) Avaliacao::query()->where('empresa_id', $empresaId)->avg('estrelas'), 1);
    }

    private function aggregateRedemptionsByCompany(): array
    {
        $totals = [];

        if ($this->hasTable('promocao_resgates')) {
            foreach (PromocaoResgate::query()
                ->selectRaw('empresa_id, COUNT(*) as total')
                ->groupBy('empresa_id')
                ->get() as $row) {
                $totals[(int) $row->empresa_id] = ($totals[(int) $row->empresa_id] ?? 0) + (int) $row->total;
            }
        }

        $table = $this->bonusAdesaoTable();
        if ($table !== null) {
            $query = DB::table($table)->selectRaw('empresa_id, COUNT(*) as total')->groupBy('empresa_id');
            if ($this->hasColumn($table, 'status')) {
                $query->where('status', 'redeemed');
            } elseif ($this->hasColumn($table, 'resgatado')) {
                $this->applyTruthyFilter($query, $table, 'resgatado', 'resgatado');
            }
            foreach ($query->get() as $row) {
                $totals[(int) $row->empresa_id] = ($totals[(int) $row->empresa_id] ?? 0) + (int) $row->total;
            }
        }

        if ($this->hasTable('bonus_aniversario_resgates')) {
            foreach (BonusAniversarioResgate::query()
                ->selectRaw('empresa_id, COUNT(*) as total')
                ->groupBy('empresa_id')
                ->get() as $row) {
                $totals[(int) $row->empresa_id] = ($totals[(int) $row->empresa_id] ?? 0) + (int) $row->total;
            }
        }

        if ($this->hasTable('cartoes_fidelidade_movimentos')) {
            foreach (CartaoFidelidadeMovimento::query()
                ->selectRaw('empresa_id, COUNT(*) as total')
                ->where('tipo', CartaoFidelidadeMovimento::TYPE_REDEEMED)
                ->groupBy('empresa_id')
                ->get() as $row) {
                $totals[(int) $row->empresa_id] = ($totals[(int) $row->empresa_id] ?? 0) + (int) $row->total;
            }
        }

        return $totals;
    }

    private function bonusAdesaoUserAggregates(int $empresaId, array $customerIds): Collection
    {
        $table = $this->bonusAdesaoTable();
        if ($table === null || $customerIds === []) {
            return collect();
        }

        $dateColumn = $this->hasColumn($table, 'redeemed_at') ? 'redeemed_at' : 'data_resgate';

        $query = DB::table($table)
            ->selectRaw("user_id, COUNT(*) as total_bonus_adesao_resgatados, MAX({$dateColumn}) as ultimo_bonus_adesao")
            ->where('empresa_id', $empresaId)
            ->whereIn('user_id', $customerIds)
            ->groupBy('user_id');

        if ($this->hasColumn($table, 'status')) {
            $query->where('status', 'redeemed');
        } elseif ($this->hasColumn($table, 'resgatado')) {
            $this->applyTruthyFilter($query, $table, 'resgatado', 'resgatado');
        }

        return $query->get()->keyBy('user_id');
    }

    private function bonusAdesaoTable(): ?string
    {
        $table = (new BonusAdesaoResgate())->getTable();

        return $this->hasTable($table) ? $table : null;
    }

    private function countUsersByPerfil(string $perfil): int
    {
        if (!$this->hasTable('users')) {
            return 0;
        }

        $roleColumn = $this->resolveUsersRoleColumn();
        if ($roleColumn === null) {
            return 0;
        }

        return (int) DB::table('users')
            ->whereRaw("LOWER({$roleColumn}) = ?", [strtolower($perfil)])
            ->count();
    }

    private function resolveUsersRoleColumn(): ?string
    {
        foreach (['perfil', 'role', 'tipo'] as $column) {
            if ($this->hasColumn('users', $column)) {
                return $column;
            }
        }

        return null;
    }

    private function resolveLastVisit(
        mixed $ultimaVisita,
        mixed $ultimaMovimentacao,
        mixed $ultimoCheckin,
        mixed $dataInscricao
    ): ?Carbon {
        foreach ([$ultimaVisita, $ultimaMovimentacao, $ultimoCheckin, $dataInscricao] as $value) {
            if (!$value) {
                continue;
            }

            return $value instanceof Carbon ? $value->copy() : Carbon::parse((string) $value);
        }

        return null;
    }

    private function daysInactive(?Carbon $lastVisit, ?Carbon $enrolledAt): int
    {
        if ($lastVisit instanceof Carbon) {
            return $lastVisit->copy()->startOfDay()->diffInDays(now()->startOfDay());
        }

        if ($enrolledAt instanceof Carbon) {
            return $enrolledAt->copy()->startOfDay()->diffInDays(now()->startOfDay());
        }

        return 0;
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

    private function applyTruthyFilter($query, string $table, string $qualifiedColumn, string $plainColumn = 'ativo'): void
    {
        if ($this->isBooleanColumn($table, $plainColumn)) {
            if (DB::connection()->getDriverName() === 'pgsql') {
                $query->whereRaw($qualifiedColumn . ' = true');
            } else {
                $query->where($qualifiedColumn, true);
            }

            return;
        }

        $query->whereIn($qualifiedColumn, [1, '1', true, 'true', 'ativo', 'ativa', 'active']);
    }
}
