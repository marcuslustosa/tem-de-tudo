<?php

namespace Tests\Feature;

use App\Models\CompanySubscription;
use App\Models\Empresa;
use App\Models\Invoice;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingServiceTest extends TestCase
{
    use RefreshDatabase;

    private BillingService $billingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->billingService = app(BillingService::class);
    }

    public function test_create_subscription_with_trial_period(): void
    {
        $company = Empresa::factory()->create();

        $subscription = $this->billingService->createSubscription($company->id, 'basic');

        $this->assertNotNull($subscription);
        $this->assertEquals(CompanySubscription::STATUS_TRIAL, $subscription->status);
        $this->assertEquals('basic', $subscription->plan->name);
        $this->assertNotNull($subscription->trial_ends_at);
        $this->assertTrue($subscription->trial_ends_at->greaterThan(now()));
    }

    public function test_generate_invoice_creates_invoice_with_auto_number(): void
    {
        $company = Empresa::factory()->create();
        $subscription = $this->billingService->createSubscription($company->id, 'professional');

        $invoice = $this->billingService->generateInvoice($subscription, now()->addDays(30));

        $this->assertNotNull($invoice);
        $this->assertStringStartsWith('INV-' . now()->format('Y'), $invoice->invoice_number);
        $this->assertEquals(29900, $invoice->amount_cents);
        $this->assertEquals(29900, $invoice->total_cents);
        $this->assertEquals(Invoice::STATUS_PENDING, $invoice->status);
    }

    public function test_mark_invoice_paid_activates_subscription(): void
    {
        $company = Empresa::factory()->create();
        $subscription = $this->billingService->createSubscription($company->id, 'basic');
        $invoice = $this->billingService->generateInvoice($subscription, now()->addDays(5));

        $paidInvoice = $this->billingService->markInvoicePaid($invoice->id, [
            'payment_method' => 'credit_card',
            'transaction_id' => 'TXN123456',
        ]);

        $this->assertEquals(Invoice::STATUS_PAID, $paidInvoice->status);
        $this->assertEquals('credit_card', $paidInvoice->payment_method);
        $this->assertEquals('TXN123456', $paidInvoice->payment_id);

        $subscription->refresh();
        $this->assertEquals(CompanySubscription::STATUS_ACTIVE, $subscription->status);
    }

    public function test_process_overdue_transitions_to_past_due(): void
    {
        $company = Empresa::factory()->create();
        $subscription = $this->billingService->createSubscription($company->id, 'basic');
        $subscription->update(['status' => CompanySubscription::STATUS_ACTIVE]);

        $invoice = $this->billingService->generateInvoice($subscription, now()->subDays(6));

        $result = $this->billingService->processOverdueInvoices();

        $invoice->refresh();
        $subscription->refresh();

        $this->assertEquals(Invoice::STATUS_OVERDUE, $invoice->status);
        $this->assertEquals(CompanySubscription::STATUS_PAST_DUE, $subscription->status);
        $this->assertGreaterThanOrEqual(1, $result['overdue_marked']);
    }

    public function test_process_overdue_suspends_after_grace_period(): void
    {
        $company = Empresa::factory()->create();
        $subscription = $this->billingService->createSubscription($company->id, 'basic');
        $subscription->update(['status' => CompanySubscription::STATUS_PAST_DUE]);

        $this->billingService->generateInvoice($subscription, now()->subDays(13));

        // Primeira execucao marca overdue; segunda aplica suspensao por grace period.
        $this->billingService->processOverdueInvoices();
        $this->billingService->processOverdueInvoices();

        $subscription->refresh();
        $this->assertEquals(CompanySubscription::STATUS_SUSPENDED, $subscription->status);
    }

    public function test_can_operate_checks_subscription_status(): void
    {
        $company = Empresa::factory()->create();

        $result = $this->billingService->canOperate($company->id);
        $this->assertFalse($result['allowed']);

        $subscription = $this->billingService->createSubscription($company->id, 'basic');
        $result = $this->billingService->canOperate($company->id);
        $this->assertTrue($result['allowed']);

        $subscription->update(['status' => CompanySubscription::STATUS_SUSPENDED]);
        $result = $this->billingService->canOperate($company->id);
        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('suspensa', mb_strtolower($result['reason']));
    }

    public function test_trial_expiration_requires_payment(): void
    {
        $company = Empresa::factory()->create();
        $subscription = $this->billingService->createSubscription($company->id, 'basic');

        $subscription->update([
            'status' => CompanySubscription::STATUS_TRIAL,
            'trial_ends_at' => now()->subDay(),
        ]);

        $result = $this->billingService->canOperate($company->id);
        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('trial', mb_strtolower($result['reason']));
    }

    public function test_billing_notifications_sent_at_milestones(): void
    {
        $company = Empresa::factory()->create();
        $subscription = $this->billingService->createSubscription($company->id, 'basic');
        $invoice = $this->billingService->generateInvoice($subscription, now());

        $sent = $this->billingService->sendBillingNotifications();

        $this->assertIsArray($sent);
        $this->assertArrayHasKey('due_date', $sent);

        $this->assertDatabaseHas('billing_notifications', [
            'company_id' => $company->id,
            'invoice_id' => $invoice->id,
            'type' => 'due_date',
            'sent' => true,
        ]);
    }
}
