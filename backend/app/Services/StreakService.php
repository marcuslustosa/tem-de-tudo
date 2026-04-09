<?php

namespace App\Services;

use App\Models\User;
use App\Models\Ponto;
use Carbon\Carbon;

class StreakService
{
    /**
     * Processa o streak do usuário ao fazer check-in.
     * Retorna array com info do streak atualizado.
     */
    public function processar(User $user): array
    {
        $hoje        = Carbon::today();
        $ontem       = Carbon::yesterday();
        $ultimoCheckin = $user->ultimo_checkin ? Carbon::parse($user->ultimo_checkin) : null;

        // Já atualizou hoje → nada a fazer
        if ($ultimoCheckin && $ultimoCheckin->isSameDay($hoje)) {
            return [
                'streak_atual'  => $user->streak_atual,
                'streak_maximo' => $user->streak_maximo,
                'bonus_pontos'  => 0,
                'novo_recorde'  => false,
            ];
        }

        $eraConsecutivo = $ultimoCheckin && $ultimoCheckin->isSameDay($ontem);
        $streakAnterior = $user->streak_atual;

        if ($eraConsecutivo) {
            $user->streak_atual += 1;
        } else {
            $user->streak_atual = 1; // reinicia
        }

        $novoRecorde = false;
        if ($user->streak_atual > $user->streak_maximo) {
            $user->streak_maximo = $user->streak_atual;
            $novoRecorde = true;
        }

        $user->ultimo_checkin = $hoje;
        $user->save();

        // Bônus de streak a cada múltiplo de 7 dias
        $bonusPontos = 0;
        if ($user->streak_atual % 7 === 0) {
            $bonusPontos = 50 * intdiv($user->streak_atual, 7); // progressivo
            $user->increment('pontos', $bonusPontos);
            $user->increment('pontos_lifetime', $bonusPontos);

            Ponto::create([
                'user_id'  => $user->id,
                'pontos'   => $bonusPontos,
                'tipo'     => 'bonus_streak',
                'descricao' => "Bônus de sequência: {$user->streak_atual} dias seguidos! 🔥",
                'data'     => now(),
            ]);
        }

        // Avança progresso de desafios do tipo streak
        $this->avancarDesafioStreak($user);

        return [
            'streak_atual'  => $user->streak_atual,
            'streak_maximo' => $user->streak_maximo,
            'bonus_pontos'  => $bonusPontos,
            'novo_recorde'  => $novoRecorde,
        ];
    }

    private function avancarDesafioStreak(User $user): void
    {
        $desafios = \App\Models\Desafio::ativos()
            ->where(fn ($q) => $q->whereNull('empresa_id'))
            ->where('tipo', 'streak')
            ->get();

        foreach ($desafios as $desafio) {
            $progresso = \App\Models\DesafioProgresso::firstOrCreate(
                ['user_id' => $user->id, 'desafio_id' => $desafio->id],
                ['progresso_atual' => 0, 'concluido' => false]
            );

            if ($progresso->concluido) continue;

            $progresso->progresso_atual = $user->streak_atual;

            if ($progresso->progresso_atual >= $desafio->meta) {
                $progresso->concluido    = true;
                $progresso->concluido_em = now();

                if (!$progresso->recompensa_dada && $desafio->recompensa_pontos > 0) {
                    $user->increment('pontos', $desafio->recompensa_pontos);
                    $user->increment('pontos_lifetime', $desafio->recompensa_pontos);

                    Ponto::create([
                        'user_id'  => $user->id,
                        'pontos'   => $desafio->recompensa_pontos,
                        'tipo'     => 'bonus_desafio',
                        'descricao' => "Desafio concluído: {$desafio->nome} 🏆",
                        'data'     => now(),
                    ]);

                    $progresso->recompensa_dada = true;
                }
            }

            $progresso->save();
        }
    }
}
