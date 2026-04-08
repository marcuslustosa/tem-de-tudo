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
        $qrCodeExistente = QRCode::where('empresa_id', $empresa->id)->first();

        if ($qrCodeExistente) {
            return $qrCodeExistente;
        }

        // Gerar código único (método estático recebe apenas $empresaId)
        $code = QRCode::gerarCodigoUnico($empresa->id);

        // Criar registro no banco com campos corretos da tabela
        $qrCode = QRCode::create([
            'code' => $code,
            'name' => 'QR Code Principal',
            'empresa_id' => $empresa->id,
            'active' => true,
        ]);

        return $qrCode;
    }

    /**
     * Gerar QR Code para Cliente
     */
    public function gerarQRCodeCliente(User $user)
    {
        // QR Code de cliente é gerado inline em ClienteAPIController::meuQRCode()
        // A tabela qr_codes não possui coluna user_id, apenas empresa_id
        // Retorna null silenciosamente para não quebrar o registro
        return null;
    }

    /**
     * Validar código do QR Code
     */
    public function validarCodigo($code)
    {
        $qrCode = QRCode::where('code', $code)
            ->where('active', true)
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

        // Atualizar registro com novo código
        $qrCode->update(['code' => $newCode]);

        return $qrCode;
    }

    /**
     * Desativar QR Code
     */
    public function desativarQRCode(QRCode $qrCode)
    {
        $qrCode->update(['active' => false]);
        return $qrCode;
    }

    /**
     * Reativar QR Code
     */
    public function reativarQRCode(QRCode $qrCode)
    {
        $qrCode->update(['active' => true]);
        return $qrCode;
    }
}
