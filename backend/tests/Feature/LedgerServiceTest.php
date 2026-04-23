<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\LedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LedgerServiceTest extends TestCase
{
    use RefreshDatabase;

    private LedgerService $ledgerService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ledgerService = app(LedgerService::class);
    }

    public function test_credit_adds_points_and_creates_ledger_entry(): void
    {
        $user = User::factory()->create(['pontos' => 0]);
        
        $ledger = $this->ledgerService->credit($user->id, 100, 'Test credit');
        
        $this->assertGreaterThan(0, $ledger->id);
        $this->assertDatabaseHas('ledger', [
            'id' => $ledger->id,
            'user_id' => $user->id,
            'transaction_type' => 'earn',
            'points' => 100,
            'balance_before' => 0,
            'balance_after' => 100,
        ]);
        
        $balance = $this->ledgerService->getBalance($user->id);
        $this->assertEquals(100, $balance);
    }

    public function test_debit_removes_points_with_balance_validation(): void
    {
        $user = User::factory()->create(['pontos' => 0]);
        $this->ledgerService->credit($user->id, 200, 'Initial credit');
        
        $ledger = $this->ledgerService->debit($user->id, 50, 'Test debit');
        
        $this->assertGreaterThan(0, $ledger->id);
        $this->assertDatabaseHas('ledger', [
            'id' => $ledger->id,
            'user_id' => $user->id,
            'transaction_type' => 'redeem',
            'points' => -50,
            'balance_before' => 200,
            'balance_after' => 150,
        ]);
        
        $balance = $this->ledgerService->getBalance($user->id);
        $this->assertEquals(150, $balance);
    }

    public function test_debit_fails_when_insufficient_balance(): void
    {
        $user = User::factory()->create(['pontos' => 0]);
        $this->ledgerService->credit($user->id, 50, 'Small credit');
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient balance');
        
        $this->ledgerService->debit($user->id, 100, 'Overdraft attempt');
    }

    public function test_reserve_points_for_pdv_redemption(): void
    {
        $user = User::factory()->create(['pontos' => 0]);
        $this->ledgerService->credit($user->id, 300, 'Initial');
        
        $ledger = $this->ledgerService->reserve($user->id, 150, 'PDV reservation');
        
        $this->assertDatabaseHas('ledger', [
            'id' => $ledger->id,
            'transaction_type' => 'reserved',
            'points' => -150,
        ]);
        
        $reserved = $this->ledgerService->getReservedBalance($user->id);
        $this->assertEquals(150, $reserved);
    }

    public function test_release_cancels_reservation(): void
    {
        $user = User::factory()->create(['pontos' => 0]);
        $this->ledgerService->credit($user->id, 200, 'Initial');
        $reservation = $this->ledgerService->reserve($user->id, 100, 'Reservation');
        
        $release = $this->ledgerService->release($reservation->id, 'Cancelled by user');
        
        $this->assertDatabaseHas('ledger', [
            'id' => $release->id,
            'transaction_type' => 'released',
            'points' => 100,
            'related_ledger_id' => $reservation->id,
        ]);
        
        $reserved = $this->ledgerService->getReservedBalance($user->id);
        $this->assertEquals(0, $reserved);
    }

    public function test_reverse_credits_points_back(): void
    {
        $user = User::factory()->create(['pontos' => 0]);
        $this->ledgerService->credit($user->id, 300, 'Initial');
        $debit = $this->ledgerService->debit($user->id, 100, 'Purchase');
        
        $reversal = $this->ledgerService->reverse($debit->id, 'Wrong transaction', 1);
        
        $this->assertDatabaseHas('ledger', [
            'id' => $reversal->id,
            'transaction_type' => 'reversal',
            'points' => 100,
            'related_ledger_id' => $debit->id,
        ]);
        
        $balance = $this->ledgerService->getBalance($user->id);
        $this->assertEquals(300, $balance);
    }

    public function test_idempotency_prevents_duplicate_transactions(): void
    {
        $user = User::factory()->create(['pontos' => 0]);
        $idempotencyKey = \Illuminate\Support\Str::uuid()->toString();
        
        $ledger1 = $this->ledgerService->credit($user->id, 100, 'Test', [
            'idempotency_key' => $idempotencyKey
        ]);
        
        $ledger2 = $this->ledgerService->credit($user->id, 100, 'Test duplicate', [
            'idempotency_key' => $idempotencyKey
        ]);
        
        $this->assertEquals($ledger1->id, $ledger2->id);
        
        $balance = $this->ledgerService->getBalance($user->id);
        $this->assertEquals(100, $balance); // Only credited once
    }

    public function test_audit_validates_ledger_integrity(): void
    {
        $user = User::factory()->create(['pontos' => 0]);
        
        $this->ledgerService->credit($user->id, 100, 'First');
        $this->ledgerService->credit($user->id, 50, 'Second');
        $this->ledgerService->debit($user->id, 30, 'Debit');
        
        $audit = $this->ledgerService->audit($user->id);
        
        $this->assertTrue($audit['valid']);
        $this->assertEquals(120, $audit['calculated_balance']);
        $this->assertEquals(0, $audit['discrepancy']);
    }

    public function test_multiple_users_isolation(): void
    {
        $user1 = User::factory()->create(['pontos' => 0]);
        $user2 = User::factory()->create(['pontos' => 0]);
        
        $this->ledgerService->credit($user1->id, 100, 'User 1 credit');
        $this->ledgerService->credit($user2->id, 200, 'User 2 credit');
        
        $balance1 = $this->ledgerService->getBalance($user1->id);
        $balance2 = $this->ledgerService->getBalance($user2->id);
        
        $this->assertEquals(100, $balance1);
        $this->assertEquals(200, $balance2);
    }
}
