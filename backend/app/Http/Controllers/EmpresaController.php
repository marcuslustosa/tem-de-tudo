<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Models\Empresa;
use App\Models\CheckIn;
use App\Models\QRCode;
use App\Models\User;
use App\Services\BonusAdesaoService;
use App\Services\BonusAniversarioService;
use App\Services\CartaoFidelidadeService;
use App\Services\LembreteRetornoService;

class EmpresaController extends Controller
{
    // Cache estrutura do banco por 1 hora (3600s) - evita overhead em produção
    private function hasEmpresasTable(): bool
    {
        try {
            return Schema::hasTable('empresas');
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
                $query->where('ativo', true);
            } else {
                $query->whereIn('ativo', [1, '1', true, 'true', 'ativo']);
            }
        }

        if ($this->hasColumn('empresas', 'status')) {
            Empresa::applyOperationalStatusFilter($query, Empresa::STATUS_ACTIVE, 'empresas.status');
        }

        return $query;
    }

    private function demoEmpresasFromUsers(): array
    {
        $defaultRows = $this->defaultDemoEmpresas();

        if (!Schema::hasTable('users')) {
            return $defaultRows;
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

        $fromUsers = collect($empresaUsers)->values()->map(function ($u, $idx) use ($defaults) {
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

        if (count($fromUsers) >= 10) {
            return $fromUsers;
        }

        $knownNames = array_map(fn ($item) => strtolower((string) ($item['nome'] ?? '')), $fromUsers);
        foreach ($defaultRows as $row) {
            $key = strtolower((string) ($row['nome'] ?? ''));
            if (in_array($key, $knownNames, true)) {
                continue;
            }
            $fromUsers[] = $row;
            if (count($fromUsers) >= 10) {
                break;
            }
        }

        return $fromUsers;
    }

    private function defaultDemoEmpresas(): array
    {
        $base = [
            [
                'nome' => 'Malagueta Galpao',
                'descricao' => 'Galpao gastronomico com almoco executivo, happy hour e fidelizacao por QR Code.',
                'categoria' => 'Restaurante',
                'ramo' => 'restaurante',
                'endereco' => 'Rua do Mercado, 128 - Centro, Sao Paulo - SP',
                'telefone' => '(11) 4002-1101',
                'whatsapp' => '(11) 98888-2101',
                'instagram' => '@malaguetagalpao',
                'facebook' => 'malaguetagalpao',
                'email' => 'malagueta@demo.local',
                'logo' => '/assets/images/company1.jpg',
                'avaliacao_media' => 4.7,
                'total_avaliacoes' => 3,
            ],
            [
                'nome' => 'Texano Burger',
                'descricao' => 'Hamburguer artesanal, combos semanais e recompensas presenciais no balcao.',
                'categoria' => 'Hamburgueria',
                'ramo' => 'hamburgueria',
                'endereco' => 'Av. Paulista, 940 - Bela Vista, Sao Paulo - SP',
                'telefone' => '(11) 4002-1102',
                'whatsapp' => '(11) 98888-2102',
                'instagram' => '@texanoburger',
                'facebook' => 'texanoburger',
                'email' => 'texano@demo.local',
                'logo' => '/assets/images/company2.jpg',
                'avaliacao_media' => 4.5,
                'total_avaliacoes' => 2,
            ],
            [
                'nome' => 'Makoto Sushi',
                'descricao' => 'Sushi bar com promocoes ativas, fidelidade e bonus de aniversario do mes.',
                'categoria' => 'Japonesa',
                'ramo' => 'japonesa',
                'endereco' => 'Rua Harmonia, 55 - Vila Madalena, Sao Paulo - SP',
                'telefone' => '(11) 4002-1103',
                'whatsapp' => '(11) 98888-2103',
                'instagram' => '@makotosushi',
                'facebook' => 'makotosushi',
                'email' => 'makoto@demo.local',
                'logo' => '/assets/images/company3.jpg',
                'avaliacao_media' => 4.0,
                'total_avaliacoes' => 1,
            ],
            [
                'nome' => 'Florenza Boutique',
                'descricao' => 'Boutique com beneficios recorrentes, mimo de aniversario e campanhas sazonais.',
                'categoria' => 'Moda/Beleza',
                'ramo' => 'moda',
                'endereco' => 'Alameda das Flores, 210 - Jardins, Sao Paulo - SP',
                'telefone' => '(11) 4002-1104',
                'whatsapp' => '(11) 98888-2104',
                'instagram' => '@florenzaboutique',
                'facebook' => 'florenzaboutique',
                'email' => 'florenza@demo.local',
                'logo' => '/assets/images/company4.jpg',
                'avaliacao_media' => 5.0,
                'total_avaliacoes' => 3,
            ],
            ['nome' => 'Padaria Pao Quentinho', 'categoria' => 'Padaria', 'ramo' => 'padaria'],
            ['nome' => 'Mercado Bom Preco', 'categoria' => 'Mercado', 'ramo' => 'mercado'],
            ['nome' => 'Pet Shop Amigo Fiel', 'categoria' => 'Petshop', 'ramo' => 'pet_shop'],
            ['nome' => 'Farmacia Saude Mais', 'categoria' => 'Farmacia', 'ramo' => 'farmacia'],
        ];

        return collect($base)->values()->map(function ($row, $idx) {
            return [
                'id' => $idx + 1,
                'nome' => $row['nome'],
                'descricao' => $row['descricao'] ?? 'Estabelecimento ativo no programa de fidelidade.',
                'categoria' => $row['categoria'],
                'ramo' => $row['ramo'],
                'endereco' => $row['endereco'] ?? ('Rua Demo, ' . (200 + $idx) . ' - Sao Paulo, SP'),
                'telefone' => $row['telefone'] ?? sprintf('(11) 9%04d-%04d', 5100 + $idx, 6100 + $idx),
                'whatsapp' => $row['whatsapp'] ?? sprintf('(11) 9%04d-%04d', 7100 + $idx, 8100 + $idx),
                'instagram' => $row['instagram'] ?? ('@empresa_demo_' . ($idx + 1)),
                'facebook' => $row['facebook'] ?? ('empresa.demo.' . ($idx + 1)),
                'email' => $row['email'] ?? ('contato' . ($idx + 1) . '@demo.com'),
                'logo' => $row['logo'] ?? ('/assets/images/company' . (($idx % 4) + 1) . '.jpg'),
                'points_multiplier' => 1 + (($idx % 3) * 0.25),
                'avaliacao_media' => $row['avaliacao_media'] ?? 0,
                'total_avaliacoes' => $row['total_avaliacoes'] ?? 0,
                'public_page_url' => '/detalhe_do_parceiro.html?id=' . ($idx + 1),
                'publicamente_visivel' => true,
                'status' => Empresa::STATUS_ACTIVE,
                'cartao_fidelidade' => null,
                'bonus_aniversario' => null,
            ];
        })->toArray();
    }

    private function demoEmpresaById(int $id): ?array
    {
        return collect($this->demoEmpresasFromUsers())
            ->first(fn (array $empresa) => (int) ($empresa['id'] ?? 0) === $id);
    }

    private function cleanUtf8($value)
    {
        if (!is_string($value)) {
            return $value;
        }
        $clean = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
        $clean = $clean === false ? '' : $clean;

        if ($clean === '' || !preg_match('/[\x{00C3}\x{00E2}\x{FFFD}\x{251C}]/u', $clean)) {
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

    private function serializePublicEmpresa(
        Empresa $empresa,
        bool $includeLoyaltyCard = false,
        bool $includeBirthdayBonus = false,
        bool $includeAdhesionBonus = false,
        bool $includeReturnReminder = false
    ): array
    {
        $payload = [
            'id' => $empresa->id,
            'nome' => $this->cleanUtf8($empresa->nome),
            'descricao' => $this->cleanUtf8($empresa->descricao ?? ''),
            'categoria' => $this->cleanUtf8($empresa->categoria ?? $empresa->ramo ?? ''),
            'ramo' => $this->cleanUtf8($empresa->ramo ?? $empresa->categoria ?? ''),
            'endereco' => $this->cleanUtf8($empresa->endereco ?? ''),
            'telefone' => $this->cleanUtf8($empresa->telefone ?? ''),
            'email' => $this->cleanUtf8($empresa->email ?? ''),
            'whatsapp' => $this->cleanUtf8($empresa->whatsapp ?? ''),
            'instagram' => $this->cleanUtf8($empresa->instagram ?? ''),
            'facebook' => $this->cleanUtf8($empresa->facebook ?? ''),
            'logo' => $this->cleanUtf8($empresa->logo ?? '/assets/images/company1.jpg'),
            'points_multiplier' => $empresa->points_multiplier ?? 1,
            'avaliacao_media' => (float) ($empresa->avaliacao_media ?? 0),
            'total_avaliacoes' => (int) ($empresa->total_avaliacoes ?? 0),
            'public_page_url' => '/detalhe_do_parceiro.html?id=' . $empresa->id,
            'publicamente_visivel' => $empresa->isPubliclyVisible(),
            'status' => $empresa->operationalStatus(),
        ];

        if ($includeLoyaltyCard) {
            try {
                $loyaltyService = app(CartaoFidelidadeService::class);
                $card = $loyaltyService->activeCompanyCard($empresa)
                    ?? $loyaltyService->latestCompanyCard($empresa);

                $payload['cartao_fidelidade'] = $card
                    ? $loyaltyService->serializeCard($card)
                    : null;
            } catch (\Throwable $e) {
                Log::warning('Falha ao serializar cartao fidelidade publico da empresa', [
                    'empresa_id' => $empresa->id,
                    'error' => $e->getMessage(),
                ]);
                $payload['cartao_fidelidade'] = null;
            }
        }

        if ($includeBirthdayBonus) {
            try {
                $bonusService = app(BonusAniversarioService::class);
                $birthdayBonus = $bonusService->activeCompanyBonus($empresa)
                    ?? $bonusService->latestCompanyBonus($empresa);

                $payload['bonus_aniversario'] = $birthdayBonus
                    ? $bonusService->serializeBonus($birthdayBonus)
                    : null;
            } catch (\Throwable $e) {
                Log::warning('Falha ao serializar bonus aniversario publico da empresa', [
                    'empresa_id' => $empresa->id,
                    'error' => $e->getMessage(),
                ]);
                $payload['bonus_aniversario'] = null;
            }
        }

        if ($includeAdhesionBonus) {
            try {
                $bonusAdesaoService = app(BonusAdesaoService::class);
                $adhesionBonus = $bonusAdesaoService->activeCompanyBonus($empresa)
                    ?? $bonusAdesaoService->latestCompanyBonus($empresa);

                $payload['bonus_adesao'] = $adhesionBonus
                    ? $bonusAdesaoService->serializeBonus($adhesionBonus)
                    : null;
            } catch (\Throwable $e) {
                Log::warning('Falha ao serializar bonus adesao publico da empresa', [
                    'empresa_id' => $empresa->id,
                    'error' => $e->getMessage(),
                ]);
                $payload['bonus_adesao'] = null;
            }
        }

        if ($includeReturnReminder) {
            try {
                $reminderService = app(LembreteRetornoService::class);
                $reminder = $reminderService->activeCompanyReminder($empresa)
                    ?? $reminderService->latestCompanyReminder($empresa);

                $payload['lembrete_retorno'] = $reminder
                    ? $reminderService->serializeReminder($reminder)
                    : null;
            } catch (\Throwable $e) {
                Log::warning('Falha ao serializar lembrete de retorno publico da empresa', [
                    'empresa_id' => $empresa->id,
                    'error' => $e->getMessage(),
                ]);
                $payload['lembrete_retorno'] = null;
            }
        }

        return $payload;
    }

    public function index()
    {
        try {
            if (!$this->hasEmpresasTable() || !$this->hasColumn('empresas', 'nome')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Catalogo de empresas indisponivel.',
                    'data' => [],
                ], 503, ['Content-Type' => 'application/json; charset=UTF-8'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
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

            return response()->json([
                'success' => true,
                'data' => $empresas,
            ], 200, ['Content-Type' => 'application/json; charset=UTF-8'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        } catch (\Exception $e) {
            Log::error('Erro ao listar empresas: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Nao foi possivel carregar o catalogo de empresas agora.',
                'data' => [],
            ], 500, ['Content-Type' => 'application/json; charset=UTF-8'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        }
    }

    /**
     * Listar empresas para cadastro de funcionários (público) + busca
     */
    public function listEmpresas(Request $request)
    {
        try {
            // Cache key baseado nos parâmetros da busca
            if (!$this->hasEmpresasTable() || !$this->hasColumn('empresas', 'nome')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Catalogo de empresas indisponivel.',
                    'data' => [],
                ], 503, ['Content-Type' => 'application/json; charset=UTF-8'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
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
            foreach (['whatsapp', 'instagram', 'facebook', 'avaliacao_media', 'total_avaliacoes'] as $column) {
                if (Schema::hasColumn('empresas', $column)) {
                    $select[] = $column;
                }
            }

            $empresas = $query
                ->select($select)
                ->when($hasCategoria, fn ($q) => $q->addSelect('categoria'))
                ->when($hasRamo, fn ($q) => $q->addSelect('ramo'))
                ->orderBy('nome')
                ->get();

            $mapped = $empresas->map(function($empresa) {
                return $this->serializePublicEmpresa($empresa);
            })->values();

            return response()->json([
                'success' => true,
                'data' => $mapped
            ], 200, ['Content-Type' => 'application/json; charset=UTF-8'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            
        } catch (\Exception $e) {
            Log::error('Erro ao listar empresas: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Nao foi possivel carregar empresas agora.',
                'data' => [],
            ], 500, ['Content-Type' => 'application/json; charset=UTF-8'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        }
    }

    public function getEmpresa($id)
    {
        try {
            if (!$this->hasEmpresasTable() || !$this->hasColumn('empresas', 'nome')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Catalogo de empresas indisponivel.',
                ], 503);
            }

            $empresa = Empresa::query()
                ->publiclyVisible()
                ->find((int) $id);

            if (!$empresa) {
                return response()->json([
                    'success' => false,
                    'message' => 'Estabelecimento nao encontrado.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $this->serializePublicEmpresa($empresa, true, true, true, true),
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

    public function getEmpresaByQrCode(string $code)
    {
        try {
            $qrCode = QRCode::query()
                ->with('empresa')
                ->where('code', $code)
                ->when($this->hasColumn('qr_codes', 'active'), fn ($query) => $query->where('active', true))
                ->first();

            if (!$qrCode || !$qrCode->empresa || !$qrCode->empresa->isPubliclyVisible()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Estabelecimento indisponivel para vinculacao.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'code' => $qrCode->code,
                    'scan_url' => app(\App\Services\QRCodeService::class)->getCompanyScanUrl($qrCode),
                    'link_page_url' => '/vincular_empresa.html?code=' . rawurlencode($qrCode->code),
                    'empresa' => $this->serializePublicEmpresa($qrCode->empresa, true, true, true, true),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Erro ao resolver empresa por QR code', [
                'code' => $code,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Nao foi possivel resolver o QR Code da empresa.',
            ], 500);
        }
    }

    public function getEmpresaPromocoes($id)
    {
        try {
            if (!Schema::hasTable('promocoes') || !Schema::hasColumn('promocoes', 'empresa_id')) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                ]);
            }

            $empresa = Empresa::query()->publiclyVisible()->find((int) $id);
            if (!$empresa) {
                return response()->json([
                    'success' => false,
                    'message' => 'Empresa indisponivel publicamente.',
                    'data' => [],
                ], 404);
            }

            $promocoes = app(\App\Services\PromocaoInstantaneaService::class)->publicPromotions($empresa);

            return response()->json([
                'success' => true,
                'data' => $promocoes,
            ], 200, ['Content-Type' => 'application/json; charset=UTF-8'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        } catch (\Throwable $e) {
            Log::error('Erro ao carregar promocoes publicas da empresa', [
                'empresa_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Nao foi possivel carregar as promocoes desta empresa.',
                'data' => [],
            ], 500, ['Content-Type' => 'application/json; charset=UTF-8'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
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
                ->whereIn('tipo', ['earn', 'ganho'])
                ->sum('pontos');

            $qrcodesAtivos = QRCode::query()
                ->where('empresa_id', $empresa->id)
                ->where('active', true)
                ->count();

            $checkinsHoje = CheckIn::query()
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

            $checkins = CheckIn::query()
                ->with(['user:id,name', 'qrCode:id,name'])
                ->where('empresa_id', $empresa->id)
                ->orderByDesc('created_at')
                ->limit(10)
                ->get()
                ->map(function (CheckIn $checkin) {
                    return [
                        'id' => $checkin->id,
                        'cliente_nome' => $checkin->user->name ?? 'Cliente',
                        'qr_nome' => $checkin->qrCode->name ?? 'QR Code',
                        'pontos' => (int) ($checkin->pontos ?? $checkin->pontos_ganhos ?? 0),
                        'created_at' => $checkin->created_at,
                    ];
                });

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

    public function adminIndex(Request $request)
    {
        try {
            if (!$this->hasEmpresasTable()) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'empresas' => [],
                        'summary' => $this->emptyAdminCompanySummary(),
                    ],
                ]);
            }

            $status = strtolower(trim((string) $request->input('status', 'todos')));
            $categoria = strtolower(trim((string) $request->input('categoria', 'todas')));
            $search = strtolower(trim((string) $request->input('search', $request->input('busca', ''))));

            $query = Empresa::query()
                ->with(['owner:id,name,email,telefone,status'])
                ->withCount('qrCodes');

            if ($status !== '' && !in_array($status, ['todos', 'all'], true)) {
                    Empresa::applyOperationalStatusFilter($query, $status, 'empresas.status');
                }

            if ($categoria !== '' && !in_array($categoria, ['todas', 'todos', 'all'], true)) {
                if ($this->hasColumn('empresas', 'categoria')) {
                    $query->whereRaw('LOWER(categoria) = ?', [$categoria]);
                } elseif ($this->hasColumn('empresas', 'ramo')) {
                    $query->whereRaw('LOWER(ramo) = ?', [$categoria]);
                }
            }

            if ($search !== '') {
                $query->where(function ($companyQuery) use ($search) {
                    $companyQuery->whereRaw('LOWER(nome) LIKE ?', ["%{$search}%"]);

                    if ($this->hasColumn('empresas', 'cnpj')) {
                        $companyQuery->orWhereRaw('LOWER(cnpj) LIKE ?', ["%{$search}%"]);
                    }
                    if ($this->hasColumn('empresas', 'telefone')) {
                        $companyQuery->orWhereRaw('LOWER(telefone) LIKE ?', ["%{$search}%"]);
                    }
                    if ($this->hasColumn('empresas', 'whatsapp')) {
                        $companyQuery->orWhereRaw('LOWER(whatsapp) LIKE ?', ["%{$search}%"]);
                    }

                    $companyQuery->orWhereHas('owner', function ($ownerQuery) use ($search) {
                        $ownerQuery
                            ->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                            ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"]);
                    });
                });
            }

            $empresas = $query->get()
                ->sortBy(fn (Empresa $empresa) => sprintf(
                    '%02d-%s',
                    $this->companyStatusOrder($empresa->operationalStatus()),
                    strtolower((string) $empresa->nome)
                ))
                ->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'empresas' => $empresas->map(fn (Empresa $empresa) => $this->serializeAdminEmpresa($empresa))->all(),
                    'summary' => $this->buildAdminCompanySummary(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Erro ao listar empresas no painel admin', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar estabelecimentos.',
            ], 500);
        }
    }

    public function approve(int $id)
    {
        return $this->transitionOperationalStatus($id, Empresa::STATUS_ACTIVE, 'ativo');
    }

    public function reject(int $id)
    {
        return $this->transitionOperationalStatus($id, Empresa::STATUS_REJECTED, 'inativo');
    }

    public function suspend(int $id)
    {
        return $this->transitionOperationalStatus($id, Empresa::STATUS_SUSPENDED, 'bloqueado');
    }

    public function adminQrCode(int $id)
    {
        $empresa = Empresa::query()->withCount('qrCodes')->findOrFail($id);
        $service = app(\App\Services\QRCodeService::class);
        $qrCode = $empresa->qrCodes()->first();

        if (!$qrCode && $empresa->operationalStatus() !== Empresa::STATUS_ACTIVE) {
            return response()->json([
                'success' => false,
                'message' => 'QR Code liberado apenas para empresas ativas.',
            ], 409);
        }

        if (!$qrCode) {
            $qrCode = $service->gerarQRCodeEmpresa($empresa);
            $empresa->refresh()->loadCount('qrCodes');
        }

        return response()->json([
            'success' => true,
            'data' => [
                'empresa' => $this->serializeAdminEmpresa($empresa),
                'qr_code' => [
                    'id' => $qrCode->id,
                    'code' => $qrCode->code,
                    'active' => (bool) $qrCode->active,
                    'usage_count' => (int) ($qrCode->usage_count ?? 0),
                    'last_used_at' => optional($qrCode->last_used_at)->toIso8601String(),
                    'scan_url' => $service->getCompanyScanUrl($qrCode),
                    'qr_url' => $service->getQRCodeUrl($qrCode),
                    'qr_image' => $service->getQRCodeImageDataUrl($qrCode),
                ],
            ],
        ]);
    }

    private function transitionOperationalStatus(int $id, string $status, string $ownerStatus)
    {
        $empresa = Empresa::query()->with(['owner'])->withCount('qrCodes')->findOrFail($id);
        $shouldBeActive = $status === Empresa::STATUS_ACTIVE;

        $empresa->update([
            'ativo' => $shouldBeActive,
            'status' => $status,
        ]);

        $empresa->refresh()->loadMissing(['owner'])->loadCount('qrCodes');
        $this->syncCompanyOwnerAccess($empresa->owner, $ownerStatus, $shouldBeActive);

        if ($status === Empresa::STATUS_ACTIVE) {
            app(\App\Services\QRCodeService::class)->gerarQRCodeEmpresa($empresa);
            $empresa->refresh()->loadMissing(['owner'])->loadCount('qrCodes');
        }

        Log::info('Status operacional de empresa atualizado pelo admin', [
            'empresa_id' => $empresa->id,
            'novo_status' => $status,
            'admin_id' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $this->companyStatusMessage($status),
            'data' => $this->serializeAdminEmpresa($empresa),
        ]);
    }

    private function syncCompanyOwnerAccess(?User $owner, string $status, bool $isActive): void
    {
        if (!$owner) {
            return;
        }

        $payload = [];
        if (Schema::hasColumn('users', 'status')) {
            $payload['status'] = $status;
        }
        if (Schema::hasColumn('users', 'is_active')) {
            $payload['is_active'] = $isActive;
        }

        if ($payload !== []) {
            User::query()->whereKey($owner->id)->update($payload);
        }
    }

    private function serializeAdminEmpresa(Empresa $empresa): array
    {
        $owner = $empresa->owner;

        return [
            'id' => $empresa->id,
            'nome' => $this->cleanUtf8($empresa->nome),
            'categoria' => $this->cleanUtf8($empresa->categoria ?? $empresa->ramo ?? 'Sem categoria'),
            'ramo' => $this->cleanUtf8($empresa->ramo ?? $empresa->categoria ?? 'Sem categoria'),
            'endereco' => $this->cleanUtf8($empresa->endereco ?? 'Endereco nao informado'),
            'telefone' => $this->cleanUtf8($empresa->telefone ?? '-'),
            'whatsapp' => $this->cleanUtf8($empresa->whatsapp ?? ''),
            'email' => $this->cleanUtf8($owner->email ?? '-'),
            'responsavel' => $this->cleanUtf8($owner->name ?? '-'),
            'responsavel_status' => $this->cleanUtf8($owner->status ?? ''),
            'cnpj' => $this->cleanUtf8($empresa->cnpj ?? ''),
            'logo' => $this->cleanUtf8($empresa->logo ?? '/assets/images/company1.jpg'),
            'status' => $empresa->operationalStatus(),
            'ativo' => (bool) $empresa->ativo,
            'publicamente_visivel' => $empresa->isPubliclyVisible(),
            'qr_code_ready' => (int) ($empresa->qr_codes_count ?? 0) > 0,
            'created_at' => optional($empresa->created_at)->toISOString(),
            'updated_at' => optional($empresa->updated_at)->toISOString(),
        ];
    }

    private function buildAdminCompanySummary(): array
    {
        if (!$this->hasEmpresasTable()) {
            return $this->emptyAdminCompanySummary();
        }

        $summary = $this->emptyAdminCompanySummary();
        Empresa::query()->get(['status', 'ativo'])->each(function (Empresa $empresa) use (&$summary) {
            $status = $empresa->operationalStatus();
            $summary['total']++;
            if (array_key_exists($status, $summary)) {
                $summary[$status]++;
            }
            if ($empresa->isPubliclyVisible()) {
                $summary['publicas']++;
            }
        });

        return $summary;
    }

    private function emptyAdminCompanySummary(): array
    {
        return [
            'total' => 0,
            'pending' => 0,
            'active' => 0,
            'suspended' => 0,
            'rejected' => 0,
            'publicas' => 0,
        ];
    }

    private function companyStatusOrder(string $status): int
    {
        return match ($status) {
            Empresa::STATUS_PENDING => 0,
            Empresa::STATUS_ACTIVE => 1,
            Empresa::STATUS_SUSPENDED => 2,
            Empresa::STATUS_REJECTED => 3,
            default => 9,
        };
    }

    private function companyStatusMessage(string $status): string
    {
        return match ($status) {
            Empresa::STATUS_ACTIVE => 'Empresa aprovada e ativada com sucesso.',
            Empresa::STATUS_REJECTED => 'Empresa rejeitada com sucesso.',
            Empresa::STATUS_SUSPENDED => 'Empresa suspensa com sucesso.',
            default => 'Status da empresa atualizado com sucesso.',
        };
    }

    /**
     * Ativar ou desativar uma empresa (admin)
     * PATCH /admin/empresas/{id}/toggle-status
     */
    public function toggleStatus(int $id)
    {
        $empresa = \App\Models\Empresa::query()->with('owner')->findOrFail($id);
        $novoStatus = !$empresa->ativo;
        $operationalStatus = $novoStatus ? Empresa::STATUS_ACTIVE : Empresa::STATUS_SUSPENDED;
        $empresa->update([
            'ativo' => $novoStatus,
            'status' => $operationalStatus,
        ]);
        $this->syncCompanyOwnerAccess($empresa->owner, $novoStatus ? 'ativo' : 'bloqueado', $novoStatus);
        if ($novoStatus) {
            app(\App\Services\QRCodeService::class)->gerarQRCodeEmpresa($empresa->fresh());
        }

        Log::info('Status de empresa alterado pelo admin', [
            'empresa_id' => $id,
            'novo_status' => $operationalStatus,
            'admin_id' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'ativo' => $novoStatus,
            'message' => $novoStatus ? 'Empresa ativada com sucesso.' : 'Empresa suspensa com sucesso.',
        ]);
    }
}
