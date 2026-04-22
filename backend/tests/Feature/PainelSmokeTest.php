<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\QRCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PainelSmokeTest extends TestCase
{
    use RefreshDatabase;

    private function bearerHeaders(User $user): array
    {
        $token = $user->createToken('painel-smoke')->plainTextToken;

        return [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ];
    }

    private function createEmpresaParaUsuario(User $owner): Empresa
    {
        return Empresa::query()->create([
            'nome' => 'Empresa Smoke',
            'endereco' => 'Rua Teste 100',
            'telefone' => '11999999999',
            'cnpj' => '99.999.999/0001-99',
            'owner_id' => $owner->id,
            'ativo' => true,
            'points_multiplier' => 1.0,
        ]);
    }

    public function test_endpoints_criticos_do_painel_cliente_respondem_sem_erro(): void
    {
        $cliente = User::factory()->create([
            'perfil' => 'cliente',
            'status' => 'ativo',
        ]);

        $headers = $this->bearerHeaders($cliente);

        $endpoints = [
            '/api/cliente/dashboard',
            '/api/cliente/promocoes',
            '/api/cliente/ranking-pontos',
            '/api/pontos/meus-dados',
            '/api/referral/meu-codigo',
        ];

        foreach ($endpoints as $endpoint) {
            $this->withHeaders($headers)
                ->getJson($endpoint)
                ->assertStatus(200, "Falha no endpoint do cliente: {$endpoint}");
        }
    }

    public function test_endpoints_criticos_do_painel_empresa_respondem_sem_erro(): void
    {
        $empresaUser = User::factory()->create([
            'perfil' => 'empresa',
            'status' => 'ativo',
        ]);

        $empresa = $this->createEmpresaParaUsuario($empresaUser);
        QRCode::query()->create([
            'empresa_id' => $empresa->id,
            'name' => 'QR Principal',
            'code' => 'QR-SMOKE-' . $empresa->id,
            'active' => true,
        ]);

        $headers = $this->bearerHeaders($empresaUser);

        $endpoints = [
            '/api/empresa/dashboard',
            '/api/empresa/perfil',
            '/api/empresa/clientes',
            '/api/empresa/qrcodes',
            '/api/empresa/relatorio-pontos',
            '/api/empresa/recent-checkins',
        ];

        foreach ($endpoints as $endpoint) {
            $this->withHeaders($headers)
                ->getJson($endpoint)
                ->assertStatus(200, "Falha no endpoint da empresa: {$endpoint}");
        }
    }

    public function test_endpoints_criticos_do_painel_admin_respondem_sem_erro(): void
    {
        $admin = User::factory()->create([
            'perfil' => 'admin',
            'status' => 'ativo',
        ]);

        $headers = $this->bearerHeaders($admin);

        $endpoints = [
            '/api/admin/dashboard-stats',
            '/api/admin/pontos/estatisticas',
            '/api/admin/pontos/checkins-pendentes',
            '/api/admin/users',
            '/api/admin/settings',
            '/api/admin/content',
            '/api/admin/tickets',
            '/api/admin/tickets/stats',
        ];

        foreach ($endpoints as $endpoint) {
            $this->withHeaders($headers)
                ->getJson($endpoint)
                ->assertStatus(200, "Falha no endpoint do admin: {$endpoint}");
        }
    }
}
