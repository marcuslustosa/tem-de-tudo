<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RedemptionAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cliente_cannot_request_redemption_for_another_user(): void
    {
        $cliente = User::factory()->create([
            'perfil' => 'cliente',
            'status' => 'ativo',
        ]);

        $otherUser = User::factory()->create([
            'perfil' => 'cliente',
            'status' => 'ativo',
        ]);

        $empresa = Empresa::factory()->create();

        Sanctum::actingAs($cliente);

        $response = $this
            ->withHeader('X-Idempotency-Key', 'client-authz-test-001')
            ->postJson('/api/redemption/request', [
                'user_id' => $otherUser->id,
                'company_id' => $empresa->id,
                'points' => 20,
                'type' => 'product',
            ]);

        $response
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_empresa_cannot_read_pending_redemptions_of_another_company(): void
    {
        $empresaUserA = User::factory()->create([
            'perfil' => 'empresa',
            'status' => 'ativo',
        ]);

        $empresaUserB = User::factory()->create([
            'perfil' => 'empresa',
            'status' => 'ativo',
        ]);

        $empresaA = Empresa::factory()->create([
            'owner_id' => $empresaUserA->id,
        ]);

        $empresaB = Empresa::factory()->create([
            'owner_id' => $empresaUserB->id,
        ]);

        Sanctum::actingAs($empresaUserA);

        $response = $this->getJson("/api/redemption/company/{$empresaB->id}/pending");

        $response
            ->assertStatus(403)
            ->assertJsonPath('success', false);

        $this->assertNotSame($empresaA->id, $empresaB->id);
    }
}

