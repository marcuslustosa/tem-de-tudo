<?php

namespace Tests\Feature;

use App\Models\BonusAniversario;
use App\Models\BonusAniversarioResgate;
use App\Models\Empresa;
use App\Models\InscricaoEmpresa;
use App\Models\LembreteAusencia;
use App\Models\LembreteEnvio;
use App\Models\NotificacaoPush;
use App\Models\PushSubscription;
use App\Models\User;
use App\Services\ClienteQrCodeService;
use App\Services\WebPushDeliveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BonusAniversarioPhase6FlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_company_can_create_update_toggle_birthday_bonus_and_public_detail_exposes_it(): void
    {
        [, $empresa, $token] = $this->makeActiveCompany();

        $create = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/empresa/bonus-aniversario', [
                'titulo' => 'Mes do aniversariante',
                'descricao' => 'Ganhe uma sobremesa especial no mes do seu aniversario.',
                'imagem_url' => 'https://example.com/aniversario.jpg',
                'notification_title' => 'Seu bonus aniversario chegou',
                'notification_body' => 'Passe na loja e apresente seu QR Code para validar.',
                'ativo' => true,
            ]);

        $create
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.empresa_id', $empresa->id)
            ->assertJsonPath('data.titulo', 'Mes do aniversariante')
            ->assertJsonPath('data.validade_tipo', 'birthday_month');

        $bonusId = (int) $create->json('data.id');

        $detail = $this->getJson("/api/empresas/{$empresa->id}");
        $detail
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.bonus_aniversario.id', $bonusId);

        $update = $this->withHeaders($this->authHeaders($token))
            ->putJson("/api/empresa/bonus-aniversario/{$bonusId}", [
                'titulo' => 'Semana do aniversariante',
                'dias_validade' => 7,
                'notification_body' => 'Valido por 7 dias a partir do seu aniversario.',
            ]);

        $update
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.titulo', 'Semana do aniversariante')
            ->assertJsonPath('data.dias_validade', 7)
            ->assertJsonPath('data.validade_tipo', 'days_after_birthday');

        $toggle = $this->withHeaders($this->authHeaders($token))
            ->patchJson("/api/empresa/bonus-aniversario/{$bonusId}/toggle", [
                'ativo' => false,
            ]);

        $toggle
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.ativo', false);
    }

    public function test_blocked_company_cannot_manage_birthday_bonus(): void
    {
        [, , $token] = $this->makeCompany(Empresa::STATUS_SUSPENDED, false, 'ativo', 'phase6-suspended-company');

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/empresa/bonus-aniversario', [
                'titulo' => 'Nao deveria salvar',
                'descricao' => 'Empresa suspensa nao pode operar este bonus.',
                'ativo' => true,
            ]);

        $response
            ->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'company_status_blocked');
    }

    public function test_customer_can_view_birthday_bonus_and_company_can_validate_once_per_year(): void
    {
        [$companyUser, $empresa] = $this->makeActiveCompany();
        [$wrongCompanyUser] = $this->makeActiveCompany('phase6-company-b');

        $bonus = BonusAniversario::query()->create([
            'empresa_id' => $empresa->id,
            'titulo' => 'Bolo do aniversariante',
            'presente' => 'Bolo do aniversariante',
            'descricao' => 'Valido somente no estabelecimento, lendo o QR do cliente.',
            'imagem' => 'https://example.com/bolo.jpg',
            'notification_title' => 'Seu presente de aniversario',
            'notification_body' => 'Mostre seu QR Code para validar.',
            'ativo' => true,
        ]);

        $customer = User::factory()->create([
            'perfil' => 'cliente',
            'status' => 'ativo',
            'telefone' => '(11) 98888-2000',
            'data_nascimento' => now()->subYears(28)->month(now()->month)->day(now()->day)->toDateString(),
        ]);
        InscricaoEmpresa::query()->create([
            'user_id' => $customer->id,
            'empresa_id' => $empresa->id,
            'data_inscricao' => now()->subMonths(2),
            'ultima_visita' => now()->subDays(12),
            'bonus_adesao_resgatado' => false,
        ]);

        Sanctum::actingAs($customer);

        $available = $this
            ->getJson("/api/cliente/bonus-aniversario/disponiveis?empresa_id={$empresa->id}");

        $available
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.items.0.status', BonusAniversarioResgate::STATUS_AVAILABLE)
            ->assertJsonPath('data.items.0.bonus.id', $bonus->id);

        $customerQr = app(ClienteQrCodeService::class)->gerar($customer);

        Sanctum::actingAs($companyUser);

        $lookup = $this
            ->postJson('/api/empresa/clientes/qrcode/consultar', [
                'qrcode' => $customerQr['code'],
            ]);

        $lookup
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.cliente.id', $customer->id)
            ->assertJsonPath('data.bonus_aniversario.status', BonusAniversarioResgate::STATUS_AVAILABLE)
            ->assertJsonPath('data.bonus_aniversario.bonus.id', $bonus->id);

        Sanctum::actingAs($wrongCompanyUser);

        $wrongCompany = $this
            ->postJson("/api/empresa/bonus-aniversario/{$bonus->id}/validar", [
                'cliente_id' => $customer->id,
            ]);

        $wrongCompany
            ->assertStatus(409)
            ->assertJsonPath('success', false);

        Sanctum::actingAs($companyUser);

        $validate = $this
            ->postJson("/api/empresa/bonus-aniversario/{$bonus->id}/validar", [
                'cliente_id' => $customer->id,
            ]);

        $validate
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.bonus_aniversario.status', BonusAniversarioResgate::STATUS_REDEEMED);

        Sanctum::actingAs($companyUser);

        $duplicate = $this
            ->postJson("/api/empresa/bonus-aniversario/{$bonus->id}/validar", [
                'cliente_id' => $customer->id,
            ]);

        $duplicate
            ->assertStatus(409)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('bonus_aniversario_resgates', [
            'bonus_aniversario_id' => $bonus->id,
            'empresa_id' => $empresa->id,
            'user_id' => $customer->id,
            'ano' => (int) now()->year,
            'status' => BonusAniversarioResgate::STATUS_REDEEMED,
            'validated_by' => $companyUser->id,
        ]);
    }

    public function test_company_can_send_birthday_bonus_only_to_linked_eligible_customers(): void
    {
        [, $empresa, $companyToken] = $this->makeActiveCompany();

        $bonus = BonusAniversario::query()->create([
            'empresa_id' => $empresa->id,
            'titulo' => 'Presente do aniversariante',
            'presente' => 'Presente do aniversariante',
            'descricao' => 'Passe na loja para validar presencialmente.',
            'notification_title' => 'Seu presente chegou',
            'notification_body' => 'Mostre seu QR Code para resgatar.',
            'ativo' => true,
        ]);

        $eligibleCustomer = User::factory()->create([
            'perfil' => 'cliente',
            'status' => 'ativo',
            'data_nascimento' => now()->subYears(31)->day(9)->toDateString(),
        ]);
        $notLinkedCustomer = User::factory()->create([
            'perfil' => 'cliente',
            'status' => 'ativo',
            'data_nascimento' => now()->subYears(24)->day(5)->toDateString(),
        ]);
        $outOfWindowCustomer = User::factory()->create([
            'perfil' => 'cliente',
            'status' => 'ativo',
            'data_nascimento' => now()->subYears(22)->addMonthNoOverflow()->day(3)->toDateString(),
        ]);

        InscricaoEmpresa::query()->create([
            'user_id' => $eligibleCustomer->id,
            'empresa_id' => $empresa->id,
            'data_inscricao' => now()->subMonth(),
            'bonus_adesao_resgatado' => false,
        ]);
        InscricaoEmpresa::query()->create([
            'user_id' => $outOfWindowCustomer->id,
            'empresa_id' => $empresa->id,
            'data_inscricao' => now()->subMonth(),
            'bonus_adesao_resgatado' => false,
        ]);

        PushSubscription::query()->create([
            'user_id' => $eligibleCustomer->id,
            'endpoint' => 'https://push.example.com/sub/eligible',
            'p256dh' => 'key-eligible',
            'auth' => 'auth-eligible',
        ]);
        PushSubscription::query()->create([
            'user_id' => $notLinkedCustomer->id,
            'endpoint' => 'https://push.example.com/sub/not-linked',
            'p256dh' => 'key-not-linked',
            'auth' => 'auth-not-linked',
        ]);
        PushSubscription::query()->create([
            'user_id' => $outOfWindowCustomer->id,
            'endpoint' => 'https://push.example.com/sub/out-window',
            'p256dh' => 'key-out-window',
            'auth' => 'auth-out-window',
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
                    'sent' => true,
                    'error' => null,
                ]);
        });

        $send = $this->withHeaders($this->authHeaders($companyToken))
            ->postJson("/api/empresa/bonus-aniversario/{$bonus->id}/enviar-elegiveis");

        $send
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.delivery.total_targeted', 1)
            ->assertJsonPath('meta.delivery.total_sent', 1)
            ->assertJsonPath('meta.delivery.total_failed', 0);

        $this->assertDatabaseHas('notificacoes_push', [
            'user_id' => $eligibleCustomer->id,
            'empresa_id' => $empresa->id,
            'bonus_aniversario_id' => $bonus->id,
            'tipo' => 'aniversario',
            'status' => 'sent',
        ]);
        $this->assertDatabaseMissing('notificacoes_push', [
            'user_id' => $notLinkedCustomer->id,
            'bonus_aniversario_id' => $bonus->id,
        ]);
        $this->assertDatabaseMissing('notificacoes_push', [
            'user_id' => $outOfWindowCustomer->id,
            'bonus_aniversario_id' => $bonus->id,
        ]);
    }

    public function test_company_can_configure_and_send_return_reminder_only_once_per_cycle(): void
    {
        [, $empresa, $companyToken] = $this->makeActiveCompany();

        $create = $this->withHeaders($this->authHeaders($companyToken))
            ->postJson('/api/empresa/lembrete-retorno', [
                'dias_sem_visita' => 30,
                'titulo' => 'Sentimos sua falta',
                'mensagem' => 'Faz um tempo desde sua ultima visita. Volte para conferir as novidades.',
                'ativo' => true,
            ]);

        $create
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.dias_sem_visita', 30);

        $reminderId = (int) $create->json('data.id');

        $update = $this->withHeaders($this->authHeaders($companyToken))
            ->putJson("/api/empresa/lembrete-retorno/{$reminderId}", [
                'dias_sem_visita' => 21,
                'titulo' => 'Volte a nos visitar',
            ]);

        $update
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.dias_sem_visita', 21)
            ->assertJsonPath('data.titulo', 'Volte a nos visitar');

        $this->withHeaders($this->authHeaders($companyToken))
            ->patchJson("/api/empresa/lembrete-retorno/{$reminderId}/toggle", ['ativo' => false])
            ->assertOk()
            ->assertJsonPath('data.ativo', false);

        $this->withHeaders($this->authHeaders($companyToken))
            ->patchJson("/api/empresa/lembrete-retorno/{$reminderId}/toggle", ['ativo' => true])
            ->assertOk()
            ->assertJsonPath('data.ativo', true);

        $customer = User::factory()->create([
            'perfil' => 'cliente',
            'status' => 'ativo',
        ]);
        InscricaoEmpresa::query()->create([
            'user_id' => $customer->id,
            'empresa_id' => $empresa->id,
            'data_inscricao' => now()->subMonths(4),
            'ultima_visita' => now()->subDays(45),
            'bonus_adesao_resgatado' => false,
        ]);
        PushSubscription::query()->create([
            'user_id' => $customer->id,
            'endpoint' => 'https://push.example.com/sub/reminder',
            'p256dh' => 'key-reminder',
            'auth' => 'auth-reminder',
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
                    'sent' => true,
                    'error' => null,
                ]);
        });

        $send = $this->withHeaders($this->authHeaders($companyToken))
            ->postJson('/api/empresa/lembrete-retorno/enviar-elegiveis', [
                'lembrete_id' => $reminderId,
            ]);

        $send
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.delivery.total_targeted', 1)
            ->assertJsonPath('meta.delivery.total_sent', 1);

        $duplicate = $this->withHeaders($this->authHeaders($companyToken))
            ->postJson('/api/empresa/lembrete-retorno/enviar-elegiveis', [
                'lembrete_id' => $reminderId,
            ]);

        $duplicate
            ->assertStatus(409)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('lembrete_envios', [
            'lembrete_id' => $reminderId,
            'empresa_id' => $empresa->id,
            'user_id' => $customer->id,
            'status' => LembreteEnvio::STATUS_SENT,
        ]);
        $this->assertDatabaseHas('notificacoes_push', [
            'user_id' => $customer->id,
            'empresa_id' => $empresa->id,
            'lembrete_id' => $reminderId,
            'tipo' => 'lembrete',
            'status' => LembreteEnvio::STATUS_SENT,
        ]);
    }

    private function authHeaders(string $token): array
    {
        return [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ];
    }

    private function makeActiveCompany(string $tokenName = 'phase6-company'): array
    {
        return $this->makeCompany(Empresa::STATUS_ACTIVE, true, 'ativo', $tokenName);
    }

    private function makeCompany(
        string $status,
        bool $ativo,
        string $userStatus,
        string $tokenName = 'phase6-company'
    ): array {
        $companyUser = User::factory()->create([
            'perfil' => 'empresa',
            'status' => $userStatus,
        ]);

        $empresa = Empresa::factory()->create([
            'owner_id' => $companyUser->id,
            'ativo' => $ativo,
            'status' => $status,
        ]);

        $token = $companyUser->createToken($tokenName)->plainTextToken;

        return [$companyUser, $empresa, $token];
    }
}
