<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\NotificationService;
use App\Models\PushNotification;
use App\Models\User;
use App\Models\Admin;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    private $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Obter notificações do usuário
     */
    public function getUserNotifications(Request $request)
    {
        $user = $request->get('authenticated_user');
        $userType = $user instanceof Admin ? 'admin' : 'client';

        $notifications = PushNotification::forUser($user->id, $userType)
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $notifications,
            'unread_count' => PushNotification::forUser($user->id, $userType)->unread()->count()
        ]);
    }

    /**
     * Marcar notificação como lida
     */
    public function markAsRead(Request $request, $notificationId)
    {
        $user = $request->get('authenticated_user');
        
        $success = $this->notificationService->markAsRead($notificationId, $user->id);

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Notificação marcada como lida'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Notificação não encontrada'
        ], 404);
    }

    /**
     * Marcar todas como lidas
     */
    public function markAllAsRead(Request $request)
    {
        $user = $request->get('authenticated_user');
        $userType = $user instanceof Admin ? 'admin' : 'client';

        $count = PushNotification::forUser($user->id, $userType)
            ->unread()
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => "Foram marcadas {$count} notificações como lidas",
            'count' => $count
        ]);
    }

    /**
     * Atualizar FCM token do usuário
     */
    public function updateFcmToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fcm_token' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Token FCM inválido',
                'errors' => $validator->errors()
            ], 400);
        }

        $user = $request->get('authenticated_user');
        
        $user->update([
            'fcm_token' => $request->fcm_token
        ]);

        AuditLog::logEvent('fcm_token_updated', $user->id, $request);

        return response()->json([
            'success' => true,
            'message' => 'Token FCM atualizado com sucesso'
        ]);
    }

    /**
     * Enviar broadcast (apenas admins)
     */
    public function sendBroadcast(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:500',
            'user_type' => 'required|in:all,client,company,admin',
            'data' => 'array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 400);
        }

        $count = $this->notificationService->broadcast(
            $request->title,
            $request->body,
            $request->data ?? [],
            $request->user_type
        );

        return response()->json([
            'success' => true,
            'message' => "Notificação enviada para {$count} usuários",
            'recipient_count' => $count
        ]);
    }

    /**
     * Testar notificação
     */
    public function testNotification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:welcome,points,security,level_up',
            'user_id' => 'required|integer|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 400);
        }

        $user = User::find($request->user_id);
        
        switch ($request->type) {
            case 'welcome':
                $this->notificationService->sendWelcome($user, 'client');
                $message = 'Notificação de boas-vindas enviada';
                break;

            case 'points':
                $pontos = 100;
                $empresa = (object) ['nome' => 'Empresa Teste', 'id' => 1];
                $user->increment('pontos', $pontos);
                $this->notificationService->notifyPoints($user, $pontos, $empresa, 'ganho');
                $message = 'Notificação de pontos enviada';
                break;

            case 'security':
                $this->notificationService->sendSecurityAlert($user, 'login_suspicious', [
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);
                $message = 'Alerta de segurança enviado';
                break;

            case 'level_up':
                $this->notificationService->notifyLevelUp($user, 'Bronze', 'Prata');
                $message = 'Notificação de level up enviada';
                break;

            default:
                return response()->json([
                    'success' => false,
                    'message' => 'Tipo de teste não implementado'
                ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => $message
        ]);
    }

    /**
     * Estatísticas de notificações (admin)
     */
    public function getStats(Request $request)
    {
        $days = $request->get('days', 30);
        $stats = $this->notificationService->getStats($days);

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Processar fila de notificações (admin/cron)
     */
    public function processQueue(Request $request)
    {
        $limit = $request->get('limit', 100);
        $result = $this->notificationService->processQueue($limit);

        return response()->json([
            'success' => true,
            'message' => 'Fila processada com sucesso',
            'data' => $result
        ]);
    }

    /**
     * Obter configurações de notificação do usuário
     */
    public function getNotificationSettings(Request $request)
    {
        $user = $request->get('authenticated_user');

        $settings = [
            'email_enabled' => $user->email_notifications ?? true,
            'push_enabled' => !empty($user->fcm_token),
            'points_notifications' => $user->points_notifications ?? true,
            'security_notifications' => $user->security_notifications ?? true,
            'promotional_notifications' => $user->promotional_notifications ?? false,
            'fcm_token_exists' => !empty($user->fcm_token)
        ];

        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }

    /**
     * Atualizar configurações de notificação
     */
    public function updateNotificationSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email_enabled' => 'boolean',
            'points_notifications' => 'boolean',
            'security_notifications' => 'boolean',
            'promotional_notifications' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 400);
        }

        $user = $request->get('authenticated_user');

        $user->update([
            'email_notifications' => $request->get('email_enabled', $user->email_notifications),
            'points_notifications' => $request->get('points_notifications', $user->points_notifications),
            'security_notifications' => $request->get('security_notifications', $user->security_notifications),
            'promotional_notifications' => $request->get('promotional_notifications', $user->promotional_notifications)
        ]);

        AuditLog::logEvent('notification_settings_updated', $user->id, $request);

        return response()->json([
            'success' => true,
            'message' => 'Configurações atualizadas com sucesso'
        ]);
    }
}