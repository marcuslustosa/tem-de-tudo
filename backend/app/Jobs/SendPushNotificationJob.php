<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\FirebaseNotificationService;
use App\Models\PushNotification;
use App\Models\AuditLog;

class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 30;

    private $notificationId;

    /**
     * Create a new job instance.
     */
    public function __construct($notificationId)
    {
        $this->notificationId = $notificationId;
    }

    /**
     * Execute the job.
     */
    public function handle(FirebaseNotificationService $firebaseService): void
    {
        $notification = PushNotification::find($this->notificationId);

        if (!$notification || $notification->is_sent || !$notification->fcm_token) {
            return;
        }

        try {
            $result = $firebaseService->sendToUser(
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

                AuditLog::logEvent('push_notification_sent', $notification->user_id, null, [
                    'notification_id' => $notification->id,
                    'type' => $notification->type,
                    'status' => 'success'
                ]);
            } else {
                $notification->update([
                    'error_message' => $result['error'] ?? 'Erro desconhecido'
                ]);

                AuditLog::logEvent('push_notification_failed', $notification->user_id, null, [
                    'notification_id' => $notification->id,
                    'type' => $notification->type,
                    'error' => $result['error'],
                    'status' => 'failed'
                ]);
            }

        } catch (\Exception $e) {
            $notification->update([
                'error_message' => $e->getMessage()
            ]);

            // Re-throw para tentar novamente
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $notification = PushNotification::find($this->notificationId);
        
        if ($notification) {
            $notification->update([
                'error_message' => 'Job failed after ' . $this->tries . ' attempts: ' . $exception->getMessage()
            ]);

            AuditLog::logEvent('push_notification_job_failed', $notification->user_id, null, [
                'notification_id' => $notification->id,
                'error' => $exception->getMessage(),
                'attempts' => $this->attempts(),
                'status' => 'permanently_failed'
            ]);
        }
    }
}