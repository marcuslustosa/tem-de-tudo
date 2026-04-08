<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecalcularRanking extends Command
{
    protected $signature   = 'ranking:recalcular';
    protected $description = 'Recalcula a posição no ranking de pontos de todos os clientes.';

    public function handle(): int
    {
        $this->info('Recalculando ranking de pontos...');

        try {
            // Buscar todos os clientes ordenados por pontos desc
            $clientes = DB::table('users')
                ->where('perfil', 'cliente')
                ->where(function ($q) {
                    $q->whereNull('deleted_at');
                })
                ->orderByDesc('pontos')
                ->select('id', 'pontos')
                ->get();

            if ($clientes->isEmpty()) {
                $this->info('Nenhum cliente encontrado.');
                return self::SUCCESS;
            }

            // Atribuir posição em lotes
            $updates = [];
            foreach ($clientes as $posicao => $cliente) {
                $updates[] = [
                    'id'              => $cliente->id,
                    'posicao_ranking' => $posicao + 1,
                ];
            }

            // Bulk update usando CASE WHEN para eficiência
            DB::transaction(function () use ($updates) {
                $ids = array_column($updates, 'id');
                $cases = implode(' ', array_map(
                    fn($u) => "WHEN {$u['id']} THEN {$u['posicao_ranking']}",
                    $updates
                ));
                $idList = implode(',', $ids);

                DB::statement("UPDATE users SET posicao_ranking = CASE id {$cases} END WHERE id IN ({$idList})");
            });

            $total = count($updates);
            $this->info("Ranking atualizado para {$total} clientes.");
            Log::info("ranking:recalcular concluído — {$total} clientes atualizados.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Erro ao recalcular ranking: ' . $e->getMessage());
            Log::error('ranking:recalcular falhou', ['error' => $e->getMessage()]);
            return self::FAILURE;
        }
    }
}
