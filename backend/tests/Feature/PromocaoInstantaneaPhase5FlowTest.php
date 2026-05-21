<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\InscricaoEmpresa;
use App\Models\Promocao;
use App\Models\PromocaoResgate;
use App\Models\PushSubscription;
use App\Models\User;
use App\Services\ClienteQrCodeService;
use App\Services\PromocaoInstantaneaService;
use App\Services\WebPushDeliveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromocaoInstantaneaPhase5FlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_company_can_create_update_and_toggle_instant_promotion(): void
    {
        [, $empresa, $token] = $this->makeActiveCompany();

        $create = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/empresa/promocoes', [
                'titulo' => 'Combo da tarde',
                'descricao' => 'Cafe com salgado por tempo limitado.',
                'imagem_url' => 'https://example.com/promo-combo.jpg',
                'validade' => now()->addDays(5)->toDateString(),
                'notification_title' => 'Oferta relampago',
                'notification_body' => 'Passe hoje e aproveite a promocao.',
                'ativo' => true,
            ]);

        $create
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.empresa_id', $empresa->id)
            ->assertJsonPath('data.titulo', 'Combo da tarde')
            ->assertJsonPath('data.status', 'available')
            ->assertJsonPath('data.notification_title', 'Oferta relampago');

        $promotionId = (int) $create->json('data.id');

        $this->assertDatabaseHas('promocoes', [
            'id' => $promotionId,
            'empresa_id' => $empresa->id,
            'titulo' => 'Combo da tarde',
            'ativo' => true,
        ]);

        $update = $this->withHeaders($this->authHeaders($token))
            ->putJson("/api/empresa/promocoes/{$promotionId}", [
                'titulo' => 'Combo premium da tarde',
                'notification_body' => 'Promocao ajustada para clientes vinculados.',
            ]);

        $update
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.titulo', 'Combo premium da tarde');

        $toggle = $this->withHeaders($this->authHeaders($token))
            ->patchJson("/api/empresa/promocoes/{$promotionId}/toggle", [
                'ativo' => false,
            ]);

        $toggle
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.ativo', false);
    }

    public function test_customer_can_view_but_cannot_self_redeem_promotion(): void
    {
        [, $empresa] = $this->makeActiveCompany();

        $promocao = Promocao::query()->create([
            'empresa_id' => $empresa->id,
            'titulo' => 'Sobremesa gratis',
            'descricao' => 'Valida somente no balcao com QR do cliente.',
            'imagem' => 'https://example.com/sobremesa.jpg',
            'notification_title' => 'Sobremesa liberada',
            'notification_body' => 'Passe no estabelecimento para validar.',
            'validade' => now()->addDays(7)->toDateString(),
            'ativo' => true,
            'status' => Promocao::STATUS_ACTIVE,
        ]);

        $customer = User::factory()->create([
            'perfil' => 'cliente',
            'status' => 'ativo',
        ]);
        $customerToken = $customer->createToken('phase5-customer')->plainTextToken;

        InscricaoEmpresa::query()->create([
            'user_id' => $customer->id,
            'empresa_id' => $empresa->id,
            'data_inscricao' => now(),
            'bonus_adesao_resgatado' => false,
        ]);

        $publicList = $this->getJson("/api/empresas/{$empresa->id}/promocoes");
        $publicList
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.id', $promocao->id);

        $customerList = $this->withHeaders($this->authHeaders($customerToken))
            ->getJson("/api/cliente/promocoes?empresa_id={$empresa->id}");

        $customerList
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.id', $promocao->id)
            ->assertJsonPath('data.0.viewer_status', 'available');

        $blocked = $this->withHeaders($this->authHeaders($customerToken))
            ->postJson("/api/cliente/promocoes/{$promocao->id}/resgatar");

        $blocked
            ->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('data.promotions_snapshot.available_count', 1);

        $this->assertDatabaseCount('promocao_resgates', 0);
    }

    public function test_company_can_validate_linked_customer_promotion_once(): void
    {
        [$companyUser, $empresa, $companyToken] = $this->makeActiveCompany();

        $promocao = Promocao::query()->create([
            'empresa_id' => $empresa->id,
            'titulo' => 'Bebida do dia',
            'descricao' => 'Promocao valida apenas no atendimento presencial.',
            'imagem' => 'https://example.com/bebida.jpg',
            'notification_title' => 'Bebida do dia',
            'notification_body' => 'Passe hoje para validar.',
            'validade' => now()->addDays(4)->toDateString(),
            'ativo' => true,
            'status' => Promocao::STATUS_ACTIVE,
        ]);

        $customer = User::factory()->create([
            'perfil' => 'cliente',
            'status' => 'ativo',
            'telefone' => '(11) 98888-2000',
        ]);

        InscricaoEmpresa::query()->create([
            'user_id' => $customer->id,
            'empresa_id' => $empresa->id,
            'data_inscricao' => now(),
            'bonus_adesao_resgatado' => false,
        ]);

        $customerQr = app(ClienteQrCodeService::class)->gerar($customer);

        $lookup = $this->withHeaders($this->authHeaders($companyToken))
            ->postJson('/api/empresa/clientes/qrcode/consultar', [
                'qrcode' => $customerQr['code'],
            ]);

        $lookup
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.cliente.id', $customer->id)
            ->assertJsonPath('data.promocoes.items.0.id', $promocao->id)
            ->assertJsonPath('data.promocoes.items.0.viewer_status', 'available');

        $validate = $this->withHeaders($this->authHeaders($companyToken))
            ->postJson("/api/empresa/promocoes/{$promocao->id}/validar", [
                'cliente_id' => $customer->id,
            ]);

        $validate
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.promocoes.redeemed_count', 1);

        $duplicate = $this->withHeaders($this->authHeaders($companyToken))
            ->postJson("/api/empresa/promocoes/{$promocao->id}/validar", [
                'cliente_id' => $customer->id,
            ]);

        $duplicate
            ->assertStatus(409)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('promocao_resgates', [
            'promocao_id' => $promocao->id,
            'empresa_id' => $empresa->id,
            'user_id' => $customer->id,
            'status' => PromocaoResgate::STATUS_REDEEMED,
            'validated_by' => $companyUser->id,
        ]);
    }

    public function test_company_weekly_send_limit_blocks_third_promotion(): void
    {
        [, $empresa, $token] = $this->makeActiveCompany();

        Promocao::query()->create([
            'empresa_id' => $empresa->id,
            'titulo' => 'Push 1',
            'descricao' => 'Primeira campanha enviada.',
            'imagem' => 'https://example.com/p1.jpg',
            'notification_title' => 'Push 1',
            'notification_body' => 'Primeira campanha.',
            'validade' => now()->addDays(3)->toDateString(),
            'ativo' => true,
            'status' => Promocao::STATUS_ACTIVE,
            'data_envio' => now()->subDay(),
        ]);

        Promocao::query()->create([
            'empresa_id' => $empresa->id,
            'titulo' => 'Push 2',
            'descricao' => 'Segunda campanha enviada.',
            'imagem' => 'https://example.com/p2.jpg',
            'notification_title' => 'Push 2',
            'notification_body' => 'Segunda campanha.',
            'validade' => now()->addDays(3)->toDateString(),
            'ativo' => true,
            'status' => Promocao::STATUS_ACTIVE,
            'data_envio' => now()->subHours(12),
        ]);

        $third = Promocao::query()->create([
            'empresa_id' => $empresa->id,
            'titulo' => 'Push 3',
            'descricao' => 'Terceira campanha deve ser bloqueada.',
            'imagem' => 'https://example.com/p3.jpg',
            'notification_title' => 'Push 3',
            'notification_body' => 'Terceira campanha.',
            'validade' => now()->addDays(3)->toDateString(),
            'ativo' => true,
            'status' => Promocao::STATUS_ACTIVE,
        ]);

        $blocked = $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/empresa/promocoes/{$third->id}/enviar");

        $blocked
            ->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    public function test_company_sends_promotion_only_to_linked_customers_with_active_subscription(): void
    {
        [, $empresa, $token] = $this->makeActiveCompany();

        $promocao = Promocao::query()->create([
            'empresa_id' => $empresa->id,
            'titulo' => 'Menu executivo especial',
            'descricao' => 'Oferta valida apenas para clientes vinculados.',
            'imagem' => 'https://example.com/menu.jpg',
            'notification_title' => 'Oferta de hoje',
            'notification_body' => 'Passe no estabelecimento e apresente seu QR Code.',
            'validade' => now()->addDays(4)->toDateString(),
            'ativo' => true,
            'status' => Promocao::STATUS_ACTIVE,
        ]);

        $linkedWithSubscription = User::factory()->create([
            'perfil' => 'cliente',
            'status' => 'ativo',
        ]);
        $linkedWithoutSubscription = User::factory()->create([
            'perfil' => 'cliente',
            'status' => 'ativo',
        ]);
        $notLinkedWithSubscription = User::factory()->create([
            'perfil' => 'cliente',
            'status' => 'ativo',
        ]);

        InscricaoEmpresa::query()->create([
            'user_id' => $linkedWithSubscription->id,
            'empresa_id' => $empresa->id,
            'data_inscricao' => now()->subMonth(),
            'bonus_adesao_resgatado' => false,
        ]);
        InscricaoEmpresa::query()->create([
            'user_id' => $linkedWithoutSubscription->id,
            'empresa_id' => $empresa->id,
            'data_inscricao' => now()->subWeeks(2),
            'bonus_adesao_resgatado' => false,
        ]);

        PushSubscription::query()->create([
            'user_id' => $linkedWithSubscription->id,
            'endpoint' => 'https://push.example.com/sub/linked-with-subscription',
            'public_key' => 'public-key-linked-with-subscription',
            'auth_token' => 'auth-linked-with-subscription',
            'p256dh' => 'public-key-linked-with-subscription',
            'auth' => 'auth-linked-with-subscription',
            'content_encoding' => 'aes128gcm',
        ]);
        PushSubscription::query()->create([
            'user_id' => $notLinkedWithSubscription->id,
            'endpoint' => 'https://push.example.com/sub/not-linked',
            'public_key' => 'public-key-not-linked',
            'auth_token' => 'auth-not-linked',
            'p256dh' => 'public-key-not-linked',
            'auth' => 'auth-not-linked',
            'content_encoding' => 'aes128gcm',
        ]);

        $this->mock(WebPushDeliveryService::class, function ($mock): void {
            $mock->shouldReceive('auth')->once()->andReturn([
                'VAPID' => [
                    'subject' => 'mailto:test@example.com',
                    'publicKey' => 'public',
                    'privateKey' => 'private',
                ],
            ]);
            $mock->shouldReceive('deliverToSubscriptions')
                ->once()
                ->andReturn([
                    'status' => 'sent',
                    'sent' => true,
                    'error' => null,
                    'subscriptions_sent' => 1,
                    'subscriptions_failed' => 0,
                    'subscriptions_skipped' => 0,
                    'invalidated' => 0,
                    'errors' => [],
                    'config_missing' => false,
                ]);
        });

        $send = $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/empresa/promocoes/{$promocao->id}/enviar");

        $send
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.delivery.total_elegiveis', 2)
            ->assertJsonPath('meta.delivery.total_com_subscription', 1)
            ->assertJsonPath('meta.delivery.enviados', 1)
            ->assertJsonPath('meta.delivery.ignorados_sem_subscription', 1)
            ->assertJsonPath('meta.delivery.ignorados_sem_vinculo', 0);

        $this->assertDatabaseHas('notificacoes_push', [
            'user_id' => $linkedWithSubscription->id,
            'empresa_id' => $empresa->id,
            'promocao_id' => $promocao->id,
            'tipo' => 'promocao',
            'status' => 'sent',
        ]);
        $this->assertDatabaseHas('notificacoes_push', [
            'user_id' => $linkedWithoutSubscription->id,
            'empresa_id' => $empresa->id,
            'promocao_id' => $promocao->id,
            'tipo' => 'promocao',
            'status' => PromocaoInstantaneaService::LOG_STATUS_NO_SUBSCRIPTION,
        ]);
        $this->assertDatabaseMissing('notificacoes_push', [
            'user_id' => $notLinkedWithSubscription->id,
            'promocao_id' => $promocao->id,
        ]);
    }

    public function test_company_promotion_send_returns_config_missing_without_500(): void
    {
        [, $empresa, $token] = $this->makeActiveCompany();

        $promocao = Promocao::query()->create([
            'empresa_id' => $empresa->id,
            'titulo' => 'Campanha sem VAPID',
            'descricao' => 'Nao deve quebrar quando o push nao esta configurado.',
            'imagem' => 'https://example.com/campanha-sem-vapid.jpg',
            'notification_title' => 'Configuração pendente',
            'notification_body' => 'Servidor ainda sem VAPID.',
            'validade' => now()->addDays(3)->toDateString(),
            'ativo' => true,
            'status' => Promocao::STATUS_ACTIVE,
        ]);

        $linkedCustomer = User::factory()->create([
            'perfil' => 'cliente',
            'status' => 'ativo',
        ]);

        InscricaoEmpresa::query()->create([
            'user_id' => $linkedCustomer->id,
            'empresa_id' => $empresa->id,
            'data_inscricao' => now()->subWeek(),
            'bonus_adesao_resgatado' => false,
        ]);

        PushSubscription::query()->create([
            'user_id' => $linkedCustomer->id,
            'endpoint' => 'https://push.example.com/sub/config-missing',
            'public_key' => 'public-key-config-missing',
            'auth_token' => 'auth-config-missing',
            'p256dh' => 'public-key-config-missing',
            'auth' => 'auth-config-missing',
            'content_encoding' => 'aes128gcm',
        ]);

        $this->mock(WebPushDeliveryService::class, function ($mock): void {
            $mock->shouldReceive('auth')->once()->andReturn(null);
        });

        $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/empresa/promocoes/{$promocao->id}/enviar")
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'config_missing')
            ->assertJsonPath('meta.delivery.total_elegiveis', 1)
            ->assertJsonPath('meta.delivery.total_com_subscription', 1)
            ->assertJsonPath('meta.delivery.enviados', 0)
            ->assertJsonPath('meta.delivery.falhas', 1);
    }

    private function authHeaders(string $token): array
    {
        return [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ];
    }

    private function makeActiveCompany(string $tokenName = 'phase5-company'): array
    {
        $companyUser = User::factory()->create([
            'perfil' => 'empresa',
            'status' => 'ativo',
        ]);

        $empresa = Empresa::factory()->create([
            'owner_id' => $companyUser->id,
            'ativo' => true,
            'status' => Empresa::STATUS_ACTIVE,
        ]);

        $token = $companyUser->createToken($tokenName)->plainTextToken;

        return [$companyUser, $empresa, $token];
    }
}
