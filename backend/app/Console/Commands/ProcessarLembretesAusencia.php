<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class ProcessarLembretesAusencia extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lembretes:ausencia {--dias=30 : Numero de dias sem check-in para disparar lembrete}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envia lembretes para clientes ausentes ha mais de X dias sem check-in';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dias = (int) $this->option('dias');
        $this->info("Iniciando lembretes de ausencia (>{$dias} dias sem check-in)...");

        $corte = Carbon::now()->subDays($dias);
        $totalEnviados = 0;

        try {
            // Buscar clientes ativos sem check-in recente
            $ausentes = User::where('perfil', 'cliente')
                ->where(function ($q) use ($corte) {
                    $q->whereNull('ultimo_checkin')
                      ->orWhere('ultimo_checkin', '<', $corte);
                })
                ->whereNotNull('email')
                ->limit(200)
                ->get();

            $this->info("Encontrados {$ausentes->count()} clientes ausentes.");

            foreach ($ausentes as $cliente) {
                try {
                    // Enviar e-mail de lembrete (se Mail estiver configurado)
                    if (config('mail.default') && config('mail.mailers.' . config('mail.default') . '.host')) {
                        Mail::raw(
                            "Ola, {$cliente->name}! Sentimos sua falta no Tem de Tudo. " .
                            "Acesse agora e acumule mais pontos nos nossos parceiros!",
                            function ($msg) use ($cliente) {
                                $msg->to($cliente->email)
                                    ->subject('Sentimos sua falta! Volte ao Tem de Tudo');
                            }
                        );
                    }

                    // Log de lembrete enviado
                    Log::info("Lembrete de ausencia enviado", [
                        'user_id' => $cliente->id,
                        'email' => $cliente->email,
                        'ultimo_checkin' => $cliente->ultimo_checkin,
                    ]);

                    $totalEnviados++;
                } catch (\Throwable $e) {
                    Log::warning("Falha ao enviar lembrete para user {$cliente->id}: " . $e->getMessage());
                }
            }

            $this->info("Lembretes enviados: {$totalEnviados}");
            Log::info("Processamento de lembretes de ausencia concluido", ['enviados' => $totalEnviados]);

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Erro ao processar lembretes: " . $e->getMessage());
            Log::error("Erro no processamento de lembretes de ausencia: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
