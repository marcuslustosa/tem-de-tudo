<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\InscricaoEmpresa;
use App\Models\PushSubscription;
use App\Models\User;
use App\Services\WebPushDeliveryService;
use Database\Seeders\I9PlusDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPushFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seed_creates_push_iphone_client_linked_to_malagueta_and_test_promotion(): void
    {
        $this->seed(I9PlusDemoSeeder::class);

        $client = User::query()->where('email', 'cliente.push@demo.local')->first();
        $this->assertNotNull($client);
        $this->assertSame('cliente', $client->perfil);
        $this->assertSame('ativo', $client->status);

        $owner = User::query()->where('email', 'malagueta@demo.local')->first();
        $empresa = Empresa::query()->where('owner_id', $owner?->id)->first();

        $this->assertNotNull($empresa);
        $this->assertDatabaseHas('inscricoes_empresa', [
            'user_id' => $client->id,
            'empresa_id' => $empresa->id,
        ]);

        $this->assertDatabaseHas('promocoes', [
            'empresa_id' => $empresa->id,
            'titulo' => 'Teste de Push',
            'ativo' => true,
        ]);
    }

    public function test_admin_can_lookup_client_push_status(): void
    {
        $admin = User::factory()->create([
            'perfil' => 'admin',
            'status' => 'ativo',
        ]);
        $client = User::factory()->create([
            'perfil' => 'cliente',
            'status' => 'ativo',
            'email' => 'cliente.push@demo.local',
            'name' => 'Cliente Push iPhone',
        ]);

        PushSubscription::query()->create([
            'user_id' => $client->id,
            'endpoint' => 'https://push.example.com/sub/admin-status-1',
            'public_key' => 'public-key-admin-status-1',
            'auth_token' => 'auth-token-admin-status-1',
            'p256dh' => 'public-key-admin-status-1',
            'auth' => 'auth-token-admin-status-1',
            'content_encoding' => 'aes128gcm',
            'device_type' => 'ios',
        ]);

        $response = $this->withHeaders($this->authHeaders($admin->createToken('admin-push-status')->plainTextToken))
            ->getJson('/api/admin/push/client-status?email=cliente.push@demo.local');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'cliente.push@demo.local')
            ->assertJsonPath('data.push.has_active_subscription', true)
            ->assertJsonPath('data.push.total_subscriptions', 1);
    }

    public function test_admin_can_send_test_push_to_client_with_active_subscription(): void
    {
        $admin = User::factory()->create([
            'perfil' => 'admin',
            'status' => 'ativo',
        ]);
        $client = User::factory()->create([
            'perfil' => 'cliente',
            'status' => 'ativo',
            'email' => 'cliente.push@demo.local',
        ]);
        $this->linkClientToCompany($client);

        PushSubscription::query()->create([
            'user_id' => $client->id,
            'endpoint' => 'https://push.example.com/sub/admin-send-1',
            'public_key' => 'public-key-admin-send-1',
            'auth_token' => 'auth-token-admin-send-1',
            'p256dh' => 'public-key-admin-send-1',
            'auth' => 'auth-token-admin-send-1',
            'content_encoding' => 'aes128gcm',
            'device_type' => 'ios',
        ]);

        $this->mock(WebPushDeliveryService::class, function ($mock): void {
            $mock->shouldReceive('deliverToSubscriptions')
                ->once()
                ->andReturn([
                    'status' => 'sent',
                    'sent' => true,
                    'error' => null,
                    'subscriptions_sent' => 1,
                    'subscriptions_failed' => 0,
                    'subscriptions_skipped' => 0,
                    'config_missing' => false,
                ]);
        });

        $response = $this->withHeaders($this->authHeaders($admin->createToken('admin-push-send')->plainTextToken))
            ->postJson('/api/admin/push/test-client', [
                'user_id' => $client->id,
                'title' => 'Teste de notificacao',
                'body' => 'Se voce recebeu esta mensagem, o push esta funcionando.',
                'url' => '/meus_pontos.html',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.delivery.total_elegiveis', 1)
            ->assertJsonPath('meta.delivery.total_com_subscription', 1)
            ->assertJsonPath('meta.delivery.enviados', 1);

        $this->assertDatabaseHas('notificacoes_push', [
            'user_id' => $client->id,
            'tipo' => 'admin_push_test',
            'status' => 'sent',
            'enviado' => true,
        ]);
    }

    public function test_admin_gets_friendly_error_when_client_has_no_subscription(): void
    {
        $admin = User::factory()->create([
            'perfil' => 'admin',
            'status' => 'ativo',
        ]);
        $client = User::factory()->create([
            'perfil' => 'cliente',
            'status' => 'ativo',
        ]);
        $this->linkClientToCompany($client);

        $response = $this->withHeaders($this->authHeaders($admin->createToken('admin-push-no-sub')->plainTextToken))
            ->postJson('/api/admin/push/test-client', [
                'user_id' => $client->id,
            ]);

        $response
            ->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'no_subscription')
            ->assertJsonPath('meta.delivery.total_com_subscription', 0)
            ->assertJsonPath('meta.delivery.ignorados_sem_subscription', 1);
    }

    public function test_non_admin_profiles_cannot_call_admin_push_endpoints(): void
    {
        $client = User::factory()->create([
            'perfil' => 'cliente',
            'status' => 'ativo',
        ]);
        $empresa = User::factory()->create([
            'perfil' => 'empresa',
            'status' => 'ativo',
        ]);

        $this->withHeaders($this->authHeaders($client->createToken('client-no-admin-push')->plainTextToken))
            ->getJson('/api/admin/push/client-status?email=cliente.push@demo.local')
            ->assertStatus(403);

        $this->withHeaders($this->authHeaders($empresa->createToken('company-no-admin-push')->plainTextToken))
            ->postJson('/api/admin/push/test-client', [
                'user_id' => 123,
            ])
            ->assertStatus(403);
    }

    public function test_unknown_user_returns_not_found_for_admin_push(): void
    {
        $admin = User::factory()->create([
            'perfil' => 'admin',
            'status' => 'ativo',
        ]);

        $this->withHeaders($this->authHeaders($admin->createToken('admin-push-not-found')->plainTextToken))
            ->postJson('/api/admin/push/test-client', [
                'user_id' => 999999,
            ])
            ->assertStatus(404)
            ->assertJsonPath('error', 'user_not_found');
    }

    private function authHeaders(string $token): array
    {
        return [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ];
    }

    private function linkClientToCompany(User $client): Empresa
    {
        $owner = User::factory()->create([
            'perfil' => 'empresa',
            'status' => 'ativo',
        ]);

        $empresa = Empresa::factory()->create([
            'owner_id' => $owner->id,
            'ativo' => true,
            'status' => Empresa::STATUS_ACTIVE,
        ]);

        InscricaoEmpresa::query()->create([
            'user_id' => $client->id,
            'empresa_id' => $empresa->id,
            'data_inscricao' => now()->subDays(5),
            'ultima_visita' => now()->subDay(),
            'bonus_adesao_resgatado' => false,
        ]);

        return $empresa;
    }
}
