<?php

namespace Tests\Feature;

use App\Models\PushSubscription;
use App\Models\User;
use App\Services\WebPushDeliveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushSubscriptionFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_client_can_store_and_revoke_push_subscription(): void
    {
        $customer = User::factory()->create([
            'perfil' => 'cliente',
            'status' => 'ativo',
        ]);
        $token = $customer->createToken('push-client')->plainTextToken;

        $subscribe = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/push/subscribe', [
                'endpoint' => 'https://push.example.com/sub/customer-1',
                'keys' => [
                    'p256dh' => 'public-key-customer-1',
                    'auth' => 'auth-token-customer-1',
                ],
                'contentEncoding' => 'aes128gcm',
            ]);

        $subscribe
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.endpoint', 'https://push.example.com/sub/customer-1')
            ->assertJsonPath('data.user_id', $customer->id);

        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $customer->id,
            'endpoint' => 'https://push.example.com/sub/customer-1',
            'public_key' => 'public-key-customer-1',
            'auth_token' => 'auth-token-customer-1',
            'revoked_at' => null,
        ]);

        $unsubscribe = $this->withHeaders($this->authHeaders($token))
            ->deleteJson('/api/push/unsubscribe', [
                'endpoint' => 'https://push.example.com/sub/customer-1',
            ]);

        $unsubscribe
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.revoked', 1);

        $this->assertNotNull(PushSubscription::query()->first()?->revoked_at);
    }

    public function test_guest_cannot_store_push_subscription(): void
    {
        $this->postJson('/api/push/subscribe', [
            'endpoint' => 'https://push.example.com/sub/guest',
            'keys' => [
                'p256dh' => 'public-key-guest',
                'auth' => 'auth-token-guest',
            ],
        ])->assertUnauthorized();
    }

    public function test_public_key_endpoint_reports_missing_configuration_without_server_error(): void
    {
        $this->mock(WebPushDeliveryService::class, function ($mock): void {
            $mock->shouldReceive('publicKey')->once()->andReturn(null);
            $mock->shouldReceive('isConfigured')->once()->andReturn(false);
        });

        $this->getJson('/api/push/public-key')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('configured', false)
            ->assertJsonPath('vapidPublicKey', null);
    }

    public function test_authenticated_client_test_endpoint_returns_config_missing_without_500(): void
    {
        $customer = User::factory()->create([
            'perfil' => 'cliente',
            'status' => 'ativo',
        ]);
        $token = $customer->createToken('push-test-client')->plainTextToken;

        PushSubscription::query()->create([
            'user_id' => $customer->id,
            'endpoint' => 'https://push.example.com/sub/test-device',
            'public_key' => 'public-key-test-device',
            'auth_token' => 'auth-token-test-device',
            'p256dh' => 'public-key-test-device',
            'auth' => 'auth-token-test-device',
            'content_encoding' => 'aes128gcm',
        ]);

        $this->mock(WebPushDeliveryService::class, function ($mock): void {
            $mock->shouldReceive('deliverToSubscriptions')
                ->once()
                ->andReturn([
                    'status' => 'config_missing',
                    'sent' => false,
                    'error' => 'Configuracao de push pendente no servidor.',
                    'subscriptions_sent' => 0,
                    'subscriptions_failed' => 0,
                    'subscriptions_skipped' => 1,
                    'config_missing' => true,
                ]);
        });

        $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/push/test')
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'config_missing')
            ->assertJsonPath('meta.delivery.total_elegiveis', 1)
            ->assertJsonPath('meta.delivery.total_com_subscription', 1)
            ->assertJsonPath('meta.delivery.enviados', 0);
    }

    private function authHeaders(string $token): array
    {
        return [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ];
    }
}
