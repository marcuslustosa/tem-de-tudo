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
            // Se existe mas não tem arquivo, gerar arquivo
            if (!$qrCodeExistente->qr_path) {
                $this->salvarQRCodeNoStorage($qrCodeExistente);
            }
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

        // Salvar QR Code como arquivo PNG
        $this->salvarQRCodeNoStorage($qrCode);

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
        // Se tem base64 salvo, retorna
        if ($qrCode->qr_image) {
            return 'data:image/png;base64,' . $qrCode->qr_image;
        }

        // Se tem arquivo, lê e converte para base64
        if ($qrCode->qr_path && Storage::disk('public')->exists($qrCode->qr_path)) {
            $imageData = Storage::disk('public')->get($qrCode->qr_path);
            return 'data:image/png;base64,' . base64_encode($imageData);
        }

        // Se não tem nada, gera e retorna
        $qrImage = QrCodeGenerator::format('png')
            ->size(300)
            ->errorCorrection('H')
            ->margin(2)
            ->generate($qrCode->code);

        return 'data:image/png;base64,' . base64_encode($qrImage);
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

    /**
     * Salvar QR Code como arquivo PNG no storage
     */
    public function salvarQRCodeNoStorage(QRCode $qrCode)
    {
        // Determinar o tipo e ID para o caminho
        $folder = $qrCode->empresa_id ? 'qrcodes/empresas' : 'qrcodes/clientes';
        $id = $qrCode->empresa_id ?? $qrCode->user_id ?? $qrCode->id;
        
        // Gerar imagem do QR Code como PNG
        $qrImage = QrCodeGenerator::format('png')
            ->size(300)
            ->errorCorrection('H')
            ->margin(2)
            ->generate($qrCode->code);

        // Salvar no storage público
        $filename = "{$id}_{$qrCode->id}.png";
        $path = "{$folder}/{$filename}";
        
        Storage::disk('public')->put($path, $qrImage);

        // Atualizar registro com o caminho
        $qrCode->update([
            'qr_path' => $path
        ]);

        return $qrCode;
    }

    /**
     * Migrar QR Code de base64 para arquivo (se necessário)
     */
    public function migrarBase64ParaArquivo(QRCode $qrCode)
    {
        // Se já tem arquivo, não faz nada
        if ($qrCode->qr_path && Storage::disk('public')->exists($qrCode->qr_path)) {
            return $qrCode;
        }

        // Se tem base64, converte para arquivo
        if ($qrCode->qr_image) {
            $folder = $qrCode->empresa_id ? 'qrcodes/empresas' : 'qrcodes/clientes';
            $id = $qrCode->empresa_id ?? $qrCode->user_id ?? $qrCode->id;
            $filename = "{$id}_{$qrCode->id}.png";
            $path = "{$folder}/{$filename}";

            // Decodificar base64 e salvar
            $imageData = base64_decode($qrCode->qr_image);
            Storage::disk('public')->put($path, $imageData);

            // Atualizar registro
            $qrCode->update([
                'qr_path' => $path,
                'qr_image' => null // Limpar base64 para economizar espaço
            ]);
        } else {
            // Gerar do zero
            $this->salvarQRCodeNoStorage($qrCode);
        }

        return $qrCode;
    }

    /**
     * Obter URL pública do QR Code
     */
    public function getQRCodeUrl(QRCode $qrCode)
    {
        // Garantir que existe arquivo
        if (!$qrCode->qr_path) {
            $this->salvarQRCodeNoStorage($qrCode);
            $qrCode->refresh();
        }

        return Storage::url($qrCode->qr_path);
    }

    /**
     * Migrar todos os QR codes existentes de base64 para arquivos
     */
    public function migrarTodosQRCodes()
    {
        $qrCodes = QRCode::whereNull('qr_path')->get();
        $migrados = 0;
        $erros = 0;

        foreach ($qrCodes as $qrCode) {
            try {
                $this->migrarBase64ParaArquivo($qrCode);
                $migrados++;
            } catch (\Exception $e) {
                $erros++;
                \Log::error("Erro ao migrar QR Code {$qrCode->id}: " . $e->getMessage());
            }
        }

        return [
            'total' => $qrCodes->count(),
            'migrados' => $migrados,
            'erros' => $erros
        ];
    }
}
