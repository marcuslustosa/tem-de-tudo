<?php

namespace App\Console\Commands;

use App\Services\BillingService;
use Illuminate\Console\Command;

class ProcessBilling extends Command
{
    protected $signature = 'billing:process';
    protected $description = 'Processa billing diario: faturas, retries, conciliacao e notificacoes';

    public function __construct(
        private readonly BillingService $billingService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Iniciando processamento de cobrancas...');

        $this->info('1/5 Gerando faturas mensais...');
        $generated = $this->billingService->generateMonthlyInvoices();
        $this->info("OK: {$generated} faturas geradas");

        $this->info('2/5 Processando faturas vencidas...');
        $overdue = $this->billingService->processOverdueInvoices();
        $this->info(sprintf(
            'OK: overdue=%d past_due=%d suspended=%d',
            $overdue['overdue_marked'],
            $overdue['subscriptions_past_due'],
            $overdue['subscriptions_suspended']
        ));

        $this->info('3/5 Executando retries de pagamento...');
        $retries = $this->billingService->processPaymentRetries();
        $this->info(sprintf(
            'OK: attempted=%d recovered=%d rescheduled=%d exhausted=%d',
            $retries['attempted'],
            $retries['recovered'],
            $retries['rescheduled'],
            $retries['exhausted']
        ));

        $this->info('4/5 Conciliando faturas pendentes...');
        $reconciled = $this->billingService->reconcilePendingInvoices();
        $this->info(sprintf(
            'OK: checked=%d paid=%d canceled=%d unchanged=%d',
            $reconciled['checked'],
            $reconciled['paid'],
            $reconciled['canceled'],
            $reconciled['unchanged']
        ));

        $this->info('5/5 Enviando notificacoes...');
        $notifications = $this->billingService->sendBillingNotifications();
        $this->info('OK: ' . array_sum($notifications) . ' notificacoes enviadas');

        $this->info('Processamento de billing concluido.');
        return self::SUCCESS;
    }
}

