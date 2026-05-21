<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\InscricaoEmpresa;
use App\Models\NotificacaoPush;
use App\Models\PushSubscription;
use App\Models\User;
use App\Services\WebPushDeliveryService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PushSubscriptionController extends Controller
{
    private const ADMIN_PUSH_TEST_TYPE = 'admin_push_test';

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

    public function adminClientStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'nullable|integer|min:1|required_without:email',
            'email' => 'nullable|email|required_without:user_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $this->resolveAdminClient($request);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente nao encontrado.',
                'error' => 'user_not_found',
            ], 404);
        }

        $subscriptions = $user->pushSubscriptions()
            ->active()
            ->orderByDesc('last_seen_at')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Cliente localizado para teste de push.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'telefone' => $user->telefone,
                    'status' => $user->status,
                    'perfil' => $user->perfil ?? $user->role,
                ],
                'push' => [
                    'has_active_subscription' => $subscriptions->isNotEmpty(),
                    'total_subscriptions' => $subscriptions->count(),
                    'active_subscriptions' => $subscriptions->count(),
                    'devices' => $subscriptions
                        ->map(fn (PushSubscription $subscription) => $subscription->device_type ?: 'desconhecido')
                        ->filter()
                        ->values()
                        ->all(),
                    'last_seen_at' => optional($subscriptions->first()?->last_seen_at)->toIso8601String(),
                ],
            ],
        ]);
    }

    public function adminTestClient(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|min:1',
            'title' => 'nullable|string|max:80',
            'body' => 'nullable|string|max:160',
            'url' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::query()->find((int) $request->integer('user_id'));
        if (!$user || !$this->isCliente($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente nao encontrado.',
                'error' => 'user_not_found',
            ], 404);
        }

        $subscriptions = $user->pushSubscriptions()
            ->active()
            ->orderByDesc('last_seen_at')
            ->get();

        $title = trim((string) $request->input('title', 'Teste de notificacao'));
        $body = trim((string) $request->input('body', 'Se voce recebeu esta mensagem, o push esta funcionando.'));
        $url = trim((string) $request->input('url', '/meus_pontos.html'));
        $attemptedAt = now();

        if ($subscriptions->isEmpty()) {
            $this->logAdminPushAttempt($user, $title, $body, false, 'no_subscription', 'Cliente ainda nao ativou notificacoes neste dispositivo.', $attemptedAt);

            return response()->json([
                'success' => false,
                'error' => 'no_subscription',
                'message' => 'Cliente ainda nao ativou notificacoes neste dispositivo.',
                'meta' => [
                    'delivery' => $this->buildDeliveryMeta($subscriptions, [
                        'status' => 'no_subscription',
                        'subscriptions_sent' => 0,
                        'subscriptions_failed' => 0,
                    ], true),
                ],
            ], 409);
        }

        $result = $this->pushDeliveryService->deliverToSubscriptions(
            $subscriptions,
            $title !== '' ? $title : 'Teste de notificacao',
            $body !== '' ? $body : 'Se voce recebeu esta mensagem, o push esta funcionando.',
            [
                'type' => 'admin_push_test',
                'tipo' => self::ADMIN_PUSH_TEST_TYPE,
                'url' => $url !== '' ? $url : '/meus_pontos.html',
            ]
        );

        $delivery = $this->buildDeliveryMeta($subscriptions, $result);
        $status = (string) ($result['status'] ?? '');
        $logStatus = ($result['sent'] ?? false) ? 'sent' : ($status === 'config_missing' ? 'config_missing' : 'failed');
        $message = match ($status) {
            'config_missing' => 'Push nao configurado no servidor.',
            'failed' => 'Nao foi possivel entregar o push teste para este cliente.',
            default => 'Push teste enviado para o cliente.',
        };

        $this->logAdminPushAttempt(
            $user,
            $title !== '' ? $title : 'Teste de notificacao',
            $body !== '' ? $body : 'Se voce recebeu esta mensagem, o push esta funcionando.',
            (bool) ($result['sent'] ?? false),
            $logStatus,
            $result['error'] ?? null,
            $attemptedAt
        );

        if ($status === 'config_missing') {
            return response()->json([
                'success' => false,
                'error' => 'config_missing',
                'message' => $message,
                'meta' => [
                    'delivery' => $delivery,
                ],
            ], 422);
        }

        if (!($result['sent'] ?? false)) {
            return response()->json([
                'success' => false,
                'error' => 'delivery_failed',
                'message' => $result['error'] ?: $message,
                'meta' => [
                    'delivery' => $delivery,
                ],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'meta' => [
                'delivery' => $delivery,
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

    private function resolveAdminClient(Request $request): ?User
    {
        $query = User::query();
        if ($request->filled('user_id')) {
            $query->whereKey((int) $request->integer('user_id'));
        } else {
            $query->where('email', trim((string) $request->input('email')));
        }

        $user = $query->first();
        if (!$user || !$this->isCliente($user)) {
            return null;
        }

        return $user;
    }

    private function isCliente(User $user): bool
    {
        $perfil = strtolower(trim((string) ($user->perfil ?? $user->role ?? '')));

        return in_array($perfil, ['cliente', 'customer'], true);
    }

    private function buildDeliveryMeta(EloquentCollection $subscriptions, array $result, bool $noSubscription = false): array
    {
        return [
            'status' => (string) ($result['status'] ?? ($noSubscription ? 'no_subscription' : 'failed')),
            'total_elegiveis' => 1,
            'total_com_subscription' => $subscriptions->count(),
            'enviados' => (int) ($result['subscriptions_sent'] ?? 0),
            'falhas' => (int) ($result['subscriptions_failed'] ?? 0),
            'ignorados_sem_subscription' => $noSubscription ? 1 : 0,
            'ignorados_sem_vinculo' => 0,
            'total_subscriptions' => $subscriptions->count(),
        ];
    }

    private function logAdminPushAttempt(
        User $user,
        string $title,
        string $body,
        bool $sent,
        string $status,
        ?string $error,
        $attemptedAt
    ): void {
        $companyId = InscricaoEmpresa::query()
            ->where('user_id', $user->id)
            ->value('empresa_id');

        if (!$companyId) {
            Log::info('Admin push test sem log em notificacoes_push: cliente sem empresa vinculada.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'status' => $status,
            ]);

            return;
        }

        NotificacaoPush::query()->create([
            'user_id' => $user->id,
            'empresa_id' => $companyId,
            'promocao_id' => null,
            'bonus_aniversario_id' => null,
            'lembrete_id' => null,
            'tipo' => self::ADMIN_PUSH_TEST_TYPE,
            'titulo' => $title,
            'mensagem' => $body,
            'imagem' => null,
            'status' => $status,
            'erro' => $error,
            'enviado' => $sent,
            'data_envio' => $attemptedAt,
        ]);
    }
}
