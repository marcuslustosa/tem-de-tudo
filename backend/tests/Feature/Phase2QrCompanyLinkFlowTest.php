<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\QRCode;
use App\Models\User;
use App\Services\QRCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase2QrCompanyLinkFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_company_qr_is_available_for_company_and_admin(): void
    {
        Storage::fake('public');

        $companyUser = User::factory()->create([
            'perfil' => 'empresa',
            'status' => 'ativo',
        ]);

        $empresa = Empresa::factory()->create([
            'owner_id' => $companyUser->id,
            'nome' => 'Loja QR Oficial',
            'ativo' => true,
            'status' => Empresa::STATUS_ACTIVE,
        ]);

        $admin = User::factory()->create([
            'perfil' => 'admin',
            'status' => 'ativo',
        ]);

        $companyToken = $companyUser->createToken('phase2-company-qr')->plainTextToken;
        $adminToken = $admin->createToken('phase2-admin-qr')->plainTextToken;

        $companyResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $companyToken,
            'Accept' => 'application/json',
        ])->getJson('/api/empresa/qrcodes');

        $companyResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.empresa_id', $empresa->id)
            ->assertJsonPath('data.0.public_page_url', '/detalhe_do_parceiro.html?id=' . $empresa->id);

        $companyCode = (string) $companyResponse->json('data.0.code');
        $companyScanUrl = (string) $companyResponse->json('data.0.scan_url');

        $this->assertStringStartsWith(QRCode::COMPANY_CODE_PREFIX, $companyCode);
        $this->assertStringContainsString('/vincular_empresa.html?code=', $companyScanUrl);
        $this->assertDatabaseHas('qr_codes', [
            'empresa_id' => $empresa->id,
            'code' => $companyCode,
        ]);

        $adminResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $adminToken,
            'Accept' => 'application/json',
        ])->getJson("/api/admin/empresas/{$empresa->id}/qrcode");

        $adminResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.empresa.id', $empresa->id)
            ->assertJsonPath('data.qr_code.code', $companyCode)
            ->assertJsonPath('data.qr_code.scan_url', $companyScanUrl);
    }

    public function test_customer_can_link_to_active_company_via_public_qr_without_duplicate_link(): void
    {
        Storage::fake('public');

        $cliente = User::factory()->create([
            'perfil' => 'cliente',
            'status' => 'ativo',
        ]);

        $empresa = Empresa::factory()->create([
            'nome' => 'Acai do Centro',
            'categoria' => 'Restaurantes',
            'ramo' => 'restaurante',
            'ativo' => true,
            'status' => Empresa::STATUS_ACTIVE,
            'avaliacao_media' => 4.7,
            'total_avaliacoes' => 19,
        ]);

        /** @var QRCodeService $service */
        $service = app(QRCodeService::class);
        $qrCode = $service->gerarQRCodeEmpresa($empresa);
        $scanUrl = $service->getCompanyScanUrl($qrCode);
        $token = $cliente->createToken('phase2-cliente-link')->plainTextToken;

        $publicLookup = $this->getJson('/api/qrcode/empresa/' . rawurlencode($qrCode->code));
        $publicLookup
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', $qrCode->code)
            ->assertJsonPath('data.empresa.id', $empresa->id)
            ->assertJsonPath('data.empresa.publicamente_visivel', true);

        $firstLink = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->postJson('/api/cliente/vincular-empresa-qrcode', [
            'code' => $scanUrl,
        ]);

        $firstLink
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.empresa.id', $empresa->id)
            ->assertJsonPath('data.empresa.vinculada', true)
            ->assertJsonPath('data.vinculo_criado', true)
            ->assertJsonPath('data.public_page_url', '/detalhe_do_parceiro.html?id=' . $empresa->id);

        $secondLink = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->postJson('/api/cliente/vincular-empresa-qrcode', [
            'code' => $qrCode->code,
        ]);

        $secondLink
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.vinculo_criado', false);

        $this->assertDatabaseHas('inscricoes_empresa', [
            'user_id' => $cliente->id,
            'empresa_id' => $empresa->id,
        ]);
        $this->assertDatabaseCount('inscricoes_empresa', 1);

        $dashboard = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson('/api/cliente/dashboard');

        $dashboard
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.empresas_vinculadas.0.id', $empresa->id)
            ->assertJsonPath('data.empresas_vinculadas.0.vinculada', true)
            ->assertJsonPath('data.acoes_rapidas.ler_qr_empresa_url', '/validar_resgate.html?modo=vinculo-empresa')
            ->assertJsonPath('data.acoes_rapidas.meu_qr_url', '/meus_pontos.html?mostrar=meu-qrcode');
    }

    public function test_customer_cannot_link_to_non_public_company_qr(): void
    {
        Storage::fake('public');

        $cliente = User::factory()->create([
            'perfil' => 'cliente',
            'status' => 'ativo',
        ]);
        $token = $cliente->createToken('phase2-cliente-blocked')->plainTextToken;

        /** @var QRCodeService $service */
        $service = app(QRCodeService::class);

        foreach ([Empresa::STATUS_PENDING, Empresa::STATUS_SUSPENDED, Empresa::STATUS_REJECTED] as $index => $status) {
            $empresa = Empresa::factory()->create([
                'nome' => 'Empresa Bloqueada ' . $index,
                'ativo' => false,
                'status' => $status,
            ]);
            $qrCode = $service->gerarQRCodeEmpresa($empresa);

            $this->getJson('/api/qrcode/empresa/' . rawurlencode($qrCode->code))
                ->assertStatus(404);

            $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ])->postJson('/api/cliente/vincular-empresa-qrcode', [
                'code' => $qrCode->code,
            ])->assertStatus(404);
        }

        $this->assertDatabaseCount('inscricoes_empresa', 0);
    }
}
