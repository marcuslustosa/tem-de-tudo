<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminCompanyApprovalFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_pending_companies_and_approve_them(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'perfil' => 'admin',
            'status' => 'ativo',
        ]);

        $companyUser = User::factory()->create([
            'perfil' => 'empresa',
            'status' => 'pendente',
        ]);

        $empresa = Empresa::factory()->create([
            'owner_id' => $companyUser->id,
            'nome' => 'Padaria Central',
            'ativo' => false,
            'status' => Empresa::STATUS_PENDING,
        ]);

        $token = $admin->createToken('admin-company-approval')->plainTextToken;

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson('/api/admin/empresas?status=pending')
            ->assertOk()
            ->assertJsonPath('success', true);

        $approve = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->postJson("/api/admin/empresas/{$empresa->id}/approve");

        $approve
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', Empresa::STATUS_ACTIVE)
            ->assertJsonPath('data.publicamente_visivel', true)
            ->assertJsonPath('data.qr_code_ready', true);

        $empresa->refresh();
        $companyUser->refresh();

        $this->assertSame(Empresa::STATUS_ACTIVE, $empresa->operationalStatus());
        $this->assertTrue((bool) $empresa->ativo);
        $this->assertSame('ativo', $companyUser->status);

        $this->assertDatabaseHas('qr_codes', [
            'empresa_id' => $empresa->id,
        ]);
    }

    public function test_admin_can_reject_or_suspend_and_keep_company_hidden(): void
    {
        $admin = User::factory()->create([
            'perfil' => 'admin',
            'status' => 'ativo',
        ]);

        $companyUser = User::factory()->create([
            'perfil' => 'empresa',
            'status' => 'pendente',
        ]);

        $empresa = Empresa::factory()->create([
            'owner_id' => $companyUser->id,
            'ativo' => false,
            'status' => Empresa::STATUS_PENDING,
        ]);

        $token = $admin->createToken('admin-company-status')->plainTextToken;

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->postJson("/api/admin/empresas/{$empresa->id}/reject")
            ->assertOk()
            ->assertJsonPath('data.status', Empresa::STATUS_REJECTED);

        $empresa->refresh();
        $companyUser->refresh();
        $this->assertSame(Empresa::STATUS_REJECTED, $empresa->operationalStatus());
        $this->assertFalse((bool) $empresa->ativo);
        $this->assertSame('inativo', $companyUser->status);

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->postJson("/api/admin/empresas/{$empresa->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', Empresa::STATUS_ACTIVE);

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->postJson("/api/admin/empresas/{$empresa->id}/suspend")
            ->assertOk()
            ->assertJsonPath('data.status', Empresa::STATUS_SUSPENDED)
            ->assertJsonPath('data.publicamente_visivel', false);

        $this->getJson("/api/empresas/{$empresa->id}")
            ->assertStatus(404);
    }
}
