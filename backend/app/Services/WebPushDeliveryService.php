<?php

namespace App\Services;

use App\Models\PushSubscription;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushDeliveryService
{
    public function publicKey(): ?string
    {
        $key = config('services.webpush.public_key') ?? env('VAPID_PUBLIC_KEY');

        return is_string($key) && trim($key) !== '' ? trim($key) : null;
    }

    public function isConfigured(): bool
    {
        return $this->auth() !== null;
    }

    public function auth(): ?array
    {
        $publicKey = $this->publicKey();
        $privateKey = config('services.webpush.private_key') ?? env('VAPID_PRIVATE_KEY');
        $privateKey = is_string($privateKey) && trim($privateKey) !== '' ? trim($privateKey) : null;

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

    /**
     * @param  Collection<int, PushSubscription>  $subscriptions
     * @return array{
     *     status:string,
     *     sent:bool,
     *     error:string|null,
     *     config_missing:bool,
     *     subscriptions_total:int,
     *     subscriptions_sent:int,
     *     subscriptions_failed:int,
     *     subscriptions_skipped:int,
     *     invalidated:int,
     *     errors:array<int,string>
     * }
     */
    public function deliverToSubscriptions(
        Collection $subscriptions,
        string $title,
        string $body,
        array $data,
        ?array $auth = null
    ): array {
        $activeSubscriptions = $subscriptions
            ->filter(fn ($subscription) => $subscription instanceof PushSubscription)
            ->filter(fn (PushSubscription $subscription) => $subscription->revoked_at === null)
            ->values();

        if ($activeSubscriptions->isEmpty()) {
            return [
                'status' => 'no_subscription',
                'sent' => false,
                'error' => 'Nenhuma subscription push ativa para este envio.',
                'config_missing' => false,
                'subscriptions_total' => 0,
                'subscriptions_sent' => 0,
                'subscriptions_failed' => 0,
                'subscriptions_skipped' => 0,
                'invalidated' => 0,
                'errors' => [],
            ];
        }

        $auth ??= $this->auth();
        if ($auth === null) {
            Log::warning('Web push indisponivel: configuracao VAPID ausente.', [
                'subscriptions_total' => $activeSubscriptions->count(),
            ]);

            return [
                'status' => 'config_missing',
                'sent' => false,
                'error' => 'Configuração de push pendente no servidor.',
                'config_missing' => true,
                'subscriptions_total' => $activeSubscriptions->count(),
                'subscriptions_sent' => 0,
                'subscriptions_failed' => 0,
                'subscriptions_skipped' => $activeSubscriptions->count(),
                'invalidated' => 0,
                'errors' => [],
            ];
        }

        $payload = $this->buildPayload($title, $body, $data);
        $subscriptionsSent = 0;
        $subscriptionsFailed = 0;
        $subscriptionsSkipped = 0;
        $invalidated = 0;
        $errors = [];
        $queuedSubscriptions = [];

        try {
            $webPush = new WebPush($auth);

            foreach ($activeSubscriptions as $subscriptionModel) {
                $publicKey = $subscriptionModel->webPushPublicKey();
                $authToken = $subscriptionModel->webPushAuthToken();
                if (!$subscriptionModel->endpoint || !$publicKey || !$authToken) {
                    $subscriptionsFailed++;
                    $invalidated++;
                    $errors[] = 'Subscription incompleta ou invalida.';
                    $subscriptionModel->revoke();
                    continue;
                }

                $queuedSubscriptions[$subscriptionModel->endpoint] = $subscriptionModel;

                $webPush->queueNotification(
                    Subscription::create([
                        'endpoint' => $subscriptionModel->endpoint,
                        'publicKey' => $publicKey,
                        'authToken' => $authToken,
                        'contentEncoding' => $subscriptionModel->webPushContentEncoding(),
                    ]),
                    json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                );
            }

            if ($queuedSubscriptions === []) {
                return [
                    'status' => 'failed',
                    'sent' => false,
                    'error' => $errors !== [] ? implode(' | ', array_unique($errors)) : 'Nenhuma subscription valida disponivel.',
                    'config_missing' => false,
                    'subscriptions_total' => $activeSubscriptions->count(),
                    'subscriptions_sent' => 0,
                    'subscriptions_failed' => $subscriptionsFailed,
                    'subscriptions_skipped' => $subscriptionsSkipped,
                    'invalidated' => $invalidated,
                    'errors' => array_values(array_unique($errors)),
                ];
            }

            foreach ($webPush->flush() as $report) {
                $subscriptionModel = $queuedSubscriptions[$report->getEndpoint()] ?? null;
                if ($report->isSuccess()) {
                    $subscriptionsSent++;

                    if ($subscriptionModel) {
                        $subscriptionModel->forceFill([
                            'last_seen_at' => now(),
                            'revoked_at' => null,
                        ])->save();
                    }

                    continue;
                }

                $subscriptionsFailed++;
                $reason = trim((string) $report->getReason());
                if ($reason !== '') {
                    $errors[] = $reason;
                }

                if ($subscriptionModel && $report->isSubscriptionExpired()) {
                    $subscriptionModel->revoke();
                    $invalidated++;
                }

                Log::warning('Falha no envio de web push para subscription.', [
                    'endpoint' => $report->getEndpoint(),
                    'reason' => $reason ?: 'unknown',
                    'expired' => $report->isSubscriptionExpired(),
                ]);
            }

            return [
                'status' => $subscriptionsSent > 0 ? 'sent' : 'failed',
                'sent' => $subscriptionsSent > 0,
                'error' => $errors !== [] ? implode(' | ', array_unique($errors)) : null,
                'config_missing' => false,
                'subscriptions_total' => $activeSubscriptions->count(),
                'subscriptions_sent' => $subscriptionsSent,
                'subscriptions_failed' => $subscriptionsFailed,
                'subscriptions_skipped' => $subscriptionsSkipped,
                'invalidated' => $invalidated,
                'errors' => array_values(array_unique($errors)),
            ];
        } catch (\Throwable $e) {
            Log::warning('Falha inesperada ao enviar web push.', [
                'error' => $e->getMessage(),
                'subscriptions_total' => $activeSubscriptions->count(),
            ]);

            return [
                'status' => 'failed',
                'sent' => false,
                'error' => $e->getMessage(),
                'config_missing' => false,
                'subscriptions_total' => $activeSubscriptions->count(),
                'subscriptions_sent' => $subscriptionsSent,
                'subscriptions_failed' => max($subscriptionsFailed, 1),
                'subscriptions_skipped' => $subscriptionsSkipped,
                'invalidated' => $invalidated,
                'errors' => [$e->getMessage()],
            ];
        }
    }

    private function buildPayload(string $title, string $body, array $data): array
    {
        $targetUrl = (string) ($data['url'] ?? '/index.html');
        $tipo = (string) ($data['tipo'] ?? $data['type'] ?? 'push');

        return [
            'title' => trim($title) !== '' ? $title : 'Tem de Tudo',
            'body' => trim($body) !== '' ? $body : 'Voce recebeu uma nova notificacao.',
            'icon' => $data['icon'] ?? '/img/icon-192.png',
            'badge' => $data['badge'] ?? '/img/icon-96.png',
            'url' => $targetUrl,
            'empresa_id' => $data['empresa_id'] ?? null,
            'tipo' => $tipo,
            'data' => array_merge($data, [
                'url' => $targetUrl,
                'empresa_id' => $data['empresa_id'] ?? null,
                'tipo' => $tipo,
            ]),
        ];
    }
}
