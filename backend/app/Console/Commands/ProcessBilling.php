<?php

namespace App\Console\Commands;

use App\Services\BillingService;
use Illuminate\Console\Command;

/**
 * Processa cobranças diárias:
 * - Gera faturas mensais
 * - Marca faturas vencidas
 * - Bloqueia empresas inadimplentes
 * - Envia notificações
 */
class ProcessBilling extends Command
{
    protected $signature = 'billing:process';
    protected $description = 'Processa cobranças diárias (faturas, vencimentos, notificações)';

    public function __construct(
        private readonly BillingService $billingService
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('Iniciando processamento de cobranças...');

        // 1. Gera faturas mensais
        $this->info('Gerando faturas mensais...');
        $generated = $this->billingService->generateMonthlyInvoices();
        $this->info("✅ {$generated} faturas geradas");

        // 2. Processa vencimentos
        $this->info('Processando faturas vencidas...');
        $overdue = $this->billingService->processOverdueInvoices();
        $this->info("✅ Marcadas: {$overdue['overdue_marked']}, Past due: {$overdue['subscriptions_past_due']}, Suspensas: {$overdue['subscriptions_suspended']}");

        // 3. Envia notificações
        $this->info('Enviando notificações...');
        $notifications = $this->billingService->sendBillingNotifications();
        $total = array_sum($notifications);
        $this->info("✅ {$total} notificações enviadas");

        $this->info('Processamento concluído!');
        return 0;
    }
}
