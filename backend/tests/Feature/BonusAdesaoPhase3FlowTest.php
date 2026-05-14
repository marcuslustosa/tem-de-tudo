<?php

namespace Tests\Feature;

use App\Models\BonusAdesao;
use App\Models\BonusAdesaoResgate;
use App\Models\Empresa;
use App\Models\InscricaoEmpresa;
use App\Models\User;
use App\Services\ClienteQrCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BonusAdesaoPhase3FlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_company_can_create_update_and_toggle_adhesion_bonus(): void
    {
        Storage::fake('public');

        [$companyUser, $empresa, $token] = $this->makeActiveCompany();

        $create = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/empresa/bonus-adesao', [
                'titulo' => 'Brinde de boas-vindas',
                'descricao' => 'Ganhe um cafe na primeira compra.',
                'data_expiracao' => now()->addDays(10)->toDateString(),
                'imagem_url' => 'https://cdn.exemplo.com/bonus.jpg',
                'termos' => 'Valido apenas uma vez por cliente vinculado.',
                'ativo' => true,
            ]);

        $create
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.empresa_id', $empresa->id)
            ->assertJsonPath('data.titulo', 'Brinde de boas-vindas')
            ->assertJsonPath('data.tipo', BonusAdesao::TYPE_ADHESION_BONUS)
            ->assertJsonPath('data.limite_por_cliente', 1)
            ->assertJsonPath('data.ativo', true);

        $bonusId = (int) $create->json('data.id');

        $this->assertDatabaseHas('bonus_adesao', [
            'id' => $bonusId,
            'empresa_id' => $empresa->id,
            'titulo' => 'Brinde de boas-vindas',
            'ativo' => true,
        ]);

        $update = $this->withHeaders($this->authHeaders($token))
            ->putJson("/api/empresa/bonus-adesao/{$bonusId}", [
                'titulo' => 'Brinde premium',
                'descricao' => 'Ganhe um cafe premium na primeira compra.',
                'ativo' => true,
            ]);

        $update
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.titulo', 'Brinde premium');

        $toggle = $this->withHeaders($this->authHeaders($token))
            ->patchJson("/api/empresa/bonus-adesao/{$bonusId}/toggle", [
                'ativo' => false,
            ]);

        $toggle
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.ativo', false);
    }

    public function test_pending_company_cannot_manage_bonus(): void
    {
        [$companyUser, $empresa, $token] = $this->makeCompany(Empresa::STATUS_PENDING, false, 'pendente');

        $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/empresa/bonus-adesao', [
                'titulo' => 'Nao deveria criar',
                'descricao' => 'Empresa pendente nao pode operar.',
            ])
            ->assertStatus(403)
            ->assertJsonPath('error', 'company_status_blocked');
    }

    public function test_customer_can_view_linked_bonus_but_cannot_self_redeem(): void
    {
        [$companyUser, $empresa] = $this->makeActiveCompany();
        $bonus = BonusAdesao::query()->create([
            'empresa_id' => $empresa->id,
            'titulo' => 'Primeira compra com brinde',
            'descricao' => 'Valido apenas no primeiro atendimento.',
            'tipo_desconto' => 'valor_fixo',
            'valor_desconto' => 0,
            'ativo' => true,
            'data_expiracao' => now()->addDays(15),
            'limite_por_cliente' => 1,
            'tipo' => BonusAdesao::TYPE_ADHESION_BONUS,
            'ordem' => 1,
        ]);

        $customer = User::factory()->create([
            'perfil' => 'cliente',
            'status' => 'ativo',
        ]);
        $customerToken = $customer->createToken('phase3-customer')->plainTextToken;

        InscricaoEmpresa::query()->create([
            'user_id' => $customer->id,
            'empresa_id' => $empresa->id,
            'data_inscricao' => now(),
            'bonus_adesao_resgatado' => false,
        ]);

        $available = $this->withHeaders($this->authHeaders($customerToken))
            ->getJson("/api/cliente/bonus-adesao/disponivel/{$empresa->id}");

        $available
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', BonusAdesaoResgate::STATUS_AVAILABLE)
            ->assertJsonPath('data.bonus.id', $bonus->id)
            ->assertJsonPath('data.can_validate', true);

        $selfRedeem = $this->withHeaders($this->authHeaders($customerToken))
            ->postJson("/api/cliente/bonus-adesao/resgatar/{$empresa->id}");

        $selfRedeem
            ->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('data.status', BonusAdesaoResgate::STATUS_AVAILABLE);
    }

    public function test_company_can_consult_customer_qr_and_validate_bonus_once(): void
    {
        [$companyUser, $empresa, $companyToken] = $this->makeActiveCompany();
        $bonus = BonusAdesao::query()->create([
            'empresa_id' => $empresa->id,
            'titulo' => 'Suco gratis na primeira visita',
            'descricao' => 'Valido uma unica vez mediante leitura do QR do cliente.',
            'tipo_desconto' => 'valor_fixo',
            'valor_desconto' => 0,
            'ativo' => true,
            'data_expiracao' => now()->addDays(15),
            'limite_por_cliente' => 1,
            'tipo' => BonusAdesao::TYPE_ADHESION_BONUS,
            'ordem' => 1,
        ]);

        $customer = User::factory()->create([
            'perfil' => 'cliente',
            'status' => 'ativo',
            'telefone' => '(11) 99999-0000',
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
            ->assertJsonPath('data.bonus_adesao.status', BonusAdesaoResgate::STATUS_AVAILABLE)
            ->assertJsonPath('data.bonus_adesao.bonus.id', $bonus->id);

        $validate = $this->withHeaders($this->authHeaders($companyToken))
            ->postJson("/api/empresa/bonus-adesao/{$bonus->id}/validar", [
                'cliente_id' => $customer->id,
            ]);

        $validate
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.cliente.id', $customer->id)
            ->assertJsonPath('data.bonus_adesao.status', BonusAdesaoResgate::STATUS_REDEEMED);

        $this->assertDatabaseHas((new BonusAdesaoResgate())->getTable(), [
            'bonus_id' => $bonus->id,
            'empresa_id' => $empresa->id,
            'user_id' => $customer->id,
            'status' => BonusAdesaoResgate::STATUS_REDEEMED,
            'validated_by' => $companyUser->id,
        ]);

        $this->assertDatabaseHas('inscricoes_empresa', [
            'empresa_id' => $empresa->id,
            'user_id' => $customer->id,
            'bonus_adesao_resgatado' => true,
        ]);

        $duplicate = $this->withHeaders($this->authHeaders($companyToken))
            ->postJson("/api/empresa/bonus-adesao/{$bonus->id}/validar", [
                'cliente_id' => $customer->id,
            ]);

        $duplicate
            ->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    public function test_legacy_inscricao_flag_is_not_the_only_source_of_truth_when_company_has_multiple_bonus_configs(): void
    {
        [, $empresa] = $this->makeActiveCompany();

        BonusAdesao::query()->create([
            'empresa_id' => $empresa->id,
            'titulo' => 'Bonus legado inativo',
            'descricao' => 'Configuracao antiga apenas para compatibilidade.',
            'tipo_desconto' => 'valor_fixo',
            'valor_desconto' => 0,
            'ativo' => false,
            'limite_por_cliente' => 1,
            'tipo' => BonusAdesao::TYPE_ADHESION_BONUS,
            'ordem' => 1,
        ]);

        $activeBonus = BonusAdesao::query()->create([
            'empresa_id' => $empresa->id,
            'titulo' => 'Bonus vigente',
            'descricao' => 'Bonus atual ainda nao resgatado neste modelo.',
            'tipo_desconto' => 'valor_fixo',
            'valor_desconto' => 0,
            'ativo' => true,
            'limite_por_cliente' => 1,
            'tipo' => BonusAdesao::TYPE_ADHESION_BONUS,
            'ordem' => 2,
        ]);

        $customer = User::factory()->create([
            'perfil' => 'cliente',
            'status' => 'ativo',
        ]);
        $customerToken = $customer->createToken('phase3-customer-legacy-flag')->plainTextToken;

        InscricaoEmpresa::query()->create([
            'user_id' => $customer->id,
            'empresa_id' => $empresa->id,
            'data_inscricao' => now(),
            'bonus_adesao_resgatado' => true,
        ]);

        $available = $this->withHeaders($this->authHeaders($customerToken))
            ->getJson("/api/cliente/bonus-adesao/disponivel/{$empresa->id}");

        $available
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', BonusAdesaoResgate::STATUS_AVAILABLE)
            ->assertJsonPath('data.bonus.id', $activeBonus->id)
            ->assertJsonPath('data.can_validate', true);
    }

    public function test_company_cannot_validate_bonus_from_another_company_or_unlinked_customer(): void
    {
        [$companyUserA, $empresaA] = $this->makeActiveCompany();
        [, $empresaB, $companyTokenB] = $this->makeActiveCompany('phase3-company-b');

        $bonusA = BonusAdesao::query()->create([
            'empresa_id' => $empresaA->id,
            'titulo' => 'Bonus A',
            'descricao' => 'Bonus da empresa A.',
            'tipo_desconto' => 'valor_fixo',
            'valor_desconto' => 0,
            'ativo' => true,
            'limite_por_cliente' => 1,
            'tipo' => BonusAdesao::TYPE_ADHESION_BONUS,
            'ordem' => 1,
        ]);
        $bonusB = BonusAdesao::query()->create([
            'empresa_id' => $empresaB->id,
            'titulo' => 'Bonus B',
            'descricao' => 'Bonus da empresa B.',
            'tipo_desconto' => 'valor_fixo',
            'valor_desconto' => 0,
            'ativo' => true,
            'limite_por_cliente' => 1,
            'tipo' => BonusAdesao::TYPE_ADHESION_BONUS,
            'ordem' => 1,
        ]);

        $customer = User::factory()->create([
            'perfil' => 'cliente',
            'status' => 'ativo',
        ]);
        InscricaoEmpresa::query()->create([
            'user_id' => $customer->id,
            'empresa_id' => $empresaA->id,
            'data_inscricao' => now(),
            'bonus_adesao_resgatado' => false,
        ]);

        $customerQr = app(ClienteQrCodeService::class)->gerar($customer);

        $lookup = $this->withHeaders($this->authHeaders($companyTokenB))
            ->postJson('/api/empresa/clientes/qrcode/consultar', [
                'qrcode' => $customerQr['code'],
            ]);

        $lookup
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.cliente.id', $customer->id)
            ->assertJsonPath('data.bonus_adesao.status', 'not_linked');

        $wrongCompanyBonus = $this->withHeaders($this->authHeaders($companyTokenB))
            ->postJson("/api/empresa/bonus-adesao/{$bonusA->id}/validar", [
                'cliente_id' => $customer->id,
            ]);

        $wrongCompanyBonus
            ->assertStatus(409)
            ->assertJsonPath('success', false);

        $unlinkedValidation = $this->withHeaders($this->authHeaders($companyTokenB))
            ->postJson("/api/empresa/bonus-adesao/{$bonusB->id}/validar", [
                'cliente_id' => $customer->id,
            ]);

        $unlinkedValidation
            ->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    private function authHeaders(string $token): array
    {
        return [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ];
    }

    private function makeActiveCompany(string $tokenName = 'phase3-company'): array
    {
        return $this->makeCompany(Empresa::STATUS_ACTIVE, true, 'ativo', $tokenName);
    }

    private function makeCompany(
        string $status,
        bool $ativo,
        string $userStatus,
        string $tokenName = 'phase3-company'
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
