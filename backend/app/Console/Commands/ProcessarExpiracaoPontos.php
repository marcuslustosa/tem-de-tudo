<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ProcessarExpiracaoPontos extends Command
{
    protected $signature = 'pontos:expirar';

    protected $description = 'Expira pontos mais antigos que o limite configurado (points_expiration_days)';

    public function handle(): void
    {
        $this->info('⏳ Iniciando expiração de pontos...');

        $diasExpiracao = $this->getDiasExpiracao();
        $this->info("🗓️  Expirar pontos com mais de {$diasExpiracao} dias.");

        $dataLimite = Carbon::now()->subDays($diasExpiracao);

        // Busca pontos não expirados mais antigos que o limite
        $pontosParaExpirar = DB::table('pontos')
            ->where('expirado', false)
            ->where('pontos', '>', 0)
            ->whereIn('tipo', ['checkin', 'bonus_adesao', 'bonus_aniversario', 'bonus_indicacao', 'bonus_indicado', 'compra', 'bonus'])
            ->where('created_at', '<', $dataLimite)
            ->get();

        if ($pontosParaExpirar->isEmpty()) {
            $this->info('✅ Nenhum ponto para expirar hoje.');
            return;
        }

        $this->info("📋 {$pontosParaExpirar->count()} registros de pontos a expirar.");

        $agrupados = $pontosParaExpirar->groupBy('user_id');
        $totalUsuarios = 0;
        $totalPontosExpirados = 0;

        DB::beginTransaction();
        try {
            foreach ($agrupados as $userId => $registros) {
                $somaPontos = $registros->sum('pontos');
                $ids = $registros->pluck('id')->toArray();

                // Marca pontos como expirados
                DB::table('pontos')
                    ->whereIn('id', $ids)
                    ->update(['expirado' => true, 'expired_at' => now()]);

                // Registra evento de expiração (ponto negativo para rastreabilidade)
                DB::table('pontos')->insert([
                    'user_id'    => $userId,
                    'empresa_id' => null,
                    'pontos'     => -$somaPontos,
                    'tipo'       => 'expiracao',
                    'descricao'  => "Expiração automática de {$somaPontos} pontos (prazo de {$diasExpiracao} dias)",
                    'expirado'   => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Deduz do saldo sem ir abaixo de zero
                DB::table('users')
                    ->where('id', $userId)
                    ->update([
                        'pontos' => DB::raw("MAX(0, pontos - {$somaPontos})")
                    ]);

                $totalUsuarios++;
                $totalPontosExpirados += $somaPontos;

                Log::info("Pontos expirados para user_id={$userId}: {$somaPontos} pts");
            }

            DB::commit();

            $this->info("✅ Expiração concluída: {$totalPontosExpirados} pontos expirados para {$totalUsuarios} usuários.");
            Log::info('pontos:expirar concluído', [
                'usuarios' => $totalUsuarios,
                'pontos_expirados' => $totalPontosExpirados,
                'dias_expiracao' => $diasExpiracao,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Erro ao expirar pontos: ' . $e->getMessage());
            Log::error('Erro em pontos:expirar', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }
    }

    private function getDiasExpiracao(): int
    {
        try {
            $disk = Storage::disk('local');
            if ($disk->exists('admin-settings.json')) {
                $settings = json_decode($disk->get('admin-settings.json'), true);
                $dias = (int) ($settings['points_expiration_days'] ?? 365);
                return $dias > 0 ? $dias : 365;
            }
        } catch (\Exception $e) {
            Log::warning('Não foi possível ler admin-settings.json, usando padrão 365 dias.', ['error' => $e->getMessage()]);
        }
        return 365;
    }
}
