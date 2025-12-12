<?php

namespace App\Services;

use App\Models\QRCode;
use App\Models\Empresa;
use App\Models\User;
use SimpleSoftwareIO\QrCode\Facades\QrCode as QrCodeGenerator;
use Illuminate\Support\Facades\Storage;

class QRCodeService
{
    /**
     * Gerar QR Code para Empresa
     */
    public function gerarQRCodeEmpresa(Empresa $empresa)
    {
        // Verificar se empresa já tem QR Code
        $qrCodeExistente = QRCode::where('empresa_id', $empresa->id)
            ->where('type', 'empresa')
            ->first();

        if ($qrCodeExistente) {
            return $qrCodeExistente;
        }

        // Gerar código único
        $code = QRCode::gerarCodigoUnico('empresa', $empresa->id);

        // Gerar imagem do QR Code (formato PNG base64)
        $qrImage = base64_encode(QrCodeGenerator::format('png')
            ->size(500)
            ->errorCorrection('H')
            ->generate($code));

        // Criar registro no banco
        $qrCode = QRCode::create([
            'code' => $code,
            'type' => 'empresa',
            'empresa_id' => $empresa->id,
            'user_id' => null,
            'qr_image' => $qrImage,
            'ativo' => true
        ]);

        return $qrCode;
    }

    /**
     * Gerar QR Code para Cliente
     */
    public function gerarQRCodeCliente(User $user)
    {
        // Verificar se cliente já tem QR Code
        $qrCodeExistente = QRCode::where('user_id', $user->id)
            ->where('type', 'cliente')
            ->first();

        if ($qrCodeExistente) {
            return $qrCodeExistente;
        }

        // Gerar código único
        $code = QRCode::gerarCodigoUnico('cliente', $user->id);

        // Gerar imagem do QR Code (formato PNG base64)
        $qrImage = base64_encode(QrCodeGenerator::format('png')
            ->size(400)
            ->errorCorrection('H')
            ->generate($code));

        // Criar registro no banco
        $qrCode = QRCode::create([
            'code' => $code,
            'type' => 'cliente',
            'empresa_id' => null,
            'user_id' => $user->id,
            'qr_image' => $qrImage,
            'ativo' => true
        ]);

        return $qrCode;
    }

    /**
     * Validar código do QR Code
     */
    public function validarCodigo($code)
    {
        $qrCode = QRCode::where('code', $code)
            ->where('ativo', true)
            ->first();

        if (!$qrCode) {
            return [
                'valido' => false,
                'mensagem' => 'QR Code inválido ou inativo'
            ];
        }

        // Retornar informações do QR Code
        return [
            'valido' => true,
            'type' => $qrCode->type,
            'qr_code' => $qrCode,
            'empresa' => $qrCode->empresa,
            'user' => $qrCode->user
        ];
    }

    /**
     * Obter imagem do QR Code como Data URL
     */
    public function getQRCodeImageDataUrl(QRCode $qrCode)
    {
        return 'data:image/png;base64,' . $qrCode->qr_image;
    }

    /**
     * Regenerar QR Code (caso necessário)
     */
    public function regenerarQRCode(QRCode $qrCode)
    {
        // Gerar novo código
        if ($qrCode->type === 'empresa') {
            $newCode = QRCode::gerarCodigoUnico('empresa', $qrCode->empresa_id);
        } else {
            $newCode = QRCode::gerarCodigoUnico('cliente', $qrCode->user_id);
        }

        // Gerar nova imagem
        $qrImage = base64_encode(QrCodeGenerator::format('png')
            ->size(500)
            ->errorCorrection('H')
            ->generate($newCode));

        // Atualizar registro
        $qrCode->update([
            'code' => $newCode,
            'qr_image' => $qrImage
        ]);

        return $qrCode;
    }

    /**
     * Desativar QR Code
     */
    public function desativarQRCode(QRCode $qrCode)
    {
        $qrCode->update(['ativo' => false]);
        return $qrCode;
    }

    /**
     * Reativar QR Code
     */
    public function reativarQRCode(QRCode $qrCode)
    {
        $qrCode->update(['ativo' => true]);
        return $qrCode;
    }
}
