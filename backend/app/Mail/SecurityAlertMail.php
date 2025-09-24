<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class SecurityAlertMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public $event;
    public $details;

    public function __construct($user, $event, $details = [])
    {
        $this->user = $user;
        $this->event = $event;
        $this->details = $details;
    }

    public function build()
    {
        $subjects = [
            'login_suspicious' => '🔒 Alerta de Segurança - Login Suspeito',
            'password_changed' => '🔐 Sua senha foi alterada',
            'account_locked' => '⚠️ Conta temporariamente bloqueada',
            'new_device' => '📱 Novo dispositivo acessou sua conta'
        ];

        $subject = $subjects[$this->event] ?? '🔔 Alerta de Segurança';

        return $this->subject($subject)
                    ->view('emails.security-alert')
                    ->with([
                        'user' => $this->user,
                        'event' => $this->event,
                        'details' => $this->details
                    ]);
    }
}