<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\SendWebPushJob;

class AvaliarNivelAnual extends Command
{
    protected $signature   = 'nivel:avaliar-anual';
    protected $description = 'Avalia anualmente se clientes devem ter o nível rebaixado com base nos pontos do último ano.';

    // Pontos mínimos por nível: [nível_id => mínimo para manter]
    private const LIMITES = [
        4 => 5000, // Platina
        3 => 1500, // Ouro
        2 => 500,  // Prata
        1 => 0,    // Bronze (nunca cai abaixo)
    ];

    private const NIVEL_NOME = [1 => 'Bronze', 2 => 'Prata', 3 => 'Ouro', 4 => 'Platina'];
    private const NIVEL_MULT = [1 => 1.0, 2 => 1.1, 3 => 1.2, 4 => 1.3];

    public function handle(): int
    {
        $this->info('Avaliação anual de nível iniciada...');

        try {
            $desde = now()->subDays(365);
            $rebaixados = 0;

            // Buscar todos os clientes com nível > Bronze
            $clientes = DB::table('users')
                ->where('perfil', 'cliente')
                ->where('nivel', '>', 1)
                ->whereNull('deleted_at')
                ->select('id', 'nivel', 'name')
                ->get();

            foreach ($clientes as $cliente) {
                // Somar pontos ganhos nos últimos 365 dias (exclui resgates negativos)
                $pontosAno = (int) DB::table('pontos')
                    ->where('user_id', $cliente->id)
                    ->where('created_at', '>=', $desde)
                    ->where('pontos', '>', 0)
                    ->sum('pontos');

                // Calcular nível que o cliente merece com base nos pontos do ano
                $nivelMerecido = 1;
                foreach (self::LIMITES as $nivelId => $minimo) {
                    if ($pontosAno >= $minimo) {
                        $nivelMerecido = $nivelId;
                        break;
                    }
                }

                // Rebaixar somente se necessário
                if ($nivelMerecido < $cliente->nivel) {
                    DB::table('users')->where('id', $cliente->id)->update([
                        'nivel'                => $nivelMerecido,
                        'multiplicador_pontos' => self::NIVEL_MULT[$nivelMerecido],
                        'updated_at'           => now(),
                    ]);

                    $nomeAnterior = self::NIVEL_NOME[$cliente->nivel] ?? 'Desconhecido';
                    $nomeNovo    = self::NIVEL_NOME[$nivelMerecido]   ?? 'Bronze';

                    // Notificação push ao cliente
                    SendWebPushJob::dispatch(
                        title: 'Atualização de Nível',
                        body: "Seu nível foi ajustado de {$nomeAnterior} para {$nomeNovo} com base na sua atividade do último ano. Continue acumulando pontos!",
                        data: ['type' => 'nivel_downgrade', 'novo_nivel' => $nomeNovo, 'url' => '/meus_pontos.html'],
                        userIds: [$cliente->id]
                    );

                    $this->line("  [{$cliente->name}] {$nomeAnterior} → {$nomeNovo} ({$pontosAno} pts/ano)");
                    $rebaixados++;
                }
            }

            $total = $clientes->count();
            $this->info("Avaliação concluída. {$total} clientes verificados, {$rebaixados} rebaixados.");
            Log::info("nivel:avaliar-anual concluído — {$total} verificados, {$rebaixados} rebaixados.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Erro na avaliação anual: ' . $e->getMessage());
            Log::error('nivel:avaliar-anual falhou', ['error' => $e->getMessage()]);
            return self::FAILURE;
        }
    }
}
