<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use App\Services\WebPushDeliveryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PushSubscriptionController extends Controller
{
    public function __construct(
        private readonly WebPushDeliveryService $pushDeliveryService
    ) {
    }

    public function publicKey()
    {
        $publicKey = $this->pushDeliveryService->publicKey();
        $configured = $this->pushDeliveryService->isConfigured();

        return response()->json([
            'success' => true,
            'configured' => $configured,
            'vapidPublicKey' => $publicKey,
            'serviceWorker' => '/sw-push.js',
            'message' => $configured
                ? 'Push configurado para este ambiente.'
                : 'Configuração de push pendente no servidor.',
        ]);
    }

    public function subscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'endpoint' => 'required|string',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
            'contentEncoding' => 'nullable|string|max:32',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $userId = Auth::id();
        $endpoint = trim((string) $request->input('endpoint'));
        $publicKey = trim((string) $request->input('keys.p256dh'));
        $authToken = trim((string) $request->input('keys.auth'));
        $contentEncoding = trim((string) ($request->input('contentEncoding') ?: 'aes128gcm'));

        $sub = PushSubscription::updateOrCreate(
            ['endpoint' => $endpoint],
            [
                'user_id' => $userId,
                'public_key' => $publicKey,
                'auth_token' => $authToken,
                'content_encoding' => $contentEncoding,
                'p256dh' => $publicKey,
                'auth' => $authToken,
                'user_agent' => $request->userAgent(),
                'device_type' => PushSubscription::detectDeviceType($request->userAgent()),
                'ip' => $request->ip(),
                'last_seen_at' => now(),
                'revoked_at' => null,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Notificacoes ativadas neste dispositivo.',
            'data' => $sub,
        ]);
    }

    public function unsubscribe(Request $request)
    {
        $endpoint = trim((string) $request->input('endpoint', ''));

        $query = PushSubscription::query()
            ->where('user_id', Auth::id())
            ->active();

        if ($endpoint !== '') {
            $query->where('endpoint', $endpoint);
        }

        $subscriptions = $query->get();
        foreach ($subscriptions as $subscription) {
            $subscription->revoke();
        }

        return response()->json([
            'success' => true,
            'message' => 'Notificacoes desativadas neste dispositivo.',
            'data' => [
                'revoked' => $subscriptions->count(),
            ],
        ]);
    }

    public function test(Request $request)
    {
        $user = Auth::user();
        $subscriptions = $user?->pushSubscriptions()->active()->get() ?? collect();
        if ($subscriptions->isEmpty()) {
            return response()->json([
                'success' => false,
                'error' => 'no_subscription',
                'message' => 'Este dispositivo ainda nao ativou notificacoes push.',
                'meta' => [
                    'delivery' => [
                        'status' => 'no_subscription',
                        'total_elegiveis' => 1,
                        'total_com_subscription' => 0,
                        'enviados' => 0,
                        'falhas' => 0,
                        'ignorados_sem_subscription' => 1,
                        'ignorados_sem_vinculo' => 0,
                    ],
                ],
            ], 422);
        }

        $result = $this->pushDeliveryService->deliverToSubscriptions(
            $subscriptions,
            'Teste de notificacao',
            'Push enviado com sucesso para este dispositivo.',
            [
                'type' => 'test',
                'tipo' => 'test',
                'url' => '/meus_pontos.html',
            ]
        );

        $delivery = [
            'status' => $result['status'],
            'total_elegiveis' => 1,
            'total_com_subscription' => $subscriptions->count(),
            'enviados' => (int) ($result['subscriptions_sent'] ?? 0),
            'falhas' => (int) ($result['subscriptions_failed'] ?? 0),
            'ignorados_sem_subscription' => 0,
            'ignorados_sem_vinculo' => 0,
        ];

        if (($result['status'] ?? null) === 'config_missing') {
            return response()->json([
                'success' => false,
                'error' => 'config_missing',
                'message' => 'Configuração de push pendente no servidor.',
                'meta' => [
                    'delivery' => $delivery,
                ],
            ], 422);
        }

        if (!($result['sent'] ?? false)) {
            return response()->json([
                'success' => false,
                'error' => 'delivery_failed',
                'message' => $result['error'] ?: 'Não foi possível enviar a notificação de teste.',
                'meta' => [
                    'delivery' => $delivery,
                ],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notificacao de teste enviada para este dispositivo.',
            'meta' => [
                'delivery' => $delivery,
            ],
        ]);
    }
}
