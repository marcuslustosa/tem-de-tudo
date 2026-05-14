<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyOperationalStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_company_routes_only_expose_active_companies(): void
    {
        $active = Empresa::factory()->create([
            'nome' => 'Empresa Ativa',
            'ativo' => true,
            'status' => Empresa::STATUS_ACTIVE,
        ]);

        $pending = Empresa::factory()->create([
            'nome' => 'Empresa Pendente',
            'ativo' => false,
            'status' => Empresa::STATUS_PENDING,
        ]);

        $suspended = Empresa::factory()->create([
            'nome' => 'Empresa Suspensa',
            'ativo' => false,
            'status' => Empresa::STATUS_SUSPENDED,
        ]);

        $rejected = Empresa::factory()->create([
            'nome' => 'Empresa Rejeitada',
            'ativo' => false,
            'status' => Empresa::STATUS_REJECTED,
        ]);

        $list = $this->getJson('/api/empresas');
        $list->assertOk();

        $serialized = json_encode($list->json('data') ?? []);

        $this->assertStringContainsString($active->nome, (string) $serialized);
        $this->assertStringNotContainsString($pending->nome, (string) $serialized);
        $this->assertStringNotContainsString($suspended->nome, (string) $serialized);
        $this->assertStringNotContainsString($rejected->nome, (string) $serialized);

        $this->getJson("/api/empresas/{$active->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->getJson("/api/empresas/{$pending->id}")
            ->assertStatus(404);
    }

    public function test_authenticated_customer_list_only_receives_active_companies(): void
    {
        $cliente = User::factory()->create([
            'perfil' => 'cliente',
            'status' => 'ativo',
        ]);

        $active = Empresa::factory()->create([
            'nome' => 'Empresa Cliente Ativa',
            'ativo' => true,
            'status' => Empresa::STATUS_ACTIVE,
        ]);

        $pending = Empresa::factory()->create([
            'nome' => 'Empresa Cliente Pendente',
            'ativo' => false,
            'status' => Empresa::STATUS_PENDING,
        ]);

        $token = $cliente->createToken('customer-company-list')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson('/api/cliente/empresas');

        $response->assertOk();

        $serialized = json_encode($response->json('data') ?? []);

        $this->assertStringContainsString($active->nome, (string) $serialized);
        $this->assertStringNotContainsString($pending->nome, (string) $serialized);
    }
}
