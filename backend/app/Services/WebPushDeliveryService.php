<?php

namespace App\Services;

use App\Models\PushSubscription;
use Illuminate\Support\Collection;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushDeliveryService
{
    public function auth(): ?array
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

    /**
     * @param  Collection<int, PushSubscription>  $subscriptions
     * @return array{sent: bool, error: string|null}
     */
    public function deliverToSubscriptions(Collection $subscriptions, string $title, string $body, array $data, array $auth): array
    {
        try {
            $webPush = new WebPush($auth);

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
}
