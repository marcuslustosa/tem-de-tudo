<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCompanyRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_company_registration_creates_pending_company_hidden_from_public(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'perfil' => 'empresa',
            'name' => 'Maria Responsavel',
            'responsavel' => 'Maria Responsavel',
            'nome_fantasia' => 'Acai da Maria',
            'email' => 'maria.empresa@example.com',
            'telefone' => '(11) 98888-0000',
            'whatsapp' => '(11) 97777-0000',
            'categoria' => 'restaurantes',
            'cnpj' => '12.345.678/0001-90',
            'endereco' => 'Rua das Flores, 100',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'terms' => true,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('token', null)
            ->assertJsonPath('data.redirect_to', '/entrar.html');

        $user = User::query()->where('email', 'maria.empresa@example.com')->firstOrFail();
        $empresa = Empresa::query()->where('owner_id', $user->id)->firstOrFail();

        $this->assertSame('pendente', $user->status);
        $this->assertSame(Empresa::STATUS_PENDING, $empresa->operationalStatus());
        $this->assertFalse((bool) $empresa->ativo);
        $this->assertSame('Acai da Maria', $empresa->nome);
        $this->assertSame('restaurantes', $empresa->ramo);

        $this->assertDatabaseMissing('qr_codes', [
            'empresa_id' => $empresa->id,
        ]);

        $publicList = $this->getJson('/api/empresas');
        $publicList->assertOk();
        $serialized = json_encode($publicList->json('data') ?? []);
        $this->assertStringNotContainsString('Acai da Maria', (string) $serialized);
    }

    public function test_pending_company_cannot_operate_company_routes_even_with_valid_token(): void
    {
        $empresaUser = User::factory()->create([
            'perfil' => 'empresa',
            'status' => 'pendente',
        ]);

        Empresa::factory()->create([
            'owner_id' => $empresaUser->id,
            'ativo' => false,
            'status' => Empresa::STATUS_PENDING,
        ]);

        $token = $empresaUser->createToken('pending-company')->plainTextToken;

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson('/api/empresa/dashboard')
            ->assertStatus(403)
            ->assertJsonPath('error', 'company_status_blocked')
            ->assertJsonPath('company_status', Empresa::STATUS_PENDING);
    }
}
