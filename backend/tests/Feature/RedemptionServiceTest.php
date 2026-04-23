<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\User;
use App\Services\RedemptionService;
use App\Services\LedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedemptionServiceTest extends TestCase
{
    use RefreshDatabase;

    private RedemptionService $redemptionService;
    private LedgerService $ledgerService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redemptionService = app(RedemptionService::class);
        $this->ledgerService = app(LedgerService::class);
    }

    public function test_request_redemption_creates_intent_and_reserves_points(): void
    {
        $user = User::factory()->create(['pontos' => 0]);
        $company = Empresa::factory()->create();
        
        $this->ledgerService->credit($user->id, 500, 'Initial credit');
        
        $intent = $this->redemptionService->requestRedemption($user->id, $company->id, 200, [
            'pdv_operator_id' => 1,
            'pdv_terminal_id' => 'TERMINAL-001',
        ]);
        
        $this->assertNotNull($intent);
        $this->assertEquals('reserved', $intent->status);
        $this->assertEquals(200, $intent->points_requested);
        $this->assertNotNull($intent->expires_at);
        
        // Check reservation in ledger
        $reserved = $this->ledgerService->getReservedBalance($user->id);
        $this->assertEquals(200, $reserved);
    }

    public function test_confirm_redemption_debits_points_and_releases_reservation(): void
    {
        $user = User::factory()->create(['pontos' => 0]);
        $company = Empresa::factory()->create();
        
        $this->ledgerService->credit($user->id, 300, 'Initial');
        $intent = $this->redemptionService->requestRedemption($user->id, $company->id, 150);
        
        $confirmed = $this->redemptionService->confirmRedemption($intent->intent_id, 150);
        
        $this->assertEquals('confirmed', $confirmed->status);
        $this->assertEquals(150, $confirmed->points_confirmed);
        $this->assertNotNull($confirmed->confirmed_at);
        
        // Check final balance
        $balance = $this->ledgerService->getBalance($user->id);
        $this->assertEquals(150, $balance); // 300 - 150
        
        // Check no reserved balance
        $reserved = $this->ledgerService->getReservedBalance($user->id);
        $this->assertEquals(0, $reserved);
    }

    public function test_confirm_with_different_amount_adjusts_points(): void
    {
        $user = User::factory()->create(['pontos' => 0]);
        $company = Empresa::factory()->create();
        
        $this->ledgerService->credit($user->id, 500, 'Initial');
        $intent = $this->redemptionService->requestRedemption($user->id, $company->id, 200);
        
        // Confirm with only 100 points (partial redemption)
        $confirmed = $this->redemptionService->confirmRedemption($intent->intent_id, 100);
        
        $this->assertEquals(100, $confirmed->points_confirmed);
        
        $balance = $this->ledgerService->getBalance($user->id);
        $this->assertEquals(400, $balance); // 500 - 100
    }

    public function test_cancel_redemption_releases_reserved_points(): void
    {
        $user = User::factory()->create(['pontos' => 0]);
        $company = Empresa::factory()->create();
        
        $this->ledgerService->credit($user->id, 250, 'Initial');
        $intent = $this->redemptionService->requestRedemption($user->id, $company->id, 100);
        
        $canceled = $this->redemptionService->cancelRedemption($intent->intent_id, 'Customer changed mind');
        
        $this->assertEquals('canceled', $canceled->status);
        $this->assertNotNull($canceled->canceled_at);
        
        // Points returned
        $balance = $this->ledgerService->getBalance($user->id);
        $this->assertEquals(250, $balance);
        
        $reserved = $this->ledgerService->getReservedBalance($user->id);
        $this->assertEquals(0, $reserved);
    }

    public function test_reverse_redemption_credits_points_back(): void
    {
        $user = User::factory()->create(['pontos' => 0]);
        $company = Empresa::factory()->create();
        
        $this->ledgerService->credit($user->id, 400, 'Initial');
        $intent = $this->redemptionService->requestRedemption($user->id, $company->id, 150);
        $this->redemptionService->confirmRedemption($intent->intent_id, 150);
        
        // Reverse (estorno)
        $reversed = $this->redemptionService->reverseRedemption($intent->intent_id, 'Wrong transaction', 1);
        
        $this->assertEquals('reversed', $reversed->status);
        $this->assertNotNull($reversed->reversed_at);
        
        // Points credited back
        $balance = $this->ledgerService->getBalance($user->id);
        $this->assertEquals(400, $balance); // Back to original
    }

    public function test_process_expired_reservations_cancels_old_intents(): void
    {
        $user = User::factory()->create(['pontos' => 0]);
        $company = Empresa::factory()->create();
        
        $this->ledgerService->credit($user->id, 500, 'Initial');
        
        // Create expired intent (manually set)
        $intent = $this->redemptionService->requestRedemption($user->id, $company->id, 200);
        \DB::table('redemption_intents')
            ->where('intent_id', $intent->intent_id)
            ->update(['expires_at' => now()->subMinutes(1)]);
        
        $processed = $this->redemptionService->processExpiredReservations();
        
        $this->assertEquals(1, $processed['processed']);
        
        // Check intent is expired
        $intent->refresh();
        $this->assertEquals('expired', $intent->status);
        
        // Points released
        $reserved = $this->ledgerService->getReservedBalance($user->id);
        $this->assertEquals(0, $reserved);
    }

    public function test_get_user_redemptions_returns_history(): void
    {
        $user = User::factory()->create(['pontos' => 0]);
        $company = Empresa::factory()->create();
        
        $this->ledgerService->credit($user->id, 1000, 'Initial');
        
        $this->redemptionService->requestRedemption($user->id, $company->id, 100);
        $this->redemptionService->requestRedemption($user->id, $company->id, 150);
        
        $redemptions = $this->redemptionService->getUserRedemptions($user->id, 10);
        
        $this->assertCount(2, $redemptions);
    }

    public function test_get_company_pending_redemptions_shows_pdv_queue(): void
    {
        $user1 = User::factory()->create(['pontos' => 0]);
        $user2 = User::factory()->create(['pontos' => 0]);
        $company = Empresa::factory()->create();
        
        $this->ledgerService->credit($user1->id, 500, 'Initial');
        $this->ledgerService->credit($user2->id, 500, 'Initial');
        
        $this->redemptionService->requestRedemption($user1->id, $company->id, 100);
        $intent2 = $this->redemptionService->requestRedemption($user2->id, $company->id, 200);
        $this->redemptionService->confirmRedemption($intent2->intent_id, 200); // Confirm one
        
        $pending = $this->redemptionService->getCompanyPendingRedemptions($company->id);
        
        // Only 1 pending (the other is confirmed)
        $this->assertCount(1, $pending);
        $this->assertEquals('reserved', $pending[0]->status);
    }

    public function test_insufficient_balance_blocks_redemption(): void
    {
        $user = User::factory()->create(['pontos' => 0]);
        $company = Empresa::factory()->create();
        
        $this->ledgerService->credit($user->id, 50, 'Small credit');
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Saldo insuficiente');
        
        $this->redemptionService->requestRedemption($user->id, $company->id, 100);
    }

    public function test_redemption_metadata_stored(): void
    {
        $user = User::factory()->create(['pontos' => 0]);
        $operator = User::factory()->create(['perfil' => 'empresa']);
        $company = Empresa::factory()->create();
        
        $this->ledgerService->credit($user->id, 300, 'Initial');
        
        $intent = $this->redemptionService->requestRedemption($user->id, $company->id, 150, [
            'pdv_operator_id' => $operator->id,
            'pdv_terminal_id' => 'TERMINAL-ABC',
            'notes' => 'Special customer',
        ]);
        
        $metadata = $intent->metadata;
        
        $this->assertEquals($operator->id, $metadata['pdv_operator_id']);
        $this->assertEquals('TERMINAL-ABC', $metadata['pdv_terminal_id']);
        $this->assertEquals('Special customer', $metadata['notes']);
    }
}
