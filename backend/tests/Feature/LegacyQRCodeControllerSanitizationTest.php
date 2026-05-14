<?php

namespace Tests\Feature;

use App\Http\Controllers\QRCodeController;
use App\Models\BonusAdesao;
use App\Models\BonusAdesaoResgate;
use App\Models\Empresa;
use App\Models\QRCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class LegacyQRCodeControllerSanitizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_scan_empresa_no_longer_redeems_adhesion_bonus_or_flips_legacy_flag(): void
    {
        $customer = User::factory()->create([
            'perfil' => 'cliente',
            'status' => 'ativo',
        ]);

        $companyOwner = User::factory()->create([
            'perfil' => 'empresa',
            'status' => 'ativo',
        ]);

        $empresa = Empresa::factory()->create([
            'owner_id' => $companyOwner->id,
            'ativo' => true,
            'status' => Empresa::STATUS_ACTIVE,
        ]);

        $qrCode = QRCode::query()->create([
            'empresa_id' => $empresa->id,
            'name' => 'QR principal legado',
            'code' => QRCode::gerarCodigoUnico($empresa->id),
            'active' => true,
        ]);

        $bonus = BonusAdesao::query()->create([
            'empresa_id' => $empresa->id,
            'titulo' => 'Bonus de boas-vindas',
            'descricao' => 'Nao pode ser liberado automaticamente pelo fluxo legado.',
            'tipo_desconto' => 'valor_fixo',
            'valor_desconto' => 0,
            'ativo' => true,
            'limite_por_cliente' => 1,
            'tipo' => BonusAdesao::TYPE_ADHESION_BONUS,
            'ordem' => 1,
        ]);

        $this->actingAs($customer);

        $response = app(QRCodeController::class)->escanearEmpresa(
            Request::create('/legacy/qrcode/escanear-empresa', 'POST', [
                'code' => $qrCode->code,
            ])
        );

        $payload = $response->getData(true);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertTrue($payload['success']);
        $this->assertTrue($payload['deprecated']['is_legacy']);
        $this->assertNull($payload['data']['bonus_liberado']);
        $this->assertSame(BonusAdesaoResgate::STATUS_AVAILABLE, $payload['data']['bonus_adesao']['status']);
        $this->assertSame($bonus->id, $payload['data']['bonus_adesao']['bonus']['id']);

        $this->assertDatabaseHas('inscricoes_empresa', [
            'user_id' => $customer->id,
            'empresa_id' => $empresa->id,
            'bonus_adesao_resgatado' => false,
        ]);

        $this->assertDatabaseMissing((new BonusAdesaoResgate())->getTable(), [
            'bonus_id' => $bonus->id,
            'user_id' => $customer->id,
        ]);
    }

    public function test_legacy_scan_empresa_rejects_non_public_company_qr(): void
    {
        $customer = User::factory()->create([
            'perfil' => 'cliente',
            'status' => 'ativo',
        ]);

        $companyOwner = User::factory()->create([
            'perfil' => 'empresa',
            'status' => 'pendente',
        ]);

        $empresa = Empresa::factory()->create([
            'owner_id' => $companyOwner->id,
            'ativo' => false,
            'status' => Empresa::STATUS_PENDING,
        ]);

        $qrCode = QRCode::query()->create([
            'empresa_id' => $empresa->id,
            'name' => 'QR bloqueado',
            'code' => QRCode::gerarCodigoUnico($empresa->id),
            'active' => true,
        ]);

        $this->actingAs($customer);

        $response = app(QRCodeController::class)->escanearEmpresa(
            Request::create('/legacy/qrcode/escanear-empresa', 'POST', [
                'code' => $qrCode->code,
            ])
        );

        $payload = $response->getData(true);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertFalse($payload['success']);
        $this->assertTrue($payload['deprecated']['is_legacy']);

        $this->assertDatabaseMissing('inscricoes_empresa', [
            'user_id' => $customer->id,
            'empresa_id' => $empresa->id,
        ]);
    }
}
