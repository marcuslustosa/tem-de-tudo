<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\QRCodeService;

class MigrateQRCodesToFilesystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'qrcode:migrate-to-filesystem';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migra QR codes de base64 para arquivos PNG no storage/public/qrcodes';

    protected $qrCodeService;

    public function __construct(QRCodeService $qrCodeService)
    {
        parent::__construct();
        $this->qrCodeService = $qrCodeService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Iniciando migração de QR codes para filesystem...');
        $this->newLine();

        $resultado = $this->qrCodeService->migrarTodosQRCodes();

        $this->info("📊 Resultado da migração:");
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Total de QR codes', $resultado['total']],
                ['Migrados com sucesso', $resultado['migrados']],
                ['Erros', $resultado['erros']],
            ]
        );

        if ($resultado['migrados'] > 0) {
            $this->info("✅ {$resultado['migrados']} QR codes foram migrados para storage/public/qrcodes/");
        }

        if ($resultado['erros'] > 0) {
            $this->warn("⚠️ {$resultado['erros']} QR codes falharam. Verifique os logs.");
        }

        if ($resultado['total'] === 0) {
            $this->comment('ℹ️ Nenhum QR code para migrar (todos já estão em arquivos).');
        }

        return Command::SUCCESS;
    }
}
