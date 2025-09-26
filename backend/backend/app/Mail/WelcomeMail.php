<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class WelcomeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public $userType;

    public function __construct($user, $userType = 'client')
    {
        $this->user = $user;
        $this->userType = $userType;
    }

    public function build()
    {
        $subject = $this->userType === 'company' 
            ? 'Bem-vindo à TemDeTudo - Empresa Parceira!' 
            : 'Bem-vindo à TemDeTudo!';

        return $this->subject($subject)
                    ->view('emails.welcome')
                    ->with([
                        'user' => $this->user,
                        'userType' => $this->userType
                    ]);
    }
}