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
        
        // Pontos totais
        $pontosTotais = DB::table('pontos')
            ->where('user_id', $user->id)
            ->whereNotIn('tipo', ['resgate', 'redeem'])
            ->sum('pontos');
        
        $pontosGastos = DB::table('pontos')
            ->where('user_id', $user->id)
            ->whereIn('tipo', ['resgate', 'redeem'])
            ->sum('pontos');
        
        $saldoPontos = $pontosTotais - $pontosGastos;
        
        // Empresas favoritas (onde tem mais pontos)
        $empresasFavoritas = DB::table('pontos')
            ->join('empresas', 'pontos.empresa_id', '=', 'empresas.id')
            ->select('empresas.*', DB::raw('SUM(pontos.pontos) as total_pontos'))
            ->where('pontos.user_id', $user->id)
            ->whereNotIn('pontos.tipo', ['resgate', 'redeem'])
            ->groupBy('empresas.id')
            ->orderByDesc('total_pontos')
            ->limit(3)
            ->get();
        
        // Ãšltimas transaÃ§Ãµes
        $ultimasTransacoes = DB::table('pontos')
            ->join('empresas', 'pontos.empresa_id', '=', 'empresas.id')
            ->select('pontos.*', 'empresas.nome as empresa_nome')
            ->where('pontos.user_id', $user->id)
            ->orderByDesc('pontos.created_at')
            ->limit(10)
            ->get();
        
        // PromoÃ§Ãµes disponÃ­veis
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
     * Listar todas as empresas disponÃ­veis
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
            $busca = strtolower($request->busca);
            $query->whereRaw('LOWER(nome) LIKE ?', ['%' . $busca . '%']);
        }
        
        $empresas = $query->orderBy('nome')->get();
        
        // Para cada empresa, calcular pontos do usuÃ¡rio
        $user = Auth::user();
        foreach ($empresas as $empresa) {
            $pontos = DB::table('pontos')
                ->where('user_id', $user->id)
                ->where('empresa_id', $empresa->id)
                ->whereNotIn('tipo', ['resgate', 'redeem'])
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
    public function resgatarPromocao(Request $request, $promocaoId)
    {
        $user = Auth::user();
        
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
        $pontosCusto = $loyalty->promotionCost($promocao);
        
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
}




