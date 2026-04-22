<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\User;
use App\Services\ClienteQrCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FidelityFlowTest extends TestCase
{
    use RefreshDatabase;

    private function bearerHeaders(User $user): array
    {
        $token = $user->createToken('fidelity-flow')->plainTextToken;

        return [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ];
    }

    public function test_empresa_scan_cliente_registra_pontos_e_notificacoes_para_ambos(): void
    {
        $empresaUser = User::factory()->create([
            'perfil' => 'empresa',
            'status' => 'ativo',
        ]);

        $cliente = User::factory()->create([
            'perfil' => 'cliente',
            'status' => 'ativo',
            'pontos' => 0,
        ]);

        $empresa = Empresa::query()->create([
            'nome' => 'Empresa Fluxo',
            'endereco' => 'Rua Fluxo 10',
            'telefone' => '11999999999',
            'cnpj' => '88.888.888/0001-88',
            'owner_id' => $empresaUser->id,
            'ativo' => true,
            'points_multiplier' => 1.0,
        ]);

        $qrcode = app(ClienteQrCodeService::class)->gerar($cliente)['code'];

        $response = $this->withHeaders($this->bearerHeaders($empresaUser))
            ->postJson('/api/empresa/escanear-cliente', [
                'qrcode' => $qrcode,
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.cliente.id', $cliente->id);

        $this->assertDatabaseHas('pontos', [
            'user_id' => $cliente->id,
            'empresa_id' => $empresa->id,
            'tipo' => 'ganho',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $cliente->id,
            'type' => 'transacao',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $empresaUser->id,
            'type' => 'transacao_empresa',
        ]);
    }

    public function test_admin_ticket_lifecycle_endpoints_work(): void
    {
        $admin = User::factory()->create([
            'perfil' => 'admin',
            'status' => 'ativo',
        ]);

        $headers = $this->bearerHeaders($admin);

        $create = $this->withHeaders($headers)
            ->postJson('/api/admin/tickets', [
                'title' => 'Erro no fluxo de resgate',
                'message' => 'Cupom nao validou no caixa da loja.',
                'priority' => 'alta',
            ]);

        $create
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'pendente');

        $ticketId = (int) $create->json('data.id');
        $this->assertGreaterThan(0, $ticketId);

        $this->withHeaders($headers)
            ->getJson('/api/admin/tickets?status=pendente')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->withHeaders($headers)
            ->postJson("/api/admin/tickets/{$ticketId}/resolve", [
                'note' => 'Incidente tratado e validado.',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'resolvido');

        $this->withHeaders($headers)
            ->postJson("/api/admin/tickets/{$ticketId}/reopen")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'pendente');

        $this->withHeaders($headers)
            ->deleteJson("/api/admin/tickets/{$ticketId}")
            ->assertOk()
            ->assertJsonPath('success', true);
    }
}
