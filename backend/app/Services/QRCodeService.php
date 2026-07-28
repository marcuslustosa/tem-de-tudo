<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\QRCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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
        $activeValue = $this->databaseBooleanValue(true);
        $qrCodeExistente = QRCode::where('empresa_id', $empresa->id)->first();

        if ($qrCodeExistente) {
            if (!$qrCodeExistente->code) {
                $qrCodeExistente->update([
                    'code' => QRCode::gerarCodigoUnico($empresa->id),
                    'active' => $activeValue,
                ]);
            }

            $this->salvarQRCodeNoStorage($qrCodeExistente);

            return $qrCodeExistente->refresh();
        }

        $qrCode = QRCode::create([
            'code' => QRCode::gerarCodigoUnico($empresa->id),
            'name' => 'QR Code Principal',
            'empresa_id' => $empresa->id,
            'active' => $activeValue,
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
            if ($this->isBooleanColumn('qr_codes', 'active') && DB::connection()->getDriverName() === 'pgsql') {
                $query->whereRaw('active = true');
            } else {
                $query->where('active', $this->databaseBooleanValue(true));
            }
        } elseif (Schema::hasColumn('qr_codes', 'ativo')) {
            if ($this->isBooleanColumn('qr_codes', 'ativo') && DB::connection()->getDriverName() === 'pgsql') {
                $query->whereRaw('ativo = true');
            } else {
                $query->where('ativo', $this->databaseBooleanValue(true));
            }
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

    /**
     * Caminho esperado do arquivo para o conteudo ATUAL do QR.
     *
     * O nome carrega um resumo do payload de proposito: o QR da empresa aponta
     * para uma URL montada com APP_URL, e a imagem ficava gravada em disco para
     * sempre. Se o dominio do app mudasse, o adesivo que ja esta colado na mesa
     * continuava mandando o cliente para o host antigo, sem nunca ser regerado.
     * Com o resumo no nome, mudou o destino, mudou o arquivo.
     */
    private function caminhoEsperadoDoArquivo(QRCode $qrCode, string $payload, string $extension): string
    {
        $folder = $qrCode->empresa_id ? 'qrcodes/empresas' : 'qrcodes/generic';
        $id = $qrCode->empresa_id ?? $qrCode->id;
        $resumo = substr(sha1($payload), 0, 10);

        return "{$folder}/{$id}_{$qrCode->id}_{$resumo}.{$extension}";
    }

    public function getQRCodeImageDataUrl(QRCode $qrCode)
    {
        $payload = $this->getQrPayload($qrCode);
        $asset = $this->renderQrAsset($payload);
        $esperado = $this->caminhoEsperadoDoArquivo($qrCode, $payload, $asset['extension']);

        // So reaproveita o arquivo em disco se ele foi gerado para este mesmo
        // destino. Caso contrario regrava, para o QR nunca ficar desatualizado.
        if ($qrCode->qr_path === $esperado && Storage::disk('public')->exists($esperado)) {
            $imageData = Storage::disk('public')->get($esperado);
            $mime = str_ends_with(strtolower($esperado), '.svg') ? 'image/svg+xml' : 'image/png';

            return 'data:' . $mime . ';base64,' . base64_encode($imageData);
        }

        $this->salvarQRCodeNoStorage($qrCode);

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
        $qrCode->update(['active' => $this->databaseBooleanValue(false)]);

        return $qrCode;
    }

    public function reativarQRCode(QRCode $qrCode)
    {
        $qrCode->update(['active' => $this->databaseBooleanValue(true)]);

        return $qrCode;
    }

    public function salvarQRCodeNoStorage(QRCode $qrCode)
    {
        $payload = $this->getQrPayload($qrCode);
        $asset = $this->renderQrAsset($payload);
        $path = $this->caminhoEsperadoDoArquivo($qrCode, $payload, $asset['extension']);

        Storage::disk('public')->put($path, $asset['contents']);

        // A coluna base64 `qr_image` e do formato antigo e nem existe em toda
        // base. Nao mexemos nela: o getter simplesmente deixou de consultar,
        // entao um base64 velho nao tem mais como devolver o destino errado.
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

    private function databaseBooleanValue(bool $value): bool|string
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            return $value ? 'true' : 'false';
        }

        return $value;
    }

    private function isBooleanColumn(string $table, string $column): bool
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return false;
        }

        try {
            $type = strtolower((string) Schema::getColumnType($table, $column));

            return in_array($type, ['bool', 'boolean'], true);
        } catch (\Throwable) {
            return false;
        }
    }
}
