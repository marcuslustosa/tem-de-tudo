<?php

namespace App\Services;

use App\Models\User;
use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\PushNotification;
use App\Mail\WelcomeMail;
use App\Mail\PontosNotificationMail;
use App\Mail\SecurityAlertMail;
use App\Mail\AdminReportMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

class NotificationService
{
    private $pushService;

    public function __construct(FirebaseNotificationService $pushService)
    {
        $this->pushService = $pushService;
    }

    /**
     * Enviar boas-vindas para novo usuário
     */
    public function sendWelcome($user, $userType = 'client')
    {
        // Email de boas-vindas
        Queue::push(function() use ($user, $userType) {
            Mail::to($user->email)->send(new WelcomeMail($user, $userType));
        });

        // Push notification
        $title = $userType === 'company' 
            ? 'Bem-vinda, Empresa Parceira!' 
            : 'Bem-vindo à TemDeTudo!';

        $body = $userType === 'company'
            ? 'Sua empresa agora faz parte da maior rede de fidelidade do Brasil!'
            : 'Comece a acumular pontos e ganhar recompensas incríveis!';

        $this->sendPush($user, $title, $body, [
            'type' => 'welcome',
            'userType' => $userType,
            'action' => 'open_app'
        ], 'welcome');

        // Log da ação
        AuditLog::logEvent('notification_sent', $user->id, request(), [
            'type' => 'welcome',
            'userType' => $userType,
            'channels' => ['email', 'push']
        ]);
    }

    /**
     * Notificar ganho/resgate de pontos
     */
    public function notifyPoints($user, $pontos, $empresa, $tipo = 'ganho')
    {
        // Email de pontos
        Queue::push(function() use ($user, $pontos, $empresa, $tipo) {
            Mail::to($user->email)->send(new PontosNotificationMail($user, $pontos, $empresa, $tipo));
        });

        // Push notification
        if ($tipo === 'ganho') {
            $title = "🎉 +{$pontos} pontos!";
            $body = "Você ganhou pontos na {$empresa->nome}. Total: {$user->pontos} pontos";
            $icon = '🎁';
        } else {
            $title = "✅ Resgate realizado!";
            $body = "Você resgatou {$pontos} pontos. Saldo: {$user->pontos} pontos";
            $icon = '🛍️';
        }

        $this->sendPush($user, $title, $body, [
            'type' => 'points_' . $tipo,
            'points' => $pontos,
            'empresa_id' => $empresa->id ?? null,
            'total_points' => $user->pontos,
            'action' => 'open_profile'
        ], 'points_' . ($tipo === 'ganho' ? 'gained' : 'redeemed'));

        // Verificar mudança de nível
        $this->checkLevelUp($user, $pontos, $tipo);
    }

    /**
     * Verificar e notificar mudança de nível
     */
    private function checkLevelUp($user, $pontosAdicionados, $tipo)
    {
        if ($tipo !== 'ganho') return;

        $pontosAnteriores = $user->pontos - $pontosAdicionados;
        $nivelAnterior = $this->calculateLevel($pontosAnteriores);
        $nivelAtual = $this->calculateLevel($user->pontos);

        if ($nivelAnterior !== $nivelAtual) {
            $this->notifyLevelUp($user, $nivelAnterior, $nivelAtual);
        }
    }

    /**
     * Notificar mudança de nível
     */
    public function notifyLevelUp($user, $nivelAnterior, $nivelAtual)
    {
        $emojis = [
            'Bronze' => '🥉',
            'Prata' => '🥈', 
            'Ouro' => '🥇',
            'Diamante' => '💎'
        ];

        $title = "🚀 Parabéns! Você subiu de nível!";
        $body = "Você evoluiu de {$nivelAnterior} para {$nivelAtual}! {$emojis[$nivelAtual]}";

        $this->sendPush($user, $title, $body, [
            'type' => 'level_up',
            'old_level' => $nivelAnterior,
            'new_level' => $nivelAtual,
            'points' => $user->pontos,
            'action' => 'open_profile'
        ], 'level_up');

        // Log especial para level up
        AuditLog::logEvent('level_up', $user->id, request(), [
            'old_level' => $nivelAnterior,
            'new_level' => $nivelAtual,
            'points' => $user->pontos
        ]);
    }

    /**
     * Enviar alerta de segurança
     */
    public function sendSecurityAlert($user, $event, $details = [])
    {
        // Email de segurança
        Queue::push(function() use ($user, $event, $details) {
            Mail::to($user->email)->send(new SecurityAlertMail($user, $event, $details));
        });

        // Push notification de alta prioridade
        $titles = [
            'login_suspicious' => '🔒 Login Suspeito Detectado',
            'password_changed' => '🔐 Senha Alterada',
            'account_locked' => '⚠️ Conta Bloqueada',
            'new_device' => '📱 Novo Dispositivo'
        ];

        $bodies = [
            'login_suspicious' => 'Detectamos um login suspeito em sua conta. Verifique agora!',
            'password_changed' => 'Sua senha foi alterada com sucesso.',
            'account_locked' => 'Sua conta foi temporariamente bloqueada por segurança.',
            'new_device' => 'Um novo dispositivo acessou sua conta.'
        ];

        $title = $titles[$event] ?? '🔔 Alerta de Segurança';
        $body = $bodies[$event] ?? 'Atividade de segurança detectada em sua conta.';

        $this->sendPush($user, $title, $body, [
            'type' => 'security_alert',
            'event' => $event,
            'details' => $details,
            'action' => 'open_security',
            'priority' => 'high'
        ], 'security_alert');
    }

    /**
     * Enviar relatório administrativo
     */
    public function sendAdminReport($admin, $report, $period = 'daily')
    {
        // Email do relatório
        Queue::push(function() use ($admin, $report, $period) {
            Mail::to($admin->email)->send(new AdminReportMail($report, $admin, $period));
        });

        // Push notification para admin
        $periods = ['daily' => 'diário', 'weekly' => 'semanal', 'monthly' => 'mensal'];
        $periodName = $periods[$period] ?? 'personalizado';

        $title = "📊 Relatório {$periodName} pronto";
        $body = "Seu relatório administrativo foi gerado. {$report['users']['total']} usuários ativos.";

        $this->sendPush($admin, $title, $body, [
            'type' => 'admin_report',
            'period' => $period,
            'stats' => [
                'users' => $report['users']['total'] ?? 0,
                'new_users' => $report['users']['new'] ?? 0
            ],
            'action' => 'open_admin'
        ], 'admin_alert', 'admin');
    }

    /**
     * Broadcast para todos os usuários
     */
    public function broadcast($title, $body, $data = [], $userType = 'all')
    {
        $users = collect();

        if ($userType === 'all' || $userType === 'client') {
            $users = $users->merge(User::whereNotNull('fcm_token')->get());
        }

        if ($userType === 'all' || $userType === 'admin') {
            $users = $users->merge(Admin::whereNotNull('fcm_token')->get());
        }

        if ($userType === 'company') {
            $users = $users->merge(User::where('tipo', 'empresa')->whereNotNull('fcm_token')->get());
        }

        // Criar notificações em massa
        $count = PushNotification::createBulk($users->toArray(), $title, $body, $data, 'broadcast');

        // Enviar por tópico também (mais eficiente)
        if ($userType === 'all') {
            $this->pushService->sendToTopic('all_users', $title, $body, $data);
        } else {
            $this->pushService->sendToTopic($userType . '_users', $title, $body, $data);
        }

        AuditLog::logEvent('broadcast_sent', request()->get('authenticated_user')?->id, request(), [
            'title' => $title,
            'userType' => $userType,
            'recipient_count' => $count
        ]);

        return $count;
    }

    /**
     * Enviar push notification individual
     */
    private function sendPush($user, $title, $body, $data = [], $type = 'general', $userType = null)
    {
        $userType = $userType ?? ($user instanceof Admin ? 'admin' : 'client');

        // Criar registro no banco
        $notification = PushNotification::createForUser(
            $user->id,
            $userType,
            $title,
            $body,
            $data,
            $type,
            $user->fcm_token
        );

        // Enviar imediatamente se tiver token
        if ($user->fcm_token) {
            $result = $this->pushService->sendToUser($user->fcm_token, $title, $body, $data);
            
            if ($result['success']) {
                $notification->update([
                    'is_sent' => true,
                    'sent_at' => now()
                ]);
            } else {
                $notification->update([
                    'error_message' => $result['error']
                ]);
            }
        }

        return $notification;
    }

    /**
     * Calcular nível do usuário
     */
    private function calculateLevel($pontos)
    {
        if ($pontos >= 10000) return 'Diamante';
        if ($pontos >= 5000) return 'Ouro';
        if ($pontos >= 2000) return 'Prata';
        return 'Bronze';
    }

    /**
     * Processar fila de notificações
     */
    public function processQueue($limit = 100)
    {
        return $this->pushService->processQueue($limit);
    }

    /**
     * Obter estatísticas de notificações
     */
    public function getStats($days = 30)
    {
        return PushNotification::getStats($days);
    }

    /**
     * Marcar notificação como lida
     */
    public function markAsRead($notificationId, $userId)
    {
        $notification = PushNotification::where('id', $notificationId)
            ->where('user_id', $userId)
            ->first();

        if ($notification) {
            $notification->markAsRead();
            return true;
        }

        return false;
    }
}