<?php

namespace App\Services;

use App\Mail\BillingNotificationMail;
use App\Models\BillingEvent;
use App\Models\BillingNotification;
use App\Models\CompanySubscription;
use App\Models\Empresa;
use App\Models\Invoice;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class BillingService
{
    public function createSubscription(int $companyId, string $planName = 'basic'): CompanySubscription
    {
        $plan = SubscriptionPlan::where('name', $planName)->firstOrFail();
        Empresa::findOrFail($companyId);

        $trialEndsAt = Carbon::now()->addDays($plan->trial_days);

        $subscription = CompanySubscription::create([
            'company_id' => $companyId,
            'subscription_plan_id' => $plan->id,
            'status' => CompanySubscription::STATUS_TRIAL,
            'started_at' => Carbon::now(),
            'trial_ends_at' => $trialEndsAt,
            'current_period_start' => Carbon::now(),
            'current_period_end' => $trialEndsAt,
            'billing_day' => 1,
            'grace_period_days' => 7,
        ]);

        $this->recordEvent('subscription_created', [
            'plan' => $planName,
            'status' => $subscription->status,
        ], null, $subscription, $companyId);

        return $subscription;
    }

    public function generateInvoice(CompanySubscription $subscription, ?Carbon $dueDate = null): Invoice
    {
        $plan = $subscription->plan;
        $dueDate = $dueDate ?: Carbon::now()->addDays(5);

        $discountCents = 0;
        $totalCents = $plan->monthly_price_cents - $discountCents;

        $invoice = Invoice::create([
            'subscription_id' => $subscription->id,
            'company_id' => $subscription->company_id,
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'amount_cents' => $plan->monthly_price_cents,
            'discount_cents' => $discountCents,
            'total_cents' => $totalCents,
            'status' => Invoice::STATUS_PENDING,
            'due_date' => $dueDate,
            'reconciliation_status' => 'pending',
        ]);

        $this->recordEvent('invoice_generated', [
            'invoice_id' => $invoice->id,
            'total_cents' => $invoice->total_cents,
            'due_date' => $invoice->due_date?->toDateString(),
        ], $invoice, $subscription, $subscription->company_id);

        return $invoice;
    }

    public function markInvoicePaid(int $invoiceId, array $paymentData = []): Invoice
    {
        /** @var Invoice $invoice */
        $invoice = DB::transaction(function () use ($invoiceId, $paymentData) {
            $invoice = Invoice::lockForUpdate()->findOrFail($invoiceId);

            if ($invoice->isPaid()) {
                return $invoice;
            }

            $paymentMetadata = $paymentData['metadata'] ?? $invoice->payment_metadata;
            if (is_array($paymentMetadata)) {
                $paymentMetadata['paid_via'] = $paymentData['source'] ?? 'manual';
                $paymentMetadata['paid_at'] = now()->toIso8601String();
            }

            $invoice->update([
                'status' => Invoice::STATUS_PAID,
                'paid_at' => now(),
                'payment_method' => $paymentData['method'] ?? $paymentData['payment_method'] ?? $invoice->payment_method,
                'payment_id' => $paymentData['id'] ?? $paymentData['transaction_id'] ?? $invoice->payment_id,
                'payment_metadata' => $paymentMetadata,
                'reconciliation_status' => 'reconciled',
                'external_status' => $paymentData['external_status'] ?? $invoice->external_status ?? 'paid',
                'reconciled_at' => now(),
                'next_retry_at' => null,
                'last_failure_reason' => null,
            ]);

            $subscription = $invoice->subscription;
            if ($subscription->status !== CompanySubscription::STATUS_ACTIVE) {
                $subscription->update([
                    'status' => CompanySubscription::STATUS_ACTIVE,
                    'current_period_start' => Carbon::now(),
                    'current_period_end' => Carbon::now()->addMonth(),
                ]);
            }

            return $invoice->fresh();
        });

        $this->recordEvent('invoice_paid', [
            'payment_method' => $invoice->payment_method,
            'payment_id' => $invoice->payment_id,
        ], $invoice, $invoice->subscription, $invoice->company_id);

        return $invoice;
    }

    public function processOverdueInvoices(): array
    {
        $processed = [
            'overdue_marked' => 0,
            'subscriptions_past_due' => 0,
            'subscriptions_suspended' => 0,
        ];

        $overdueInvoices = Invoice::where('status', Invoice::STATUS_PENDING)
            ->where('due_date', '<', Carbon::now())
            ->get();

        foreach ($overdueInvoices as $invoice) {
            $invoice->update([
                'status' => Invoice::STATUS_OVERDUE,
                'reconciliation_status' => 'pending',
            ]);
            $processed['overdue_marked']++;

            $this->recordEvent('invoice_marked_overdue', [
                'invoice_id' => $invoice->id,
                'due_date' => $invoice->due_date?->toDateString(),
            ], $invoice, $invoice->subscription, $invoice->company_id, 'warning');
        }

        $subscriptionsWithOverdue = CompanySubscription::whereHas('invoices', function ($q) {
            $q->where('status', Invoice::STATUS_OVERDUE);
        })->where('status', CompanySubscription::STATUS_ACTIVE)->get();

        foreach ($subscriptionsWithOverdue as $subscription) {
            $subscription->update(['status' => CompanySubscription::STATUS_PAST_DUE]);
            $processed['subscriptions_past_due']++;

            $this->recordEvent('subscription_past_due', [
                'subscription_id' => $subscription->id,
            ], null, $subscription, $subscription->company_id, 'warning');
        }

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

                $this->recordEvent('subscription_suspended', [
                    'subscription_id' => $subscription->id,
                    'grace_period_days' => $subscription->grace_period_days,
                ], null, $subscription, $subscription->company_id, 'critical');
            }
        }

        return $processed;
    }

    public function processPaymentRetries(): array
    {
        $result = [
            'attempted' => 0,
            'recovered' => 0,
            'rescheduled' => 0,
            'exhausted' => 0,
        ];

        if (!(bool) config('billing.payment_retry.enabled', true)) {
            return $result;
        }

        $maxAttempts = max(1, (int) config('billing.payment_retry.max_attempts', 3));
        $backoffDays = $this->retryBackoffDays();

        $candidates = Invoice::query()
            ->whereIn('status', [Invoice::STATUS_PENDING, Invoice::STATUS_OVERDUE])
            ->where(function ($query) {
                $query->whereNull('next_retry_at')
                    ->orWhere('next_retry_at', '<=', now());
            })
            ->where('retry_count', '<', $maxAttempts)
            ->orderBy('due_date')
            ->get();

        foreach ($candidates as $invoice) {
            $result['attempted']++;
            $attemptNumber = ((int) $invoice->retry_count) + 1;
            $externalStatus = $this->resolveExternalStatus($invoice);

            if ($this->isPaidStatus($externalStatus)) {
                $this->markInvoicePaid($invoice->id, [
                    'external_status' => $externalStatus,
                    'source' => 'retry',
                    'metadata' => $invoice->payment_metadata,
                    'id' => $invoice->payment_id,
                ]);

                $result['recovered']++;
                $this->recordEvent('payment_retry_recovered', [
                    'invoice_id' => $invoice->id,
                    'attempt' => $attemptNumber,
                    'external_status' => $externalStatus,
                ], $invoice, $invoice->subscription, $invoice->company_id);
                continue;
            }

            $hasAttemptsLeft = $attemptNumber < $maxAttempts;
            $invoice->update([
                'retry_count' => $attemptNumber,
                'last_retry_at' => now(),
                'next_retry_at' => $hasAttemptsLeft ? now()->addDays($this->retryOffsetDays($attemptNumber, $backoffDays)) : null,
                'last_failure_reason' => sprintf('Gateway status: %s', $externalStatus ?? 'unknown'),
            ]);

            if ($hasAttemptsLeft) {
                $result['rescheduled']++;
                $this->recordEvent('payment_retry_scheduled', [
                    'invoice_id' => $invoice->id,
                    'attempt' => $attemptNumber,
                    'next_retry_at' => optional($invoice->next_retry_at)->toIso8601String(),
                    'external_status' => $externalStatus,
                ], $invoice, $invoice->subscription, $invoice->company_id, 'warning');
            } else {
                $result['exhausted']++;
                $this->recordEvent('payment_retry_exhausted', [
                    'invoice_id' => $invoice->id,
                    'attempt' => $attemptNumber,
                    'external_status' => $externalStatus,
                ], $invoice, $invoice->subscription, $invoice->company_id, 'critical');
            }
        }

        return $result;
    }

    public function reconcilePendingInvoices(): array
    {
        $result = [
            'checked' => 0,
            'paid' => 0,
            'canceled' => 0,
            'unchanged' => 0,
        ];

        if (!(bool) config('billing.reconciliation.enabled', true)) {
            return $result;
        }

        $lookbackDays = max(1, (int) config('billing.reconciliation.lookback_days', 30));
        $threshold = now()->subDays($lookbackDays);

        $invoices = Invoice::query()
            ->whereIn('status', [Invoice::STATUS_PENDING, Invoice::STATUS_OVERDUE])
            ->whereNotNull('payment_id')
            ->where('updated_at', '>=', $threshold)
            ->orderByDesc('updated_at')
            ->get();

        foreach ($invoices as $invoice) {
            $result['checked']++;
            $externalStatus = $this->resolveExternalStatus($invoice);

            if ($this->isPaidStatus($externalStatus)) {
                $this->markInvoicePaid($invoice->id, [
                    'external_status' => $externalStatus,
                    'source' => 'reconciliation',
                    'metadata' => $invoice->payment_metadata,
                    'id' => $invoice->payment_id,
                ]);

                $result['paid']++;
                $this->recordEvent('invoice_reconciled_paid', [
                    'invoice_id' => $invoice->id,
                    'external_status' => $externalStatus,
                ], $invoice, $invoice->subscription, $invoice->company_id);
                continue;
            }

            if ($this->isCanceledStatus($externalStatus)) {
                $invoice->update([
                    'status' => Invoice::STATUS_CANCELED,
                    'reconciliation_status' => 'reconciled',
                    'external_status' => $externalStatus,
                    'reconciled_at' => now(),
                    'next_retry_at' => null,
                ]);

                $result['canceled']++;
                $this->recordEvent('invoice_reconciled_canceled', [
                    'invoice_id' => $invoice->id,
                    'external_status' => $externalStatus,
                ], $invoice, $invoice->subscription, $invoice->company_id, 'warning');
                continue;
            }

            $invoice->update([
                'reconciliation_status' => $externalStatus ? 'pending_confirmation' : 'no_signal',
                'external_status' => $externalStatus,
                'reconciled_at' => now(),
            ]);

            $result['unchanged']++;
            $this->recordEvent('invoice_reconciled_no_change', [
                'invoice_id' => $invoice->id,
                'external_status' => $externalStatus,
            ], $invoice, $invoice->subscription, $invoice->company_id);
        }

        return $result;
    }

    public function sendBillingNotifications(): array
    {
        $sent = [
            'reminder_3_days' => 0,
            'reminder_1_day' => 0,
            'due_date' => 0,
            'overdue_3_days' => 0,
            'overdue_7_days' => 0,
        ];

        $pendingInvoices = Invoice::where('status', Invoice::STATUS_PENDING)->get();

        foreach ($pendingInvoices as $invoice) {
            $daysUntilDue = $invoice->daysUntilDue();

            if ($daysUntilDue === 3) {
                $this->sendNotification($invoice, BillingNotification::TYPE_REMINDER_3_DAYS);
                $sent['reminder_3_days']++;
            }

            if ($daysUntilDue === 1) {
                $this->sendNotification($invoice, BillingNotification::TYPE_REMINDER_1_DAY);
                $sent['reminder_1_day']++;
            }

            if ($daysUntilDue === 0) {
                $this->sendNotification($invoice, BillingNotification::TYPE_DUE_DATE);
                $sent['due_date']++;
            }
        }

        $overdueInvoices = Invoice::where('status', Invoice::STATUS_OVERDUE)->get();

        foreach ($overdueInvoices as $invoice) {
            $daysSinceDue = $invoice->daysSinceDue();

            if ($daysSinceDue === 3) {
                $this->sendNotification($invoice, BillingNotification::TYPE_OVERDUE_3_DAYS);
                $sent['overdue_3_days']++;
            }

            if ($daysSinceDue === 7) {
                $this->sendNotification($invoice, BillingNotification::TYPE_OVERDUE_7_DAYS);
                $sent['overdue_7_days']++;
            }
        }

        return $sent;
    }

    protected function sendNotification(Invoice $invoice, string $type): void
    {
        $exists = BillingNotification::where('invoice_id', $invoice->id)
            ->where('type', $type)
            ->where('sent', true)
            ->exists();

        if ($exists) {
            return;
        }

        $notification = BillingNotification::create([
            'invoice_id' => $invoice->id,
            'company_id' => $invoice->company_id,
            'type' => $type,
            'channel' => 'email',
            'sent' => false,
        ]);

        try {
            $recipient = $invoice->company?->owner?->email;
            if (!$recipient) {
                throw new \RuntimeException("Empresa #{$invoice->company_id} sem email de contato do responsavel.");
            }

            Mail::to($recipient)->send(new BillingNotificationMail($invoice, $type));

            $notification->update([
                'sent' => true,
                'sent_at' => now(),
            ]);

            Log::info('Billing notification sent', [
                'invoice_id' => $invoice->id,
                'type' => $type,
                'company_id' => $invoice->company_id,
                'recipient' => $recipient,
            ]);

            $this->recordEvent('billing_notification_sent', [
                'type' => $type,
                'recipient' => $recipient,
                'notification_id' => $notification->id,
            ], $invoice, $invoice->subscription, $invoice->company_id);
        } catch (\Throwable $e) {
            $notification->update([
                'error' => $e->getMessage(),
            ]);

            Log::error('Failed to send billing notification', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            $this->recordEvent('billing_notification_failed', [
                'type' => $type,
                'error' => $e->getMessage(),
                'notification_id' => $notification->id,
            ], $invoice, $invoice->subscription, $invoice->company_id, 'error');
        }
    }

    public function generateMonthlyInvoices(): int
    {
        $generated = 0;
        $today = Carbon::now()->day;

        $subscriptions = CompanySubscription::where('status', CompanySubscription::STATUS_ACTIVE)
            ->where('billing_day', $today)
            ->get();

        foreach ($subscriptions as $subscription) {
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

    public function canOperate(int $companyId): array
    {
        try {
            $subscriptionTable = (new CompanySubscription())->getTable();
            if (!Schema::hasTable($subscriptionTable)) {
                return [
                    'allowed' => true,
                    'reason' => 'Sistema de assinatura em modo de compatibilidade.',
                ];
            }

            if (!Schema::hasColumn($subscriptionTable, 'company_id') || !Schema::hasColumn($subscriptionTable, 'status')) {
                return [
                    'allowed' => true,
                    'reason' => 'Sistema de assinatura em modo de compatibilidade.',
                ];
            }

            $query = CompanySubscription::query()->where('company_id', $companyId);
            if (Schema::hasColumn($subscriptionTable, 'id')) {
                $query->orderByDesc('id');
            }

            $subscription = $query->first();

            if (!$subscription) {
                return [
                    'allowed' => false,
                    'reason' => 'Nenhuma assinatura encontrada',
                ];
            }

            $trialExpired = false;
            if (
                Schema::hasColumn($subscriptionTable, 'trial_ends_at')
                && (string) $subscription->status === CompanySubscription::STATUS_TRIAL
                && !empty($subscription->trial_ends_at)
            ) {
                $trialExpired = Carbon::parse($subscription->trial_ends_at)->isPast();
            }

            if ($trialExpired) {
                return [
                    'allowed' => false,
                    'reason' => 'Periodo de trial expirado. Realize o pagamento para continuar.',
                ];
            }

            if ($subscription->isActive()) {
                return ['allowed' => true, 'reason' => null];
            }

            if ((string) $subscription->status === CompanySubscription::STATUS_PAST_DUE) {
                return [
                    'allowed' => true,
                    'reason' => 'Pagamento em atraso. Sistema sera bloqueado em breve.',
                ];
            }

            return [
                'allowed' => false,
                'reason' => 'Assinatura suspensa. Entre em contato com o suporte.',
            ];
        } catch (\Throwable $e) {
            Log::warning('BillingService::canOperate em modo de compatibilidade', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);

            return [
                'allowed' => true,
                'reason' => 'Sistema de assinatura em modo de compatibilidade.',
            ];
        }
    }

    private function resolveExternalStatus(Invoice $invoice): ?string
    {
        $metadata = $invoice->payment_metadata;
        if (!is_array($metadata)) {
            return null;
        }

        $candidates = [
            $metadata['mock_gateway_status'] ?? null,
            $metadata['gateway_status'] ?? null,
            $metadata['external_status'] ?? null,
            $metadata['status'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (!is_string($candidate)) {
                continue;
            }

            $value = strtolower(trim($candidate));
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function isPaidStatus(?string $status): bool
    {
        return in_array($status, ['paid', 'approved', 'authorized', 'succeeded', 'success'], true);
    }

    private function isCanceledStatus(?string $status): bool
    {
        return in_array($status, ['canceled', 'cancelled', 'refunded', 'rejected', 'expired'], true);
    }

    private function retryBackoffDays(): array
    {
        $config = config('billing.payment_retry.backoff_days', [1, 3, 5]);
        if (is_array($config)) {
            $values = $config;
        } else {
            $values = explode(',', (string) $config);
        }

        $days = [];
        foreach ($values as $value) {
            $parsed = (int) trim((string) $value);
            if ($parsed > 0) {
                $days[] = $parsed;
            }
        }

        return $days !== [] ? array_values($days) : [1, 3, 5];
    }

    private function retryOffsetDays(int $attemptNumber, array $backoffDays): int
    {
        $index = max(0, $attemptNumber - 1);
        return $backoffDays[$index] ?? end($backoffDays);
    }

    private function recordEvent(
        string $eventType,
        array $payload = [],
        ?Invoice $invoice = null,
        ?CompanySubscription $subscription = null,
        ?int $companyId = null,
        string $level = 'info'
    ): void {
        static $eventsTableExists = null;
        if ($eventsTableExists === null) {
            $eventsTableExists = Schema::hasTable('billing_events');
        }

        if (!$eventsTableExists) {
            return;
        }

        try {
            $resolvedCompanyId = $companyId
                ?? $invoice?->company_id
                ?? $subscription?->company_id;

            if (!$resolvedCompanyId) {
                return;
            }

            BillingEvent::create([
                'company_id' => $resolvedCompanyId,
                'subscription_id' => $subscription?->id ?? $invoice?->subscription_id,
                'invoice_id' => $invoice?->id,
                'event_type' => $eventType,
                'level' => $level,
                'payload' => $payload,
                'occurred_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Falha ao registrar evento de billing', [
                'event_type' => $eventType,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
