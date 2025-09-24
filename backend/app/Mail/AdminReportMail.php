<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class AdminReportMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $report;
    public $admin;
    public $period;

    public function __construct($report, $admin, $period = 'daily')
    {
        $this->report = $report;
        $this->admin = $admin;
        $this->period = $period;
    }

    public function build()
    {
        $periods = [
            'daily' => 'Diário',
            'weekly' => 'Semanal', 
            'monthly' => 'Mensal'
        ];

        $periodName = $periods[$this->period] ?? 'Personalizado';
        
        return $this->subject("📊 Relatório {$periodName} - TemDeTudo")
                    ->view('emails.admin-report')
                    ->with([
                        'report' => $this->report,
                        'admin' => $this->admin,
                        'period' => $this->period,
                        'periodName' => $periodName
                    ]);
    }
}