<?php

namespace App\Console\Commands;

use App\Mail\PointsExpirationWarningMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class NotificarExpiracaoPontos extends Command
{
    protected $signature = 'pontos:notificar-expiracao';

    protected $description = 'Notifica usuários sobre pontos que estão prestes a expirar (7 dias antes)';

    public function handle(): void
    {
        $this->info('⏳ Iniciando notificação de expiração de pontos...');

        $diasExpiracao = $this->getDiasExpiracao();
        $diasAviso = 7; // Avisar 7 dias antes

        $dataLimiteExpiracao = Carbon::now()->addDays($diasAviso);
        $dataLimiteInferior = Carbon::now()->subDays($diasExpiracao)->addDays($diasAviso);
        $dataLimiteSuperior = Carbon::now()->subDays($diasExpiracao)->addDays($diasAviso + 1);

        $this->info("📅 Buscando pontos que expiram em {$diasAviso} dias...");

        // Busca pontos que expirarão nos próximos 7 dias
        $pontosExpirando = DB::table('pontos')
            ->select('user_id', DB::raw('SUM(pontos) as total_expirando'))
            ->where('expirado', false)
            ->where('pontos', '>', 0)
            ->whereIn('tipo', ['checkin', 'bonus_adesao', 'bonus_aniversario', 'bonus_indicacao', 'bonus_indicado', 'compra', 'bonus'])
            ->whereBetween('created_at', [$dataLimiteInferior, $dataLimiteSuperior])
            ->groupBy('user_id')
            ->having('total_expirando', '>', 0)
            ->get();

        if ($pontosExpirando->isEmpty()) {
            $this->info('✅ Nenhum ponto expirando nos próximos 7 dias.');
            return;
        }

        $this->info("📋 {$pontosExpirando->count()} usuários com pontos expirando.");

        $totalNotificados = 0;
        $totalErros = 0;

        foreach ($pontosExpirando as $item) {
            try {
                $user = User::find($item->user_id);

                if (!$user || !$user->email) {
                    continue;
                }

                // Verifica se já enviou notificação recentemente (evita spam)
                $notificacaoRecente = DB::table('notifications')
                    ->where('user_id', $user->id)
                    ->where('type', 'points_expiration_warning')
                    ->where('created_at', '>', Carbon::now()->subDays(2))
                    ->exists();

                if ($notificacaoRecente) {
                    $this->info("⏭️  Notificação já enviada recentemente para user_id={$user->id}");
                    continue;
                }

                // Envia email
                Mail::to($user->email)->send(new PointsExpirationWarningMail(
                    user: $user,
                    pontosExpirando: (int) $item->total_expirando,
                    dataExpiracao: Carbon::now()->addDays($diasAviso)->format('d/m/Y'),
                    diasRestantes: $diasAviso
                ));

                // Registra notificação
                DB::table('notifications')->insert([
                    'user_id' => $user->id,
                    'type' => 'points_expiration_warning',
                    'title' => 'Pontos expirando',
                    'message' => "{$item->total_expirando} pontos expirarão em {$diasAviso} dias",
                    'data' => json_encode([
                        'pontos_expirando' => $item->total_expirando,
                        'dias_restantes' => $diasAviso,
                        'data_expiracao' => Carbon::now()->addDays($diasAviso)->toDateString(),
                    ]),
                    'read' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $totalNotificados++;
                $this->info("✅ Notificado user_id={$user->id} ({$user->email}): {$item->total_expirando} pontos");

            } catch (\Exception $e) {
                $totalErros++;
                $this->error("❌ Erro ao notificar user_id={$item->user_id}: {$e->getMessage()}");
                Log::error('Erro em pontos:notificar-expiracao', [
                    'user_id' => $item->user_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        $this->info("✅ Notificações concluídas: {$totalNotificados} enviadas, {$totalErros} erros.");
        Log::info('pontos:notificar-expiracao concluído', [
            'notificados' => $totalNotificados,
            'erros' => $totalErros,
            'dias_aviso' => $diasAviso,
        ]);
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
