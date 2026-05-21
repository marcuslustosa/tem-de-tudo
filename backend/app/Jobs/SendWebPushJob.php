<?php

namespace App\Jobs;

use App\Models\PushSubscription;
use App\Services\WebPushDeliveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWebPushJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $title,
        public string $body,
        public array $data = [],
        public ?array $userIds = null
    ) {
    }

    public function handle(WebPushDeliveryService $pushDeliveryService): void
    {
        $query = PushSubscription::query()->active();
        if ($this->userIds) {
            $query->whereIn('user_id', $this->userIds);
        }

        $subscriptions = $query->get();
        if ($subscriptions->isEmpty()) {
            return;
        }

        $result = $pushDeliveryService->deliverToSubscriptions(
            $subscriptions,
            $this->title,
            $this->body,
            $this->data
        );

        if (($result['status'] ?? null) === 'config_missing') {
            Log::warning('SendWebPushJob ignorado por configuracao VAPID ausente.', [
                'subscriptions_total' => $subscriptions->count(),
            ]);

            return;
        }

        if (!($result['sent'] ?? false)) {
            Log::warning('SendWebPushJob concluiu sem entregas bem-sucedidas.', [
                'subscriptions_total' => $subscriptions->count(),
                'error' => $result['error'] ?? null,
            ]);
        }
    }
}
