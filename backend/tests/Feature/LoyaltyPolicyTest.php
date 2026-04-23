<?php

namespace Tests\Feature;

use App\Models\CompanyLoyaltyConfig;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LoyaltyPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_empresa_can_update_and_read_own_loyalty_policy(): void
    {
        $empresaUser = User::factory()->create([
            'perfil' => 'empresa',
            'status' => 'ativo',
        ]);

        $empresa = Empresa::factory()->create([
            'owner_id' => $empresaUser->id,
        ]);

        Sanctum::actingAs($empresaUser);

        $update = $this->putJson('/api/empresa/fidelidade/config', [
            'points_per_real' => 2.5,
            'scan_base_points' => 180,
            'redeem_points_per_currency' => 12,
            'min_redeem_points' => 80,
            'welcome_bonus_points' => 20,
            'is_active' => true,
            'metadata' => [
                'source' => 'test-suite',
            ],
        ]);

        $update
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.policy.source', 'company_override_active')
            ->assertJsonPath('data.policy.points_per_real', 2.5)
            ->assertJsonPath('data.policy.scan_base_points', 180)
            ->assertJsonPath('data.policy.min_redeem_points', 80);

        $this->assertDatabaseHas('company_loyalty_configs', [
            'company_id' => $empresa->id,
            'scan_base_points' => 180,
            'min_redeem_points' => 80,
            'welcome_bonus_points' => 20,
            'is_active' => true,
        ]);

        $this->getJson('/api/empresa/fidelidade/config')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.company.id', $empresa->id)
            ->assertJsonPath('data.policy.redeem_points_per_currency', 12)
            ->assertJsonPath('data.policy.metadata.source', 'test-suite');
    }

    public function test_programa_endpoint_reflects_company_policy_override(): void
    {
        $empresaUser = User::factory()->create([
            'perfil' => 'empresa',
            'status' => 'ativo',
        ]);

        $empresa = Empresa::factory()->create([
            'owner_id' => $empresaUser->id,
        ]);

        CompanyLoyaltyConfig::query()->create([
            'company_id' => $empresa->id,
            'points_per_real' => 3.0,
            'scan_base_points' => 250,
            'redeem_points_per_currency' => 15,
            'min_redeem_points' => 120,
            'welcome_bonus_points' => 30,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/fidelidade/programa?empresa_id=' . $empresa->id);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.config_origem', 'company_override_active')
            ->assertJsonPath('data.acumulo.pontos_por_real', 3)
            ->assertJsonPath('data.acumulo.pontos_base_scan', 250)
            ->assertJsonPath('data.resgate.pontos_por_moeda', 15)
            ->assertJsonPath('data.resgate.minimo_pontos_resgate', 120)
            ->assertJsonPath('data.onboarding_empresa.status.company_id', $empresa->id);
    }

    public function test_empresa_onboarding_endpoint_returns_operational_checklist(): void
    {
        $empresaUser = User::factory()->create([
            'perfil' => 'empresa',
            'status' => 'ativo',
        ]);

        $empresa = Empresa::factory()->create([
            'owner_id' => $empresaUser->id,
        ]);

        Sanctum::actingAs($empresaUser);

        $response = $this->getJson('/api/empresa/fidelidade/onboarding');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.company_id', $empresa->id)
            ->assertJsonStructure([
                'data' => [
                    'is_ready',
                    'progress_percent',
                    'checks_total',
                    'checks_completed',
                    'checks' => [
                        '*' => ['key', 'label', 'ok', 'required'],
                    ],
                    'next_actions',
                    'effective_policy',
                ],
            ]);
    }

    public function test_admin_can_manage_company_policy_and_view_onboarding(): void
    {
        $admin = User::factory()->create([
            'perfil' => 'admin',
            'status' => 'ativo',
        ]);

        $empresaOwner = User::factory()->create([
            'perfil' => 'empresa',
            'status' => 'ativo',
        ]);

        $empresa = Empresa::factory()->create([
            'owner_id' => $empresaOwner->id,
        ]);

        Sanctum::actingAs($admin);

        $this->putJson("/api/admin/empresas/{$empresa->id}/fidelidade/config", [
            'points_per_real' => 1.8,
            'scan_base_points' => 140,
            'min_redeem_points' => 60,
            'is_active' => true,
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.policy.points_per_real', 1.8)
            ->assertJsonPath('data.policy.scan_base_points', 140)
            ->assertJsonPath('data.policy.min_redeem_points', 60);

        $this->getJson("/api/admin/empresas/{$empresa->id}/fidelidade/config")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.company.id', $empresa->id);

        $this->getJson("/api/admin/empresas/{$empresa->id}/fidelidade/onboarding")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.company_id', $empresa->id);
    }
}
