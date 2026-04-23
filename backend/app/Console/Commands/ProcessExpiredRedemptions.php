<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RedemptionService;

class ProcessExpiredRedemptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'redemptions:process-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Processa reservas de resgate expiradas e libera os pontos';

    protected $redemptionService;

    public function __construct(RedemptionService $redemptionService)
    {
        parent::__construct();
        $this->redemptionService = $redemptionService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Processando reservas expiradas...');

        $result = $this->redemptionService->processExpiredReservations();

        $this->info("✅ Processamento concluído:");
        $this->info("   - Total expiradas: {$result['total_expired']}");
        $this->info("   - Processadas: {$result['processed']}");

        if ($result['total_expired'] > 0) {
            $this->warn("⚠️  {$result['total_expired']} reservas foram expiradas e liberadas");
        }

        return 0;
    }
}
