<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BillingNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public string $notificationType
    ) {}

    public function build()
    {
        $typeMap = [
            'reminder_3_days' => 'Lembrete: vencimento em 3 dias',
            'reminder_1_day' => 'Lembrete: vencimento amanha',
            'due_date' => 'Fatura vence hoje',
            'overdue_3_days' => 'Fatura em atraso ha 3 dias',
            'overdue_7_days' => 'Fatura em atraso ha 7 dias',
        ];

        $subject = $typeMap[$this->notificationType] ?? 'Atualizacao da sua fatura';

        return $this->subject("[Tem de Tudo] {$subject}")
            ->view('emails.billing-notification')
            ->with([
                'invoice' => $this->invoice,
                'companyName' => $this->invoice->company?->nome ?? 'Parceiro',
                'notificationType' => $this->notificationType,
                'subjectLabel' => $subject,
                'formattedTotal' => $this->invoice->formatted_total,
                'dueDate' => optional($this->invoice->due_date)->format('d/m/Y'),
                'paymentUrl' => $this->invoice->payment_url,
            ]);
    }
}

