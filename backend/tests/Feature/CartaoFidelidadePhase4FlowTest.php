<?php

namespace Tests\Feature;

use App\Models\CartaoFidelidade;
use App\Models\CartaoFidelidadeMovimento;
use App\Models\Empresa;
use App\Models\InscricaoEmpresa;
use App\Models\User;
use App\Services\ClienteQrCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartaoFidelidadePhase4FlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_company_can_create_update_and_toggle_loyalty_card(): void
    {
        [, $empresa, $token] = $this->makeActiveCompany();

        $create = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/empresa/cartao-fidelidade', [
                'titulo' => 'Ganhe 1 ponto por visita',
                'descricao' => 'Acumule pontos e troque por uma sobremesa.',
                'regra_ganho' => 'Ganhe 1 ponto a cada visita.',
                'pontos_por_visita' => 1,
                'pontos_necessarios' => 10,
                'recompensa_descricao' => 'Uma sobremesa gratis',
                'data_expiracao' => now()->addDays(30)->toDateString(),
                'ativo' => true,
            ]);

        $create
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.empresa_id', $empresa->id)
            ->assertJsonPath('data.titulo', 'Ganhe 1 ponto por visita')
            ->assertJsonPath('data.pontos_por_visita', 1)
            ->assertJsonPath('data.pontos_necessarios', 10)
            ->assertJsonPath('data.ativo', true);

        $cardId = (int) $create->json('data.id');

        $this->assertDatabaseHas('cartoes_fidelidade', [
            'id' => $cardId,
            'empresa_id' => $empresa->id,
            'titulo' => 'Ganhe 1 ponto por visita',
            'meta_pontos' => 10,
            'ativo' => true,
        ]);

        $update = $this->withHeaders($this->authHeaders($token))
            ->putJson("/api/empresa/cartao-fidelidade/{$cardId}", [
                'titulo' => 'Ganhe 2 pontos por visita',
                'pontos_por_visita' => 2,
                'recompensa_descricao' => 'Uma sobremesa premium',
            ]);

        $update
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.titulo', 'Ganhe 2 pontos por visita')
            ->assertJsonPath('data.pontos_por_visita', 2);

        $toggle = $this->withHeaders($this->authHeaders($token))
            ->patchJson("/api/empresa/cartao-fidelidade/{$cardId}/toggle", [
                'ativo' => false,
            ]);

        $toggle
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.ativo', false);
    }

    public function test_company_can_consult_customer_qr_add_points_and_redeem_reward(): void
    {
        [$companyUser, $empresa, $companyToken] = $this->makeActiveCompany();

        $card = CartaoFidelidade::query()->create([
            'empresa_id' => $empresa->id,
            'titulo' => 'Ganhe 2 pontos por visita',
            'descricao' => 'A cada visita, acumule pontos para trocar por uma bebida.',
            'regra_ganho' => 'Ganhe 2 pontos a cada visita.',
            'pontos_por_visita' => 2,
            'pontos_necessarios' => 5,
            'recompensa_descricao' => 'Uma bebida gratis',
            'meta_pontos' => 5,
            'recompensa' => 'Uma bebida gratis',
            'ativo' => true,
        ]);

        $customer = User::factory()->create([
            'perfil' => 'cliente',
            'status' => 'ativo',
            'telefone' => '(11) 99999-2000',
        ]);
        $customerToken = $customer->createToken('phase4-customer')->plainTextToken;

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
            ->assertJsonPath('data.cartao_fidelidade.card.id', $card->id)
            ->assertJsonPath('data.cartao_fidelidade.progress.current_points', 0)
            ->assertJsonPath('data.cartao_fidelidade.can_add_point', true);

        $this->withHeaders($this->authHeaders($companyToken))
            ->postJson("/api/empresa/cartao-fidelidade/{$card->id}/clientes/{$customer->id}/adicionar-ponto")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.cartao_fidelidade.progress.current_points', 2);

        $this->travel(16)->seconds();

        $this->withHeaders($this->authHeaders($companyToken))
            ->postJson("/api/empresa/cartao-fidelidade/{$card->id}/clientes/{$customer->id}/adicionar-ponto")
            ->assertOk()
            ->assertJsonPath('data.cartao_fidelidade.progress.current_points', 4);

        $this->travel(16)->seconds();

        $thirdPoint = $this->withHeaders($this->authHeaders($companyToken))
            ->postJson("/api/empresa/cartao-fidelidade/{$card->id}/clientes/{$customer->id}/adicionar-ponto");

        $thirdPoint
            ->assertOk()
            ->assertJsonPath('data.cartao_fidelidade.progress.current_points', 6)
            ->assertJsonPath('data.cartao_fidelidade.progress.reward_available', true);

        $customerProgress = $this->withHeaders($this->authHeaders($customerToken))
            ->getJson("/api/cliente/cartao-fidelidade/progresso/{$empresa->id}");

        $customerProgress
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'reward_available')
            ->assertJsonPath('data.progress.current_points', 6);

        $redeem = $this->withHeaders($this->authHeaders($companyToken))
            ->postJson("/api/empresa/cartao-fidelidade/{$card->id}/clientes/{$customer->id}/resgatar");

        $redeem
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.cartao_fidelidade.progress.current_points', 1)
            ->assertJsonPath('data.cartao_fidelidade.progress.times_redeemed', 1);

        $secondRedeem = $this->withHeaders($this->authHeaders($companyToken))
            ->postJson("/api/empresa/cartao-fidelidade/{$card->id}/clientes/{$customer->id}/resgatar");

        $secondRedeem
            ->assertStatus(409)
            ->assertJsonPath('success', false);

        $this->assertDatabaseCount('cartoes_fidelidade_movimentos', 4);
        $this->assertDatabaseHas('cartoes_fidelidade_movimentos', [
            'cartao_fidelidade_id' => $card->id,
            'empresa_id' => $empresa->id,
            'user_id' => $customer->id,
            'tipo' => CartaoFidelidadeMovimento::TYPE_REDEEMED,
            'pontos' => 5,
            'created_by' => $companyUser->id,
        ]);
    }

    public function test_public_company_detail_exposes_general_loyalty_card_but_other_company_cannot_operate_it(): void
    {
        [, $empresaA] = $this->makeActiveCompany();
        [, $empresaB, $companyTokenB] = $this->makeActiveCompany('phase4-company-b');

        $cardA = CartaoFidelidade::query()->create([
            'empresa_id' => $empresaA->id,
            'titulo' => 'Cartao restaurante',
            'descricao' => 'Pontue e troque por um brinde.',
            'regra_ganho' => 'Ganhe 1 ponto a cada visita.',
            'pontos_por_visita' => 1,
            'pontos_necessarios' => 8,
            'recompensa_descricao' => 'Um brinde',
            'meta_pontos' => 8,
            'recompensa' => 'Um brinde',
            'ativo' => true,
        ]);

        $detail = $this->getJson("/api/empresas/{$empresaA->id}");
        $detail
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.cartao_fidelidade.id', $cardA->id)
            ->assertJsonPath('data.cartao_fidelidade.pontos_necessarios', 8);

        $customer = User::factory()->create([
            'perfil' => 'cliente',
            'status' => 'ativo',
        ]);

        $wrongCompany = $this->withHeaders($this->authHeaders($companyTokenB))
            ->postJson("/api/empresa/cartao-fidelidade/{$cardA->id}/clientes/{$customer->id}/adicionar-ponto");

        $wrongCompany
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

    private function makeActiveCompany(string $tokenName = 'phase4-company'): array
    {
        return $this->makeCompany(Empresa::STATUS_ACTIVE, true, 'ativo', $tokenName);
    }

    private function makeCompany(
        string $status,
        bool $ativo,
        string $userStatus,
        string $tokenName = 'phase4-company'
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
