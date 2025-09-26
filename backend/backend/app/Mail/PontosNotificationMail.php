<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class PontosNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public $pontos;
    public $empresa;
    public $tipo;

    public function __construct($user, $pontos, $empresa, $tipo = 'ganho')
    {
        $this->user = $user;
        $this->pontos = $pontos;
        $this->empresa = $empresa;
        $this->tipo = $tipo; // 'ganho' ou 'resgate'
    }

    public function build()
    {
        $subject = $this->tipo === 'ganho' 
            ? "🎉 Você ganhou {$this->pontos} pontos!" 
            : "✅ Resgate realizado - {$this->pontos} pontos";

        return $this->subject($subject)
                    ->view('emails.pontos-notification')
                    ->with([
                        'user' => $this->user,
                        'pontos' => $this->pontos,
                        'empresa' => $this->empresa,
                        'tipo' => $this->tipo
                    ]);
    }
}