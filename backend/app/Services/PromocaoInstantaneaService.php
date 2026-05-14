<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\InscricaoEmpresa;
use App\Models\NotificacaoPush;
use App\Models\Promocao;
use App\Models\PromocaoResgate;
use App\Models\PushSubscription;
use App\Models\User;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class PromocaoInstantaneaService
{
    public const WEEKLY_SEND_LIMIT = 2;

    public const LOG_STATUS_PENDING = 'pending';
    public const LOG_STATUS_SENT = 'sent';
    public const LOG_STATUS_FAILED = 'failed';
    public const LOG_STATUS_NO_SUBSCRIPTION = 'no_subscription';

    public function companyPromotions(Empresa $empresa)
    {
        return Promocao::query()
            ->where('empresa_id', $empresa->id)
            ->orderByDesc('ativo')
            ->orderByDesc('created_at');
    }

    public function latestCompanyPromotion(Empresa $empresa): ?Promocao
    {
        return $this->companyPromotions($empresa)->first();
    }

    public function publicPromotions(Empresa $empresa): array
    {
        return $this->companyPromotions($empresa)
            ->get()
            ->filter(fn (Promocao $promocao) => $promocao->isOperationallyAvailable())
            ->map(fn (Promocao $promocao) => $this->serializePromotion($promocao, [
                'viewer_status' => 'available',
                'can_self_redeem' => false,
                'can_present_qr' => false,
                'redeemed_at' => null,
            ]))
            ->values()
            ->all();
    }

    public function customerPromotions(Empresa $empresa, User $customer): array
    {
        $inscricao = $this->findInscricao($empresa, $customer);
        $items = $this->companyPromotions($empresa)
            ->get()
            ->filter(fn (Promocao $promocao) => $promocao->isOperationallyAvailable())
            ->map(fn (Promocao $promocao) => $this->serializePromotionForViewer($promocao, $customer, $inscricao))
            ->values()
            ->all();

        $availableCount = collect($items)->where('viewer_status', 'available')->count();
        $redeemedCount = collect($items)->where('viewer_status', 'redeemed')->count();

        return [
            'status' => $items === []
                ? 'unavailable'
                : ($availableCount > 0 ? 'available' : ($redeemedCount > 0 ? 'redeemed' : 'not_linked')),
            'message' => $this->customerPromotionsMessage($items, $inscricao !== null),
            'items' => $items,
            'available_count' => $availableCount,
            'redeemed_count' => $redeemedCount,
            'can_validate_any' => $availableCount > 0,
        ];
    }

    public function listPromotionsForCustomer(User $customer, ?int $empresaId = null): array
    {
        $inscricaoQuery = InscricaoEmpresa::query()->where('user_id', $customer->id);
        if ($empresaId) {
            $inscricaoQuery->where('empresa_id', $empresaId);
        }

        $linkedCompanies = $inscricaoQuery->pluck('empresa_id')->unique()->values();
        if ($empresaId && $linkedCompanies->isEmpty()) {
            $empresa = Empresa::query()->publiclyVisible()->find($empresaId);
            if (!$empresa) {
                return [];
            }

            return $this->companyPromotions($empresa)
                ->get()
                ->filter(fn (Promocao $promocao) => $promocao->isOperationallyAvailable())
                ->map(fn (Promocao $promocao) => array_merge(
                    $this->serializePromotion($promocao),
                    [
                        'viewer_status' => 'not_linked',
                        'message' => 'Vincule-se a empresa para ficar elegivel a esta promocao.',
                        'can_self_redeem' => false,
                        'can_present_qr' => false,
                        'redeemed_at' => null,
                        'empresa' => [
                            'id' => $empresa->id,
                            'nome' => $empresa->nome,
                            'logo' => $empresa->logo,
                            'categoria' => $empresa->categoria ?? $empresa->ramo ?? null,
                            'public_page_url' => '/detalhe_do_parceiro.html?id=' . $empresa->id,
                        ],
                    ]
                ))
                ->values()
                ->all();
        }

        if ($linkedCompanies->isEmpty()) {
            return [];
        }

        $companies = Empresa::query()
            ->publiclyVisible()
            ->whereIn('id', $linkedCompanies->all())
            ->get()
            ->keyBy('id');

        $items = [];
        foreach ($linkedCompanies as $companyId) {
            $empresa = $companies->get((int) $companyId);
            if (!$empresa) {
                continue;
            }

            $snapshot = $this->customerPromotions($empresa, $customer);
            foreach ($snapshot['items'] as $item) {
                $items[] = array_merge($item, [
                    'empresa' => [
                        'id' => $empresa->id,
                        'nome' => $empresa->nome,
                        'logo' => $empresa->logo,
                        'categoria' => $empresa->categoria ?? $empresa->ramo ?? null,
                        'public_page_url' => '/detalhe_do_parceiro.html?id=' . $empresa->id,
                    ],
                ]);
            }
        }

        return $items;
    }

    public function savePromotion(Empresa $empresa, array $payload, ?Promocao $promocao = null): Promocao
    {
        $titulo = trim((string) ($payload['titulo'] ?? $promocao?->titulo ?? ''));
        $descricao = $this->normalizeNullableString($payload['descricao'] ?? $promocao?->descricao ?? null);
        $imagem = $this->normalizeNullableString($payload['imagem'] ?? $promocao?->imagem ?? null);
        $notificationTitle = $this->normalizeNullableString($payload['notification_title'] ?? $promocao?->notification_title ?? null)
            ?: $titulo;
        $notificationBody = $this->normalizeNullableString($payload['notification_body'] ?? $promocao?->notification_body ?? null)
            ?: Str::limit((string) $descricao, 120, '');
        $ativo = array_key_exists('ativo', $payload)
            ? (bool) $payload['ativo']
            : (bool) ($promocao?->ativo ?? true);
        $validade = $this->normalizeNullableString($payload['validade'] ?? $payload['data_expiracao'] ?? $promocao?->validade ?? $promocao?->data_fim ?? null);
        $desconto = array_key_exists('desconto', $payload)
            ? (float) $payload['desconto']
            : ($promocao?->desconto ?? 0);
        $tipoRecompensa = $this->normalizeNullableString($payload['tipo_recompensa'] ?? $payload['tipo'] ?? $promocao?->tipo_recompensa ?? null);

        if ($imagem === null) {
            throw new DomainException('A promocao instantanea exige imagem.');
        }

        $status = $this->determineLegacyStatus($ativo, $validade);
        $data = [
            'empresa_id' => $empresa->id,
            'titulo' => $titulo,
            'descricao' => $descricao,
            'imagem' => $imagem,
            'ativo' => $status === Promocao::STATUS_ACTIVE ? $ativo : false,
            'status' => $status,
        ];

        if (Schema::hasColumn('promocoes', 'notification_title')) {
            $data['notification_title'] = Str::limit($notificationTitle, 80, '');
        }
        if (Schema::hasColumn('promocoes', 'notification_body')) {
            $data['notification_body'] = Str::limit($notificationBody, 120, '');
        }
        if (Schema::hasColumn('promocoes', 'validade')) {
            $data['validade'] = $validade;
        }
        if (Schema::hasColumn('promocoes', 'data_fim')) {
            $data['data_fim'] = $validade;
        }
        if (Schema::hasColumn('promocoes', 'data_inicio') && !$promocao?->data_inicio) {
            $data['data_inicio'] = now()->toDateString();
        }
        if (Schema::hasColumn('promocoes', 'desconto')) {
            $data['desconto'] = $desconto;
        }
        if (Schema::hasColumn('promocoes', 'tipo_recompensa')) {
            $data['tipo_recompensa'] = $tipoRecompensa;
        }
        if (Schema::hasColumn('promocoes', 'limite_por_usuario')) {
            $data['limite_por_usuario'] = 1;
        }

        if ($promocao) {
            $promocao->update($data);

            return $promocao->refresh();
        }

        return Promocao::query()->create($data);
    }

    public function deletePromotion(Promocao $promocao): void
    {
        $this->deleteStoredImageIfNeeded($promocao->imagem);
        $promocao->delete();
    }

    public function validatePromotion(Empresa $empresa, Promocao $promocao, User $customer, User $validator): array
    {
        $this->guardCompanyPromotionOwnership($empresa, $promocao);

        if (!$promocao->isOperationallyAvailable()) {
            throw new DomainException($promocao->isExpired()
                ? 'Promocao expirada.'
                : 'Promocao inativa ou indisponivel.');
        }

        $inscricao = $this->findInscricao($empresa, $customer);
        if (!$inscricao) {
            throw new DomainException('Cliente nao esta vinculado a esta empresa.');
        }

        if ($this->findRedemption($promocao, $customer)) {
            throw new DomainException('Promocao ja validada para este cliente.');
        }

        $timestamp = now();

        try {
            DB::transaction(function () use ($promocao, $empresa, $customer, $validator, $inscricao, $timestamp): void {
                $duplicate = PromocaoResgate::query()
                    ->where('promocao_id', $promocao->id)
                    ->where('user_id', $customer->id)
                    ->lockForUpdate()
                    ->exists();

                if ($duplicate) {
                    throw new DomainException('Promocao ja validada para este cliente.');
                }

                PromocaoResgate::query()->create([
                    'promocao_id' => $promocao->id,
                    'empresa_id' => $empresa->id,
                    'user_id' => $customer->id,
                    'status' => PromocaoResgate::STATUS_REDEEMED,
                    'redeemed_at' => $timestamp,
                    'validated_by' => $validator->id,
                ]);

                if (Schema::hasColumn('promocoes', 'resgates')) {
                    Promocao::query()->whereKey($promocao->id)->increment('resgates');
                }
                if (Schema::hasColumn('promocoes', 'usos')) {
                    Promocao::query()->whereKey($promocao->id)->increment('usos');
                }
                if (Schema::hasColumn('promocoes', 'qtd_resgatada')) {
                    Promocao::query()->whereKey($promocao->id)->increment('qtd_resgatada');
                }

                $inscricao->update([
                    'ultima_visita' => $timestamp,
                ]);
            });
        } catch (QueryException $e) {
            if ($this->isDuplicateRedemptionConstraint($e)) {
                throw new DomainException('Promocao ja validada para este cliente.', 0, $e);
            }

            throw $e;
        }

        return $this->customerPromotions($empresa, $customer);
    }

    public function weeklySendStatus(Empresa $empresa): array
    {
        $used = Promocao::query()
            ->where('empresa_id', $empresa->id)
            ->whereNotNull('data_envio')
            ->where('data_envio', '>=', now()->subDays(7))
            ->count();

        return [
            'limit' => self::WEEKLY_SEND_LIMIT,
            'used' => $used,
            'remaining' => max(0, self::WEEKLY_SEND_LIMIT - $used),
        ];
    }

    public function sendPromotion(Empresa $empresa, Promocao $promocao, User $actor): array
    {
        $this->guardCompanyPromotionOwnership($empresa, $promocao);

        if (!$promocao->isOperationallyAvailable()) {
            throw new DomainException($promocao->isExpired()
                ? 'Promocao expirada.'
                : 'Promocao inativa ou indisponivel.');
        }

        if ($promocao->foiEnviada()) {
            throw new DomainException('Esta promocao ja foi enviada por push no MVP atual. Crie uma nova promocao para um novo disparo.');
        }

        $weeklyStatus = $this->weeklySendStatus($empresa);
        if ($weeklyStatus['remaining'] <= 0) {
            throw new DomainException('Limite semanal atingido. Cada empresa pode enviar no maximo 2 promocoes instantaneas por semana.');
        }

        $inscricoes = InscricaoEmpresa::query()
            ->with('user')
            ->where('empresa_id', $empresa->id)
            ->get()
            ->filter(fn (InscricaoEmpresa $inscricao) => $inscricao->user && $this->isCustomer($inscricao->user))
            ->values();

        if ($inscricoes->isEmpty()) {
            throw new DomainException('Nenhum cliente vinculado a empresa para receber esta promocao.');
        }

        $vapidAuth = $this->webPushAuth();
        if ($vapidAuth === null) {
            throw new DomainException('Push web nao esta configurado neste ambiente.');
        }

        $targetUserIds = $inscricoes->pluck('user_id')->unique()->values();
        $subscriptionsByUser = PushSubscription::query()
            ->whereIn('user_id', $targetUserIds->all())
            ->get()
            ->groupBy('user_id');

        $sentAt = now();
        $title = $promocao->notificationTitle();
        $body = $promocao->notificationBody();
        $payload = [
            'type' => 'promocao',
            'empresa_id' => $empresa->id,
            'promocao_id' => $promocao->id,
            'url' => '/detalhe_do_parceiro.html?id=' . $empresa->id,
        ];

        $stats = [
            'total_targeted' => $inscricoes->count(),
            'total_with_subscription' => 0,
            'total_sent' => 0,
            'total_failed' => 0,
            'total_without_subscription' => 0,
        ];

        DB::transaction(function () use ($promocao, $sentAt): void {
            $promocao->update([
                'data_envio' => $sentAt,
            ]);
        });

        foreach ($inscricoes as $inscricao) {
            $customer = $inscricao->user;
            if (!$customer) {
                continue;
            }

            $log = NotificacaoPush::query()->create([
                'user_id' => $customer->id,
                'empresa_id' => $empresa->id,
                'promocao_id' => $promocao->id,
                'tipo' => 'promocao',
                'titulo' => $title,
                'mensagem' => $body,
                'imagem' => $promocao->imagem,
                'status' => self::LOG_STATUS_PENDING,
                'erro' => null,
                'enviado' => false,
                'data_envio' => null,
            ]);

            $subscriptions = $subscriptionsByUser->get($customer->id, collect());
            if ($subscriptions->isEmpty()) {
                $stats['total_without_subscription']++;
                $log->update([
                    'status' => self::LOG_STATUS_NO_SUBSCRIPTION,
                    'erro' => 'Cliente sem subscription push ativa.',
                    'data_envio' => $sentAt,
                ]);
                continue;
            }

            $stats['total_with_subscription']++;
            $result = $this->deliverPushToUser($vapidAuth, $subscriptions, $title, $body, $payload);
            if ($result['sent']) {
                $stats['total_sent']++;
                $log->update([
                    'status' => self::LOG_STATUS_SENT,
                    'erro' => $result['error'] ?: null,
                    'enviado' => true,
                    'data_envio' => $sentAt,
                ]);
            } else {
                $stats['total_failed']++;
                $log->update([
                    'status' => self::LOG_STATUS_FAILED,
                    'erro' => $result['error'] ?: 'Falha ao entregar push para este cliente.',
                    'data_envio' => $sentAt,
                ]);
            }
        }

        $promocao->refresh();
        if (Schema::hasColumn('promocoes', 'total_envios')) {
            $promocao->update([
                'total_envios' => (int) ($promocao->total_envios ?? 0) + $stats['total_sent'],
            ]);
        }

        return [
            'promocao' => $this->serializePromotion($promocao->refresh()),
            'weekly_limit' => $this->weeklySendStatus($empresa),
            'delivery' => $stats,
        ];
    }

    public function serializePromotion(Promocao $promocao, array $extra = []): array
    {
        $status = $promocao->isExpired()
            ? 'expired'
            : ($promocao->isOperationallyAvailable() ? 'available' : 'inactive');

        return array_merge([
            'id' => $promocao->id,
            'empresa_id' => $promocao->empresa_id,
            'titulo' => $promocao->titulo,
            'descricao' => $promocao->descricao,
            'imagem' => $promocao->imagem,
            'imagem_url' => $promocao->imageUrl(),
            'validade' => optional($promocao->expirationDate())->toDateString(),
            'data_expiracao' => optional($promocao->expirationDate())->toDateString(),
            'notification_title' => $promocao->notificationTitle(),
            'notification_body' => $promocao->notificationBody(),
            'ativo' => (bool) $promocao->ativo,
            'status' => $status,
            'legacy_status' => $promocao->status ?: $this->determineLegacyStatus((bool) $promocao->ativo, optional($promocao->expirationDate())->toDateString()),
            'enviada_em' => optional($promocao->data_envio)->toIso8601String(),
            'data_envio' => optional($promocao->data_envio)->toIso8601String(),
            'total_envios' => (int) ($promocao->total_envios ?? 0),
        ], $extra);
    }

    private function serializePromotionForViewer(
        Promocao $promocao,
        ?User $viewer,
        ?InscricaoEmpresa $inscricao
    ): array {
        $base = $this->serializePromotion($promocao);

        if (!$viewer || !$this->isCustomer($viewer)) {
            return array_merge($base, [
                'viewer_status' => 'public',
                'message' => 'Promocao publica disponivel. A validacao acontece no estabelecimento.',
                'can_self_redeem' => false,
                'can_present_qr' => false,
                'redeemed_at' => null,
            ]);
        }

        if (!$inscricao) {
            return array_merge($base, [
                'viewer_status' => 'not_linked',
                'message' => 'Vincule-se a empresa para ficar elegivel a esta promocao.',
                'can_self_redeem' => false,
                'can_present_qr' => false,
                'redeemed_at' => null,
            ]);
        }

        $resgate = $this->findRedemption($promocao, $viewer);
        if ($resgate) {
            return array_merge($base, [
                'viewer_status' => 'redeemed',
                'message' => 'Promocao ja validada anteriormente para este cliente.',
                'can_self_redeem' => false,
                'can_present_qr' => false,
                'redeemed_at' => optional($resgate->redeemed_at)->toIso8601String(),
            ]);
        }

        return array_merge($base, [
            'viewer_status' => 'available',
            'message' => 'Apresente seu QR Code no estabelecimento para validar.',
            'can_self_redeem' => false,
            'can_present_qr' => true,
            'redeemed_at' => null,
        ]);
    }

    private function customerPromotionsMessage(array $items, bool $hasLink): string
    {
        if ($items === []) {
            return 'Nenhuma promocao ativa no momento.';
        }

        if (!$hasLink) {
            return 'Promocoes visiveis, mas o cliente precisa estar vinculado para ficar elegivel.';
        }

        $available = collect($items)->where('viewer_status', 'available')->count();
        if ($available > 0) {
            return 'Cliente com promocao disponivel para validacao no estabelecimento.';
        }

        $redeemed = collect($items)->where('viewer_status', 'redeemed')->count();
        if ($redeemed > 0) {
            return 'Todas as promocoes atuais desta empresa ja foram utilizadas por este cliente.';
        }

        return 'Nenhuma promocao elegivel para este cliente no momento.';
    }

    private function deliverPushToUser(array $vapidAuth, Collection $subscriptions, string $title, string $body, array $data): array
    {
        try {
            $webPush = new WebPush($vapidAuth);
            foreach ($subscriptions as $subscriptionModel) {
                $webPush->queueNotification(
                    Subscription::create([
                        'endpoint' => $subscriptionModel->endpoint,
                        'keys' => [
                            'p256dh' => $subscriptionModel->p256dh,
                            'auth' => $subscriptionModel->auth,
                        ],
                    ]),
                    json_encode([
                        'title' => $title,
                        'body' => $body,
                        'data' => $data,
                    ])
                );
            }

            $success = false;
            $errors = [];
            foreach ($webPush->flush() as $report) {
                if ($report->isSuccess()) {
                    $success = true;
                    continue;
                }

                $errors[] = (string) $report->getReason();
            }

            return [
                'sent' => $success,
                'error' => $errors !== [] ? implode(' | ', array_unique($errors)) : null,
            ];
        } catch (\Throwable $e) {
            return [
                'sent' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function guardCompanyPromotionOwnership(Empresa $empresa, Promocao $promocao): void
    {
        if ($promocao->empresa_id !== $empresa->id) {
            throw new DomainException('Empresa nao pode operar promocao de outra empresa.');
        }
    }

    private function findInscricao(Empresa $empresa, User $customer): ?InscricaoEmpresa
    {
        return InscricaoEmpresa::query()
            ->where('empresa_id', $empresa->id)
            ->where('user_id', $customer->id)
            ->first();
    }

    private function findRedemption(Promocao $promocao, User $customer): ?PromocaoResgate
    {
        return PromocaoResgate::query()
            ->where('promocao_id', $promocao->id)
            ->where('user_id', $customer->id)
            ->where('status', PromocaoResgate::STATUS_REDEEMED)
            ->latest('id')
            ->first();
    }

    private function isCustomer(User $user): bool
    {
        $perfil = Str::lower(trim((string) ($user->perfil ?? $user->role ?? $user->tipo ?? '')));

        return in_array($perfil, ['cliente', 'customer'], true);
    }

    private function determineLegacyStatus(bool $ativo, ?string $validade): string
    {
        if ($validade && now()->startOfDay()->gt(Carbon::parse($validade)->endOfDay())) {
            return Promocao::STATUS_EXPIRED;
        }

        return $ativo ? Promocao::STATUS_ACTIVE : Promocao::STATUS_PAUSED;
    }

    private function normalizeNullableString($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    private function deleteStoredImageIfNeeded(?string $path): void
    {
        $path = trim((string) $path);
        if ($path === '' || str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/storage/')) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    private function isDuplicateRedemptionConstraint(QueryException $e): bool
    {
        return str_contains(Str::lower($e->getMessage()), 'promocao_resgates_promocao_user_unique');
    }

    private function webPushAuth(): ?array
    {
        $publicKey = config('services.webpush.public_key') ?? env('VAPID_PUBLIC_KEY');
        $privateKey = config('services.webpush.private_key') ?? env('VAPID_PRIVATE_KEY');
        if (!$publicKey || !$privateKey) {
            return null;
        }

        return [
            'VAPID' => [
                'subject' => env('VAPID_SUBJECT', config('app.url') ?: 'mailto:admin@example.com'),
                'publicKey' => $publicKey,
                'privateKey' => $privateKey,
            ],
        ];
    }
}
