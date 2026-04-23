<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Migra dados das tabelas antigas (pontos, ponto_transacoes) para o ledger unificado.
     * 
     * Executa em lotes para evitar timeout.
     */
    public function up(): void
    {
        $this->info('Iniciando migração de dados para ledger...');

        // 1. Migrar tabela 'pontos' (sistema antigo)
        if (Schema::hasTable('pontos')) {
            $this->migrateFromPontos();
        }

        // 2. Migrar tabela 'ponto_transacoes' (sistema intermediário)
        if (Schema::hasTable('ponto_transacoes')) {
            $this->migrateFromPontoTransacoes();
        }

        // 3. Recalcular saldos em users.pontos a partir do ledger
        $this->recalculateBalances();

        $this->info('Migração concluída!');
    }

    protected function migrateFromPontos(): void
    {
        $this->info('Migrando tabela "pontos"...');

        DB::table('pontos')
            ->orderBy('id')
            ->chunk(500, function ($transacoes) {
                foreach ($transacoes as $tx) {
                    try {
                        // Calcula saldo anterior
                        $balanceBefore = DB::table('ledger')
                            ->where('user_id', $tx->user_id)
                            ->orderBy('id', 'desc')
                            ->value('balance_after') ?? 0;

                        $points = (int) $tx->pontos;
                        $balanceAfter = $balanceBefore + $points;

                        // Mapeia tipo antigo para novo
                        $transactionType = match ($tx->tipo) {
                            'earn' => 'earn',
                            'bonus' => 'earn_bonus',
                            'redeem' => 'redeem',
                            'adjustment' => 'adjustment',
                            default => 'migration',
                        };

                        DB::table('ledger')->insert([
                            'idempotency_key' => 'migration_pontos_' . $tx->id,
                            'user_id' => $tx->user_id,
                            'company_id' => $tx->empresa_id,
                            'transaction_type' => $transactionType,
                            'points' => $points,
                            'balance_before' => $balanceBefore,
                            'balance_after' => $balanceAfter,
                            'description' => $tx->descricao ?? 'Migração de sistema antigo',
                            'metadata' => json_encode([
                                'migrated_from' => 'pontos',
                                'original_id' => $tx->id,
                                'checkin_id' => $tx->checkin_id,
                                'coupon_id' => $tx->coupon_id,
                            ]),
                            'source' => 'migration',
                            'created_at' => $tx->created_at,
                        ]);
                    } catch (\Exception $e) {
                        $this->error("Erro ao migrar transação pontos #{$tx->id}: " . $e->getMessage());
                    }
                }
            });

        $count = DB::table('ledger')->where('source', 'migration')->count();
        $this->info("✅ Migradas {$count} transações da tabela 'pontos'");
    }

    protected function migrateFromPontoTransacoes(): void
    {
        $this->info('Migrando tabela "ponto_transacoes"...');

        DB::table('ponto_transacoes')
            ->orderBy('id')
            ->chunk(500, function ($transacoes) {
                foreach ($transacoes as $tx) {
                    try {
                        // Verifica se já não migrou
                        $exists = DB::table('ledger')
                            ->where('idempotency_key', 'migration_ponto_transacoes_' . $tx->id)
                            ->exists();

                        if ($exists) {
                            continue; // Pula se já migrou
                        }

                        // Calcula saldo anterior
                        $balanceBefore = DB::table('ledger')
                            ->where('user_id', $tx->user_id)
                            ->orderBy('id', 'desc')
                            ->value('balance_after') ?? 0;

                        $points = (int) $tx->pontos;
                        $balanceAfter = $balanceBefore + $points;

                        // Mapeia tipo
                        $transactionType = match ($tx->tipo) {
                            'adicao' => 'earn',
                            'resgate' => 'redeem',
                            default => 'migration',
                        };

                        DB::table('ledger')->insert([
                            'idempotency_key' => 'migration_ponto_transacoes_' . $tx->id,
                            'user_id' => $tx->user_id,
                            'company_id' => null,
                            'transaction_type' => $transactionType,
                            'points' => $points,
                            'balance_before' => $balanceBefore,
                            'balance_after' => $balanceAfter,
                            'description' => $tx->descricao ?? 'Migração de sistema intermediário',
                            'metadata' => json_encode([
                                'migrated_from' => 'ponto_transacoes',
                                'original_id' => $tx->id,
                                'valor_compra' => $tx->valor_compra,
                                'estabelecimento_id' => $tx->estabelecimento_id,
                            ]),
                            'source' => 'migration',
                            'created_at' => $tx->created_at,
                        ]);
                    } catch (\Exception $e) {
                        $this->error("Erro ao migrar transação ponto_transacoes #{$tx->id}: " . $e->getMessage());
                    }
                }
            });

        $count = DB::table('ledger')
            ->where('metadata->migrated_from', 'ponto_transacoes')
            ->count();
        $this->info("✅ Migradas {$count} transações da tabela 'ponto_transacoes'");
    }

    protected function recalculateBalances(): void
    {
        $this->info('Recalculando saldos em users.pontos...');

        $users = DB::table('users')
            ->whereIn('perfil', ['cliente', 'customer'])
            ->pluck('id');

        foreach ($users as $userId) {
            $balance = DB::table('ledger')
                ->where('user_id', $userId)
                ->orderBy('id', 'desc')
                ->value('balance_after') ?? 0;

            DB::table('users')
                ->where('id', $userId)
                ->update(['pontos' => $balance]);
        }

        $this->info("✅ Saldos recalculados para {$users->count()} usuários");
    }

    protected function info(string $message): void
    {
        echo "[INFO] {$message}\n";
    }

    protected function error(string $message): void
    {
        echo "[ERROR] {$message}\n";
    }

    public function down(): void
    {
        // Não remove dados migrados para segurança
        $this->info('Rollback não remove dados migrados. Use o console se necessário.');
    }
};
