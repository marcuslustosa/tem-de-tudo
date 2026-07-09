<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use App\Models\QRCode;
use App\Services\QRCodeService;

/**
 * Encurta os codigos QR gigantes ja existentes (prefixos antigos
 * COMPANY_V1_ / CLIENT_LEGACY_ com 40 chars) para o formato curto novo,
 * regenerando tambem a imagem. Roda uma vez. A prova de falha (por registro).
 */
return new class extends Migration
{
    public function up(): void
    {
        try {
            if (!Schema::hasTable('qr_codes')) {
                return;
            }

            $service = app(QRCodeService::class);

            QRCode::query()
                ->where(function ($q) {
                    $q->where('code', 'like', 'COMPANY_V1_%')
                        ->orWhere('code', 'like', 'CLIENT_LEGACY_%');
                })
                ->orderBy('id')
                ->chunkById(100, function ($codes) use ($service) {
                    foreach ($codes as $qrCode) {
                        try {
                            $service->regenerarQRCode($qrCode);
                        } catch (\Throwable $e) {
                            Log::warning('shorten_existing_qr_codes: falha em 1 registro', [
                                'id' => $qrCode->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                });
        } catch (\Throwable $e) {
            Log::warning('shorten_existing_qr_codes falhou: ' . $e->getMessage());
        }
    }

    public function down(): void
    {
        // Sem reversao: nao ha como recuperar os codigos antigos.
    }
};
