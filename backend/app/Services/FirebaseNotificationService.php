<?php

namespace App\Services;

use App\Models\PushNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebaseNotificationService
{
    private $serverKey;
    private $fcmUrl;

    public function __construct()
    {
        $this->serverKey = config('services.firebase.server_key');
        $this->fcmUrl = 'https://fcm.googleapis.com/fcm/send';
    }

    /**
     * Enviar notificação push para um usuário
     */
    public function sendToUser($fcmToken, $title, $body, $data = [])
    {
        if (!$fcmToken) {
            return ['success' => false, 'error' => 'FCM token não fornecido'];
        }

        $notification = [
            'title' => $title,
            'body' => $body,
            'icon' => config('app.url') . '/favicon.ico',
            'click_action' => config('app.url'),
            'sound' => 'default'
        ];

        $payload = [
            'to' => $fcmToken,
            'notification' => $notification,
            'data' => array_merge($data, [
                'timestamp' => now()->toISOString(),
                'app' => 'TemDeTudo'
            ]),
            'priority' => 'high',
            'content_available' => true
        ];

        return $this->sendRequest($payload);
    }

    /**
     * Enviar notificação para múltiplos usuários
     */
    public function sendToMultiple($fcmTokens, $title, $body, $data = [])
    {
        if (empty($fcmTokens)) {
            return ['success' => false, 'error' => 'Nenhum token fornecido'];
        }

        // Firebase FCM limita a 1000 tokens por requisição
        $chunks = array_chunk(array_filter($fcmTokens), 1000);
        $results = [];

        foreach ($chunks as $chunk) {
            $notification = [
                'title' => $title,
                'body' => $body,
                'icon' => config('app.url') . '/favicon.ico',
                'click_action' => config('app.url'),
                'sound' => 'default'
            ];

            $payload = [
                'registration_ids' => $chunk,
                'notification' => $notification,
                'data' => array_merge($data, [
                    'timestamp' => now()->toISOString(),
                    'app' => 'TemDeTudo'
                ]),
                'priority' => 'high',
                'content_available' => true
            ];

            $result = $this->sendRequest($payload);
            $results[] = $result;
        }

        return $this->mergeResults($results);
    }

    /**
     * Enviar notificação para tópico
     */
    public function sendToTopic($topic, $title, $body, $data = [])
    {
        $notification = [
            'title' => $title,
            'body' => $body,
            'icon' => config('app.url') . '/favicon.ico',
            'click_action' => config('app.url'),
            'sound' => 'default'
        ];

        $payload = [
            'to' => "/topics/{$topic}",
            'notification' => $notification,
            'data' => array_merge($data, [
                'timestamp' => now()->toISOString(),
                'app' => 'TemDeTudo'
            ]),
            'priority' => 'high',
            'content_available' => true
        ];

        return $this->sendRequest($payload);
    }

    /**
     * Processar fila de notificações pendentes
     */
    public function processQueue($limit = 100)
    {
        $notifications = PushNotification::pending()
            ->whereNotNull('fcm_token')
            ->limit($limit)
            ->get();

        $processed = 0;
        $errors = 0;

        foreach ($notifications as $notification) {
            try {
                $result = $this->sendToUser(
                    $notification->fcm_token,
                    $notification->title,
                    $notification->body,
                    $notification->data ?? []
                );

                if ($result['success']) {
                    $notification->update([
                        'is_sent' => true,
                        'sent_at' => now(),
                        'error_message' => null
                    ]);
                    $processed++;
                } else {
                    $notification->update([
                        'error_message' => $result['error'] ?? 'Erro desconhecido'
                    ]);
                    $errors++;
                }

            } catch (\Exception $e) {
                $notification->update([
                    'error_message' => $e->getMessage()
                ]);
                $errors++;
                Log::error('Erro ao enviar push notification', [
                    'notification_id' => $notification->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'processed' => $processed,
            'errors' => $errors,
            'total' => $notifications->count()
        ];
    }

    /**
     * Fazer requisição para o FCM
     */
    private function sendRequest($payload)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->serverKey,
                'Content-Type' => 'application/json',
            ])->post($this->fcmUrl, $payload);

            $result = $response->json();

            if ($response->successful() && isset($result['success']) && $result['success'] > 0) {
                return ['success' => true, 'result' => $result];
            }

            $error = $result['results'][0]['error'] ?? 'Erro desconhecido';
            return ['success' => false, 'error' => $error, 'result' => $result];

        } catch (\Exception $e) {
            Log::error('Erro na requisição FCM', [
                'error' => $e->getMessage(),
                'payload' => $payload
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Combinar resultados de múltiplas requisições
     */
    private function mergeResults($results)
    {
        $totalSuccess = 0;
        $totalFailure = 0;
        $errors = [];

        foreach ($results as $result) {
            if ($result['success']) {
                $totalSuccess += $result['result']['success'] ?? 0;
                $totalFailure += $result['result']['failure'] ?? 0;
            } else {
                $totalFailure++;
                $errors[] = $result['error'];
            }
        }

        return [
            'success' => $totalSuccess > 0,
            'total_success' => $totalSuccess,
            'total_failure' => $totalFailure,
            'errors' => $errors
        ];
    }

    /**
     * Validar token FCM
     */
    public function validateToken($fcmToken)
    {
        if (!$fcmToken) {
            return false;
        }

        // Enviar notificação de teste silenciosa
        $payload = [
            'to' => $fcmToken,
            'data' => [
                'test' => 'validation',
                'timestamp' => now()->toISOString()
            ],
            'dry_run' => true // Não envia realmente
        ];

        $result = $this->sendRequest($payload);
        return $result['success'];
    }
}