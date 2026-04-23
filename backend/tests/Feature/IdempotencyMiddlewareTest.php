<?php

namespace Tests\Feature;

use App\Models\Ledger;
use App\Models\User;
use App\Services\LedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class IdempotencyMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_wallet_redeem_replays_same_response_with_same_idempotency_key(): void
    {
        $user = User::factory()->create([
            'perfil' => 'cliente',
            'status' => 'ativo',
            'pontos' => 0,
        ]);

        app(LedgerService::class)->credit($user->id, 200, 'Credito inicial');

        Sanctum::actingAs($user);

        $payload = [
            'pontos' => 50,
            'descricao' => 'Resgate idempotente',
        ];

        $first = $this
            ->withHeader('X-Idempotency-Key', 'wallet-redeem-key-001')
            ->postJson('/api/fidelidade/resgatar', $payload);

        $first
            ->assertOk()
            ->assertHeader('X-Idempotency-Status', 'stored')
            ->assertJsonPath('success', true);

        $firstLedgerId = $first->json('data.ledger_id');

        $second = $this
            ->withHeader('X-Idempotency-Key', 'wallet-redeem-key-001')
            ->postJson('/api/fidelidade/resgatar', $payload);

        $second
            ->assertOk()
            ->assertHeader('X-Idempotency-Status', 'replay')
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.ledger_id', $firstLedgerId);

        $redeemCount = Ledger::query()
            ->where('user_id', $user->id)
            ->where('transaction_type', 'redeem')
            ->count();

        $this->assertSame(1, $redeemCount);
    }

    public function test_wallet_redeem_without_key_still_works_in_bypass_mode(): void
    {
        $user = User::factory()->create([
            'perfil' => 'cliente',
            'status' => 'ativo',
            'pontos' => 0,
        ]);

        app(LedgerService::class)->credit($user->id, 200, 'Credito inicial');

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/fidelidade/resgatar', [
            'pontos' => 50,
            'descricao' => 'Resgate sem chave',
        ]);

        $response
            ->assertOk()
            ->assertHeader('X-Idempotency-Status', 'bypass')
            ->assertJsonPath('success', true);
    }

    public function test_wallet_redeem_requires_key_when_environment_is_production(): void
    {
        config([
            'app.env' => 'production',
            'idempotency.enforce_header' => true,
            'idempotency.require_in_production' => true,
        ]);

        $user = User::factory()->create([
            'perfil' => 'cliente',
            'status' => 'ativo',
            'pontos' => 0,
        ]);

        app(LedgerService::class)->credit($user->id, 200, 'Credito inicial');
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/fidelidade/resgatar', [
            'pontos' => 50,
            'descricao' => 'Sem idempotency key em producao',
        ]);

        $response
            ->assertStatus(400)
            ->assertHeader('X-Idempotency-Status', 'missing')
            ->assertJsonPath('success', false);
    }
}
