<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Models\AuditLog;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;

    private $mailable;
    private $recipient;
    private $userId;

    /**
     * Create a new job instance.
     */
    public function __construct($mailable, $recipient, $userId = null)
    {
        $this->mailable = $mailable;
        $this->recipient = $recipient;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Mail::to($this->recipient)->send($this->mailable);

            // Log de sucesso
            AuditLog::logEvent('email_sent', $this->userId, null, [
                'recipient' => $this->recipient,
                'mailable' => get_class($this->mailable),
                'status' => 'success'
            ]);

        } catch (\Exception $e) {
            // Log de erro
            AuditLog::logEvent('email_failed', $this->userId, null, [
                'recipient' => $this->recipient,
                'mailable' => get_class($this->mailable),
                'error' => $e->getMessage(),
                'status' => 'failed'
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
        AuditLog::logEvent('email_job_failed', $this->userId, null, [
            'recipient' => $this->recipient,
            'mailable' => get_class($this->mailable),
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
            'status' => 'permanently_failed'
        ]);
    }
}