<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Services\QRCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CompanyQrCodeServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_qr_code_generation_uses_opaque_token_and_persists_png(): void
    {
        Storage::fake('public');

        $empresa = Empresa::factory()->create([
            'ativo' => true,
            'status' => Empresa::STATUS_ACTIVE,
        ]);

        $service = app(QRCodeService::class);
        $qrCode = $service->gerarQRCodeEmpresa($empresa);

        $this->assertNotNull($qrCode->id);
        // Codigo curto e opaco (formato novo: EMP + aleatorio). Nao revela o id.
        $this->assertMatchesRegularExpression('/^EMP[A-Z0-9]+$/', $qrCode->code);
        $this->assertDoesNotMatchRegularExpression('/^QR-\\d+-/i', $qrCode->code);
        $this->assertNotEmpty($qrCode->qr_path);

        Storage::disk('public')->assertExists($qrCode->qr_path);

        $sameQrCode = $service->gerarQRCodeEmpresa($empresa);
        $this->assertSame($qrCode->id, $sameQrCode->id);
        $this->assertSame($qrCode->code, $sameQrCode->code);
    }
}
