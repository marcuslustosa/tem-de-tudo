<?php

namespace App\Services;

use App\Models\CompanySubscription;
use App\Models\Empresa;
use App\Models\Invoice;
use App\Models\SubscriptionPlan;
use App\Models\BillingNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * BillingService - Gerencia cobrança de empresas.
 * 
 * Funcionalidades:
 * - Criar assinaturas com trial
 * - Gerar faturas automáticas
 * - Bloquear empresas inadimplentes
 * - Enviar notificações de cobrança
 */
class BillingService
{
    /**
     * Cria assinatura para empresa nova
     * 
     * @param int $companyId
     * @param string $planName (basic, professional, enterprise)
     * @return CompanySubscription
     */
    public function createSubscription(int $companyId, string $planName = 'basic'): CompanySubscription
    {
        $plan = SubscriptionPlan::where('name', $planName)->firstOrFail();
        Empresa::findOrFail($companyId);

        $trialEndsAt = Carbon::now()->addDays($plan->trial_days);

        return CompanySubscription::create([
            'company_id' => $companyId,
            'subscription_plan_id' => $plan->id,
            'status' => CompanySubscription::STATUS_TRIAL,
            'started_at' => Carbon::now(),
            'trial_ends_at' => $trialEndsAt,
            'current_period_start' => Carbon::now(),
            'current_period_end' => $trialEndsAt,
            'billing_day' => 1,  // Primeiro dia do mês
            'grace_period_days' => 7,
        ]);
    }

    /**
     * Gera fatura para período de cobrança
     * 
     * @param CompanySubscription $subscription
     * @param Carbon|null $dueDate
     * @return Invoice
     */
    public function generateInvoice(CompanySubscription $subscription, ?Carbon $dueDate = null): Invoice
    {
        $plan = $subscription->plan;
        
        // Define vencimento
        if (!$dueDate) {
            $dueDate = Carbon::now()->addDays(5); // 5 dias para pagar
        }

        // Calcula desconto (se houver)
        $discountCents = 0;
        // TODO: Implementar lógica de desconto (ex: plano anual, cupom)

        $totalCents = $plan->monthly_price_cents - $discountCents;

        return Invoice::create([
            'subscription_id' => $subscription->id,
            'company_id' => $subscription->company_id,
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'amount_cents' => $plan->monthly_price_cents,
            'discount_cents' => $discountCents,
            'total_cents' => $totalCents,
            'status' => Invoice::STATUS_PENDING,
            'due_date' => $dueDate,
        ]);
    }

    /**
     * Marca fatura como paga
     * 
     * @param int $invoiceId
     * @param array $paymentData
     * @return Invoice
     */
    public function markInvoicePaid(int $invoiceId, array $paymentData = []): Invoice
    {
        return DB::transaction(function () use ($invoiceId, $paymentData) {
            $invoice = Invoice::lockForUpdate()->findOrFail($invoiceId);

            if ($invoice->isPaid()) {
                return $invoice; // Já paga, retorna
            }

            $invoice->update([
                'status' => Invoice::STATUS_PAID,
                'paid_at' => now(),
                'payment_method' => $paymentData['method'] ?? $paymentData['payment_method'] ?? null,
                'payment_id' => $paymentData['id'] ?? $paymentData['transaction_id'] ?? null,
                'payment_metadata' => $paymentData['metadata'] ?? null,
            ]);

            // Atualiza status da assinatura
            $subscription = $invoice->subscription;
            if ($subscription->status !== CompanySubscription::STATUS_ACTIVE) {
                $subscription->update([
                    'status' => CompanySubscription::STATUS_ACTIVE,
                    'current_period_start' => Carbon::now(),
                    'current_period_end' => Carbon::now()->addMonth(),
                ]);
            }

            return $invoice;
        });
    }

    /**
     * Processa faturas vencidas e bloqueia empresas
     * 
     * Deve rodar diariamente via cron/scheduler
     */
    public function processOverdueInvoices(): array
    {
        $processed = [
            'overdue_marked' => 0,
            'subscriptions_past_due' => 0,
            'subscriptions_suspended' => 0,
        ];

        // 1. Marca faturas vencidas
        $overdueInvoices = Invoice::where('status', Invoice::STATUS_PENDING)
            ->where('due_date', '<', Carbon::now())
            ->get();

        foreach ($overdueInvoices as $invoice) {
            $invoice->update(['status' => Invoice::STATUS_OVERDUE]);
            $processed['overdue_marked']++;
        }

        // 2. Atualiza assinaturas para past_due
        $subscriptionsWithOverdue = CompanySubscription::whereHas('invoices', function ($q) {
            $q->where('status', Invoice::STATUS_OVERDUE);
        })->where('status', CompanySubscription::STATUS_ACTIVE)->get();

        foreach ($subscriptionsWithOverdue as $subscription) {
            $subscription->update(['status' => CompanySubscription::STATUS_PAST_DUE]);
            $processed['subscriptions_past_due']++;
        }

        // 3. Suspende assinaturas apos grace period
        $toSuspend = CompanySubscription::where('status', CompanySubscription::STATUS_PAST_DUE)->get();

        foreach ($toSuspend as $subscription) {
            $graceLimit = Carbon::now()->subDays((int) $subscription->grace_period_days);

            $hasExpiredDebt = $subscription->invoices()
                ->whereIn('status', [Invoice::STATUS_PENDING, Invoice::STATUS_OVERDUE])
                ->where('due_date', '<', $graceLimit)
                ->exists();

            if ($hasExpiredDebt) {
                $subscription->update(['status' => CompanySubscription::STATUS_SUSPENDED]);
                $processed['subscriptions_suspended']++;
            }
        }

        return $processed;
    }

    /**
     * Envia notificações de cobrança
     * 
     * Deve rodar diariamente via cron/scheduler
     */
    public function sendBillingNotifications(): array
    {
        $sent = [
            'reminder_3_days' => 0,
            'reminder_1_day' => 0,
            'due_date' => 0,
            'overdue_3_days' => 0,
            'overdue_7_days' => 0,
        ];

        // Faturas pendentes
        $pendingInvoices = Invoice::where('status', Invoice::STATUS_PENDING)->get();

        foreach ($pendingInvoices as $invoice) {
            $daysUntilDue = $invoice->daysUntilDue();

            // Lembrete 3 dias antes
            if ($daysUntilDue == 3) {
                $this->sendNotification($invoice, BillingNotification::TYPE_REMINDER_3_DAYS);
                $sent['reminder_3_days']++;
            }

            // Lembrete 1 dia antes
            if ($daysUntilDue == 1) {
                $this->sendNotification($invoice, BillingNotification::TYPE_REMINDER_1_DAY);
                $sent['reminder_1_day']++;
            }

            // No vencimento
            if ($daysUntilDue == 0) {
                $this->sendNotification($invoice, BillingNotification::TYPE_DUE_DATE);
                $sent['due_date']++;
            }
        }

        // Faturas vencidas
        $overdueInvoices = Invoice::where('status', Invoice::STATUS_OVERDUE)->get();

        foreach ($overdueInvoices as $invoice) {
            $daysSinceDue = $invoice->daysSinceDue();

            // 3 dias após vencimento
            if ($daysSinceDue == 3) {
                $this->sendNotification($invoice, BillingNotification::TYPE_OVERDUE_3_DAYS);
                $sent['overdue_3_days']++;
            }

            // 7 dias - suspensão
            if ($daysSinceDue == 7) {
                $this->sendNotification($invoice, BillingNotification::TYPE_OVERDUE_7_DAYS);
                $sent['overdue_7_days']++;
            }
        }

        return $sent;
    }

    /**
     * Envia notificação individual
     */
    protected function sendNotification(Invoice $invoice, string $type): void
    {
        // Verifica se já enviou
        $exists = BillingNotification::where('invoice_id', $invoice->id)
            ->where('type', $type)
            ->where('sent', true)
            ->exists();

        if ($exists) {
            return; // Já enviou
        }

        $notification = BillingNotification::create([
            'invoice_id' => $invoice->id,
            'company_id' => $invoice->company_id,
            'type' => $type,
            'channel' => 'email',  // TODO: Adicionar push
            'sent' => false,
        ]);

        try {
            // TODO: Implementar envio real de email
            // Mail::to($invoice->company->email)->send(new InvoiceNotification($invoice, $type));

            Log::info("Billing notification sent", [
                'invoice_id' => $invoice->id,
                'type' => $type,
                'company' => $invoice->company->nome,
            ]);

            $notification->update([
                'sent' => true,
                'sent_at' => now(),
            ]);
        } catch (\Exception $e) {
            $notification->update([
                'error' => $e->getMessage(),
            ]);

            Log::error("Failed to send billing notification", [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Gera faturas mensais para todas assinaturas ativas
     * 
     * Deve rodar no dia de cobrança de cada empresa
     */
    public function generateMonthlyInvoices(): int
    {
        $generated = 0;
        $today = Carbon::now()->day;

        // Busca assinaturas que devem ser cobradas hoje
        $subscriptions = CompanySubscription::where('status', CompanySubscription::STATUS_ACTIVE)
            ->where('billing_day', $today)
            ->get();

        foreach ($subscriptions as $subscription) {
            // Verifica se já tem fatura pendente para este período
            $hasInvoice = $subscription->invoices()
                ->where('status', Invoice::STATUS_PENDING)
                ->where('due_date', '>=', Carbon::now())
                ->exists();

            if (!$hasInvoice) {
                $this->generateInvoice($subscription);
                $generated++;
            }
        }

        return $generated;
    }

    /**
     * Verifica se empresa pode operar
     * 
     * @param int $companyId
     * @return array ['allowed' => bool, 'reason' => string|null]
     */
    public function canOperate(int $companyId): array
    {
        $subscription = CompanySubscription::where('company_id', $companyId)
            ->orderBy('id', 'desc')
            ->first();

        if (!$subscription) {
            return [
                'allowed' => false,
                'reason' => 'Nenhuma assinatura encontrada',
            ];
        }

        if (
            $subscription->status === CompanySubscription::STATUS_TRIAL
            && $subscription->trial_ends_at
            && Carbon::parse($subscription->trial_ends_at)->isPast()
        ) {
            return [
                'allowed' => false,
                'reason' => 'Período de trial expirado. Realize o pagamento para continuar.',
            ];
        }

        if ($subscription->isActive()) {
            return ['allowed' => true, 'reason' => null];
        }

        if ($subscription->status === CompanySubscription::STATUS_PAST_DUE) {
            return [
                'allowed' => true, // Ainda permite com aviso
                'reason' => 'Pagamento em atraso. Sistema será bloqueado em breve.',
            ];
        }

        return [
            'allowed' => false,
            'reason' => 'Assinatura suspensa. Entre em contato com o suporte.',
        ];
    }
}
