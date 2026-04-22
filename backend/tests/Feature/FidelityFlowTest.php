<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\User;
use App\Services\ClienteQrCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FidelityFlowTest extends TestCase
{
    use RefreshDatabase;

    private function criarPromocao(array $overrides = []): int
    {
        $payload = [
            'empresa_id' => $overrides['empresa_id'] ?? null,
            'titulo' => $overrides['titulo'] ?? 'Promo Teste',
            'descricao' => $overrides['descricao'] ?? 'Descricao promocao',
            'ativo' => $overrides['ativo'] ?? true,
            'status' => $overrides['status'] ?? 'ativa',
            'desconto' => $overrides['desconto'] ?? 10,
            'pontos_necessarios' => $overrides['pontos_necessarios'] ?? 120,
            'resgates' => $overrides['resgates'] ?? 0,
            'qtd_resgatada' => $overrides['qtd_resgatada'] ?? 0,
            'limite_por_usuario' => $overrides['limite_por_usuario'] ?? 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $insert = [];
        foreach ($payload as $column => $value) {
            if (Schema::hasColumn('promocoes', $column)) {
                $insert[$column] = $value;
            }
        }

        return (int) DB::table('promocoes')->insertGetId($insert);
    }

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

    public function test_cliente_resgate_usa_pontos_necessarios_quando_disponivel(): void
    {
        $cliente = User::factory()->create([
            'perfil' => 'cliente',
            'status' => 'ativo',
            'pontos' => 300,
        ]);

        $empresaUser = User::factory()->create([
            'perfil' => 'empresa',
            'status' => 'ativo',
        ]);

        $empresa = Empresa::query()->create([
            'nome' => 'Empresa Resgate',
            'endereco' => 'Rua Resgate 123',
            'telefone' => '11999999999',
            'cnpj' => '77.777.777/0001-77',
            'owner_id' => $empresaUser->id,
            'ativo' => true,
            'points_multiplier' => 1.0,
        ]);

        $promocaoId = $this->criarPromocao([
            'empresa_id' => $empresa->id,
            'titulo' => 'Combo Especial',
            'pontos_necessarios' => 120,
            'desconto' => 5,
            'limite_por_usuario' => 2,
        ]);

        $response = $this->withHeaders($this->bearerHeaders($cliente))
            ->postJson("/api/cliente/promocoes/{$promocaoId}/resgatar");

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.pontos_gastos', 120);

        $this->assertDatabaseHas('pontos', [
            'user_id' => $cliente->id,
            'empresa_id' => $empresa->id,
            'tipo' => 'resgate',
            'pontos' => 120,
        ]);
    }

    public function test_programa_fidelidade_endpoint_retorna_regras_ativas(): void
    {
        $response = $this->getJson('/api/fidelidade/programa');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'modelo',
                    'acumulo' => ['pontos_por_real', 'pontos_base_scan'],
                    'resgate' => ['regra_custo'],
                    'onboarding_empresa' => ['fluxo'],
                ],
            ]);
    }
}
