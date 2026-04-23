<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PointsExpirationWarningMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public int $pontosExpirando,
        public string $dataExpiracao,
        public int $diasRestantes
    ) {}

    public function build()
    {
        return $this->subject("⚠️ Seus pontos estão prestes a expirar!")
            ->view('emails.points-expiration-warning')
            ->with([
                'userName' => $this->user->name,
                'pontosExpirando' => number_format($this->pontosExpirando, 0, ',', '.'),
                'dataExpiracao' => $this->dataExpiracao,
                'diasRestantes' => $this->diasRestantes,
                'pontosAtuais' => number_format($this->user->pontos, 0, ',', '.'),
            ]);
    }
}
