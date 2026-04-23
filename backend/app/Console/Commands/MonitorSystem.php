<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MonitorSystem extends Command
{
    protected $signature = 'monitor:system';
    protected $description = 'Monitora sistema e envia alertas se necessário';

    public function handle()
    {
        $this->info('🔍 Monitorando sistema...');
        
        $alerts = [];
        
        // 1. Verificar database
        try {
            $dbLatency = $this->measureLatency(fn() => DB::select('SELECT 1'));
            if ($dbLatency > 100) {
                $alerts[] = "⚠️ Database lento: {$dbLatency}ms";
                Log::warning('Database latency high', ['latency_ms' => $dbLatency]);
            }
        } catch (\Exception $e) {
            $alerts[] = "❌ Database offline: {$e->getMessage()}";
            Log::critical('Database connection failed', ['error' => $e->getMessage()]);
        }
        
        // 2. Verificar disk space
        $diskFree = disk_free_space(storage_path());
        $diskTotal = disk_total_space(storage_path());
        $diskUsage = (($diskTotal - $diskFree) / $diskTotal) * 100;
        
        if ($diskUsage > 90) {
            $alerts[] = "⚠️ Disco quase cheio: " . round($diskUsage, 1) . "%";
            Log::warning('Disk space low', ['usage_percent' => $diskUsage]);
        }
        
        // 3. Verificar reservas expiradas
        $expiredReservations = DB::table('redemption_intents')
            ->where('status', 'reserved')
            ->where('expires_at', '<', now())
            ->count();
        
        if ($expiredReservations > 10) {
            $alerts[] = "⚠️ {$expiredReservations} reservas expiradas pendentes";
            Log::warning('Too many expired reservations', ['count' => $expiredReservations]);
        }
        
        // 4. Verificar alertas de fraude pendentes
        $pendingFraud = DB::table('fraud_alerts')
            ->where('status', 'pending')
            ->count();
        
        if ($pendingFraud > 20) {
            $alerts[] = "⚠️ {$pendingFraud} alertas de fraude pendentes";
            Log::warning('Too many pending fraud alerts', ['count' => $pendingFraud]);
        }
        
        // 5. Verificar faturas vencidas
        $overdueInvoices = DB::table('invoices')
            ->where('status', 'overdue')
            ->count();
        
        if ($overdueInvoices > 0) {
            $this->info("ℹ️  {$overdueInvoices} faturas vencidas");
        }
        
        // 6. Verificar subscriptions suspended
        $suspendedSubs = DB::table('subscriptions')
            ->where('status', 'suspended')
            ->count();
        
        if ($suspendedSubs > 5) {
            $alerts[] = "⚠️ {$suspendedSubs} assinaturas suspensas";
            Log::warning('Multiple suspended subscriptions', ['count' => $suspendedSubs]);
        }
        
        // Resumo
        if (empty($alerts)) {
            $this->info('✅ Sistema saudável - nenhum alerta');
        } else {
            $this->warn('⚠️ Alertas encontrados:');
            foreach ($alerts as $alert) {
                $this->warn("  {$alert}");
            }
        }
        
        return 0;
    }
    
    private function measureLatency(callable $callback): float
    {
        $start = microtime(true);
        $callback();
        return round((microtime(true) - $start) * 1000, 2);
    }
}
