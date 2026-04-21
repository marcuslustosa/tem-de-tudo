<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ClienteQrCodeService;
use Tests\TestCase;

class ClienteQrCodeServiceTest extends TestCase
{
    public function test_gera_e_decodifica_qr_assinado(): void
    {
        $service = app(ClienteQrCodeService::class);

        $user = new User();
        $user->id = 123;
        $user->email = 'cliente@example.com';

        $qrData = $service->gerar($user, 600);
        $decoded = $service->decodificar($qrData['code']);

        $this->assertNotNull($decoded);
        $this->assertSame(123, $decoded['user_id']);
        $this->assertSame('v2', $decoded['version']);
        $this->assertNotEmpty($decoded['expires_at']);
    }

    public function test_rejeita_qr_assinado_tamperado(): void
    {
        $service = app(ClienteQrCodeService::class);

        $user = new User();
        $user->id = 321;
        $user->email = 'cliente2@example.com';

        $qrData = $service->gerar($user, 600);
        $tampered = $qrData['code'];
        $lastIndex = strlen($tampered) - 1;
        $tampered[$lastIndex] = $tampered[$lastIndex] === 'a' ? 'b' : 'a';

        $this->assertNull($service->decodificar($tampered));
    }
}
