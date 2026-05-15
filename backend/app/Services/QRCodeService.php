<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\QRCode;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode as QrCodeGenerator;

class QRCodeService
{
    private function renderQrAsset(string $payload): array
    {
        try {
            $png = QrCodeGenerator::format('png')
                ->size(300)
                ->errorCorrection('H')
                ->margin(2)
                ->generate($payload);

            return [
                'contents' => $png,
                'extension' => 'png',
                'mime' => 'image/png',
            ];
        } catch (\Throwable) {
            $svg = QrCodeGenerator::format('svg')
                ->size(300)
                ->errorCorrection('H')
                ->margin(2)
                ->generate($payload);

            return [
                'contents' => $svg,
                'extension' => 'svg',
                'mime' => 'image/svg+xml',
            ];
        }
    }

    public function getCompanyScanUrl(QRCode $qrCode): string
    {
        return url('/vincular_empresa.html') . '?code=' . rawurlencode((string) $qrCode->code);
    }

    public function getQrPayload(QRCode $qrCode): string
    {
        if ($qrCode->empresa_id) {
            return $this->getCompanyScanUrl($qrCode);
        }

        return (string) $qrCode->code;
    }

    /**
     * Gera ou reaproveita o QR canonical da empresa.
     */
    public function gerarQRCodeEmpresa(Empresa $empresa)
    {
        $qrCodeExistente = QRCode::where('empresa_id', $empresa->id)->first();

        if ($qrCodeExistente) {
            if (!$qrCodeExistente->code) {
                $qrCodeExistente->update([
                    'code' => QRCode::gerarCodigoUnico($empresa->id),
                    'active' => true,
                ]);
            }

            $this->salvarQRCodeNoStorage($qrCodeExistente);

            return $qrCodeExistente->refresh();
        }

        $qrCode = QRCode::create([
            'code' => QRCode::gerarCodigoUnico($empresa->id),
            'name' => 'QR Code Principal',
            'empresa_id' => $empresa->id,
            'active' => true,
        ]);

        $this->salvarQRCodeNoStorage($qrCode);

        return $qrCode->refresh();
    }

    /**
     * Caminho canonico do cliente:
     * QR assinado e temporario via ClienteQrCodeService.
     * Mantemos esse metodo como no-op por compatibilidade.
     */
    public function gerarQRCodeCliente(User $user)
    {
        return null;
    }

    /**
     * Valida QR da empresa persistido na tabela qr_codes.
     */
    public function validarCodigo($code)
    {
        $query = QRCode::where('code', $code);

        if (Schema::hasColumn('qr_codes', 'active')) {
            $query->where('active', true);
        } elseif (Schema::hasColumn('qr_codes', 'ativo')) {
            $query->where('ativo', true);
        }

        $qrCode = $query->first();

        if (!$qrCode) {
            return [
                'valido' => false,
                'mensagem' => 'QR Code invalido ou inativo',
            ];
        }

        return [
            'valido' => true,
            'type' => 'empresa',
            'qr_code' => $qrCode,
            'empresa' => $qrCode->empresa,
            'user' => null,
        ];
    }

    public function getQRCodeImageDataUrl(QRCode $qrCode)
    {
        if ($qrCode->qr_image) {
            return 'data:image/png;base64,' . $qrCode->qr_image;
        }

        if ($qrCode->qr_path && Storage::disk('public')->exists($qrCode->qr_path)) {
            $imageData = Storage::disk('public')->get($qrCode->qr_path);
            $mime = str_ends_with(strtolower((string) $qrCode->qr_path), '.svg')
                ? 'image/svg+xml'
                : 'image/png';

            return 'data:' . $mime . ';base64,' . base64_encode($imageData);
        }

        $payload = $this->getQrPayload($qrCode);
        $asset = $this->renderQrAsset($payload);

        return 'data:' . $asset['mime'] . ';base64,' . base64_encode($asset['contents']);
    }

    public function regenerarQRCode(QRCode $qrCode)
    {
        $qrCode->update([
            'code' => QRCode::gerarCodigoUnico($qrCode->empresa_id ?: null),
        ]);

        $this->salvarQRCodeNoStorage($qrCode->refresh());

        return $qrCode->refresh();
    }

    public function desativarQRCode(QRCode $qrCode)
    {
        $qrCode->update(['active' => false]);

        return $qrCode;
    }

    public function reativarQRCode(QRCode $qrCode)
    {
        $qrCode->update(['active' => true]);

        return $qrCode;
    }

    public function salvarQRCodeNoStorage(QRCode $qrCode)
    {
        $folder = $qrCode->empresa_id ? 'qrcodes/empresas' : 'qrcodes/generic';
        $id = $qrCode->empresa_id ?? $qrCode->id;
        $payload = $this->getQrPayload($qrCode);
        $asset = $this->renderQrAsset($payload);
        $filename = "{$id}_{$qrCode->id}.{$asset['extension']}";
        $path = "{$folder}/{$filename}";

        Storage::disk('public')->put($path, $asset['contents']);

        $qrCode->update([
            'qr_path' => $path,
        ]);

        return $qrCode;
    }

    public function migrarBase64ParaArquivo(QRCode $qrCode)
    {
        if ($qrCode->qr_path && Storage::disk('public')->exists($qrCode->qr_path)) {
            return $qrCode;
        }

        if ($qrCode->qr_image) {
            $folder = $qrCode->empresa_id ? 'qrcodes/empresas' : 'qrcodes/generic';
            $id = $qrCode->empresa_id ?? $qrCode->id;
            $filename = "{$id}_{$qrCode->id}.png";
            $path = "{$folder}/{$filename}";

            $imageData = base64_decode($qrCode->qr_image);
            Storage::disk('public')->put($path, $imageData);

            $qrCode->update([
                'qr_path' => $path,
                'qr_image' => null,
            ]);
        } else {
            $this->salvarQRCodeNoStorage($qrCode);
        }

        return $qrCode;
    }

    public function getQRCodeUrl(QRCode $qrCode)
    {
        if (!$qrCode->qr_path) {
            $this->salvarQRCodeNoStorage($qrCode);
            $qrCode->refresh();
        }

        return Storage::url($qrCode->qr_path);
    }

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
            'erros' => $erros,
        ];
    }
}
