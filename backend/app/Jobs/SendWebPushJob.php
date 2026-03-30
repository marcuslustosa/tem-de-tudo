<?php

namespace App\Jobs;

use App\Models\PushSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
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

    public function handle(): void
    {
        $auth = [
            'VAPID' => [
                'subject' => config('app.url') ?? 'mailto:admin@example.com',
                'publicKey' => config('services.webpush.public_key') ?? env('VAPID_PUBLIC_KEY'),
                'privateKey' => config('services.webpush.private_key') ?? env('VAPID_PRIVATE_KEY'),
            ],
        ];

        $webPush = new WebPush($auth);
        $query = PushSubscription::query();
        if ($this->userIds) {
            $query->whereIn('user_id', $this->userIds);
        }
        $subs = $query->get();

        foreach ($subs as $sub) {
            $subscription = Subscription::create([
                'endpoint' => $sub->endpoint,
                'keys' => [
                    'p256dh' => $sub->p256dh,
                    'auth' => $sub->auth,
                ],
            ]);

            $payload = json_encode([
                'title' => $this->title,
                'body' => $this->body,
                'data' => $this->data,
            ]);

            $webPush->queueNotification($subscription, $payload);
        }

        foreach ($webPush->flush() as $report) {
            if (!$report->isSuccess()) {
                Log::warning('Push failure', [
                    'endpoint' => $report->getRequest()->getUri()->__toString(),
                    'reason' => $report->getReason(),
                ]);
            }
        }
    }
}
