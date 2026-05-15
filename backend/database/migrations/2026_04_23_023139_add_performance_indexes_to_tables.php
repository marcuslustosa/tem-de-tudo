<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->ensureEmpresaColumns();

        $this->addIndexIfPossible('pontos', 'user_id', 'idx_pontos_user_id');
        $this->addIndexIfPossible('pontos', 'empresa_id', 'idx_pontos_empresa_id');
        $this->addIndexIfPossible('pontos', ['user_id', 'empresa_id'], 'idx_pontos_user_empresa');
        $this->addIndexIfPossible('pontos', 'tipo', 'idx_pontos_tipo');
        $this->addIndexIfPossible('pontos', 'created_at', 'idx_pontos_created_at');

        $this->addIndexIfPossible('empresas', 'ativo', 'idx_empresas_ativo');
        $this->addIndexIfPossible('empresas', 'status', 'idx_empresas_status');
        $this->addIndexIfPossible('empresas', 'categoria', 'idx_empresas_categoria');
        $this->addIndexIfPossible('empresas', ['ativo', 'status'], 'idx_empresas_ativo_status');

        $this->addIndexIfPossible('check_ins', 'empresa_id', 'idx_checkins_empresa_id');
        $this->addIndexIfPossible('check_ins', 'user_id', 'idx_checkins_user_id');
        $this->addIndexIfPossible('check_ins', 'created_at', 'idx_checkins_created_at');
        $this->addIndexIfPossible('check_ins', ['empresa_id', 'created_at'], 'idx_checkins_empresa_date');

        $this->addIndexIfPossible('ledger', 'user_id', 'idx_ledger_user_id');
        $this->addIndexIfPossible('ledger', 'company_id', 'idx_ledger_company_id');
        $this->addIndexIfPossible('ledger', 'transaction_type', 'idx_ledger_type');
        $this->addIndexIfPossible('ledger', 'created_at', 'idx_ledger_created_at');
        $this->addIndexIfPossible('ledger', ['user_id', 'created_at'], 'idx_ledger_user_date');

        $this->addIndexIfPossible('redemption_intents', 'company_id', 'idx_redemption_company_id');
        $this->addIndexIfPossible('redemption_intents', 'user_id', 'idx_redemption_user_id');
        $this->addIndexIfPossible('redemption_intents', 'status', 'idx_redemption_status');
        $this->addIndexIfPossible('redemption_intents', 'expires_at', 'idx_redemption_expires_at');
        $this->addIndexIfPossible('redemption_intents', ['company_id', 'status'], 'idx_redemption_company_status');

        $this->addIndexIfPossible('produtos', 'empresa_id', 'idx_produtos_empresa_id');
        $this->addIndexIfPossible('produtos', 'categoria', 'idx_produtos_categoria');
        $this->addIndexIfPossible('produtos', 'ativo', 'idx_produtos_ativo');
        $this->addIndexIfPossible('produtos', ['empresa_id', 'ativo'], 'idx_produtos_empresa_ativo');

        $this->addIndexIfPossible('promocoes', 'empresa_id', 'idx_promocoes_empresa_id');
        $this->addIndexIfPossible('promocoes', 'ativo', 'idx_promocoes_ativo');
        $this->addIndexIfPossible('promocoes', 'status', 'idx_promocoes_status');
        $this->addIndexIfPossible('promocoes', ['empresa_id', 'ativo', 'status'], 'idx_promocoes_empresa_ativo_status');

        $this->addIndexIfPossible('avaliacoes', 'empresa_id', 'idx_avaliacoes_empresa_id');
        $this->addIndexIfPossible('avaliacoes', 'user_id', 'idx_avaliacoes_user_id');
        $this->addIndexIfPossible('avaliacoes', 'created_at', 'idx_avaliacoes_created_at');

        $this->addIndexIfPossible('badges_usuarios', 'user_id', 'idx_badges_user_id');
        $this->addIndexIfPossible('badges_usuarios', 'badge_id', 'idx_badges_badge_id');
        $this->addIndexIfPossible('badges_usuarios', 'earned_at', 'idx_badges_earned_at');

        $this->addIndexIfPossible('qr_codes', 'empresa_id', 'idx_qr_empresa_id');
        $this->addIndexIfPossible('qr_codes', 'active', 'idx_qr_active');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropIndexIfExists('qr_codes', 'idx_qr_empresa_id');
        $this->dropIndexIfExists('qr_codes', 'idx_qr_active');

        $this->dropIndexIfExists('badges_usuarios', 'idx_badges_user_id');
        $this->dropIndexIfExists('badges_usuarios', 'idx_badges_badge_id');
        $this->dropIndexIfExists('badges_usuarios', 'idx_badges_earned_at');

        $this->dropIndexIfExists('avaliacoes', 'idx_avaliacoes_empresa_id');
        $this->dropIndexIfExists('avaliacoes', 'idx_avaliacoes_user_id');
        $this->dropIndexIfExists('avaliacoes', 'idx_avaliacoes_created_at');

        $this->dropIndexIfExists('promocoes', 'idx_promocoes_empresa_id');
        $this->dropIndexIfExists('promocoes', 'idx_promocoes_ativo');
        $this->dropIndexIfExists('promocoes', 'idx_promocoes_status');
        $this->dropIndexIfExists('promocoes', 'idx_promocoes_empresa_ativo_status');

        $this->dropIndexIfExists('produtos', 'idx_produtos_empresa_id');
        $this->dropIndexIfExists('produtos', 'idx_produtos_categoria');
        $this->dropIndexIfExists('produtos', 'idx_produtos_ativo');
        $this->dropIndexIfExists('produtos', 'idx_produtos_empresa_ativo');

        $this->dropIndexIfExists('redemption_intents', 'idx_redemption_company_id');
        $this->dropIndexIfExists('redemption_intents', 'idx_redemption_user_id');
        $this->dropIndexIfExists('redemption_intents', 'idx_redemption_status');
        $this->dropIndexIfExists('redemption_intents', 'idx_redemption_expires_at');
        $this->dropIndexIfExists('redemption_intents', 'idx_redemption_company_status');

        $this->dropIndexIfExists('ledger', 'idx_ledger_user_id');
        $this->dropIndexIfExists('ledger', 'idx_ledger_company_id');
        $this->dropIndexIfExists('ledger', 'idx_ledger_type');
        $this->dropIndexIfExists('ledger', 'idx_ledger_created_at');
        $this->dropIndexIfExists('ledger', 'idx_ledger_user_date');

        $this->dropIndexIfExists('check_ins', 'idx_checkins_empresa_id');
        $this->dropIndexIfExists('check_ins', 'idx_checkins_user_id');
        $this->dropIndexIfExists('check_ins', 'idx_checkins_created_at');
        $this->dropIndexIfExists('check_ins', 'idx_checkins_empresa_date');

        $this->dropIndexIfExists('empresas', 'idx_empresas_ativo');
        $this->dropIndexIfExists('empresas', 'idx_empresas_status');
        $this->dropIndexIfExists('empresas', 'idx_empresas_categoria');
        $this->dropIndexIfExists('empresas', 'idx_empresas_ativo_status');

        $this->dropIndexIfExists('pontos', 'idx_pontos_user_id');
        $this->dropIndexIfExists('pontos', 'idx_pontos_empresa_id');
        $this->dropIndexIfExists('pontos', 'idx_pontos_user_empresa');
        $this->dropIndexIfExists('pontos', 'idx_pontos_tipo');
        $this->dropIndexIfExists('pontos', 'idx_pontos_created_at');
    }

    private function ensureEmpresaColumns(): void
    {
        if (!Schema::hasTable('empresas')) {
            return;
        }

        $needsCategoria = !Schema::hasColumn('empresas', 'categoria');
        $needsStatus = !Schema::hasColumn('empresas', 'status');

        if ($needsCategoria || $needsStatus) {
            Schema::table('empresas', function (Blueprint $table) use ($needsCategoria, $needsStatus): void {
                if ($needsCategoria) {
                    $table->string('categoria')->nullable();
                }

                if ($needsStatus) {
                    $table->string('status', 30)->default('active');
                }
            });
        }

        if (Schema::hasColumn('empresas', 'categoria') && Schema::hasColumn('empresas', 'ramo')) {
            DB::table('empresas')
                ->where(function ($query): void {
                    $query->whereNull('categoria')
                        ->orWhere('categoria', '');
                })
                ->whereNotNull('ramo')
                ->where('ramo', '!=', '')
                ->update(['categoria' => DB::raw('ramo')]);
        }

        if (Schema::hasColumn('empresas', 'status')) {
            DB::table('empresas')
                ->where(function ($query): void {
                    $query->whereNull('status')
                        ->orWhere('status', '');
                })
                ->update([
                    'status' => DB::raw("CASE WHEN COALESCE(ativo, true) = true THEN 'active' ELSE 'suspended' END"),
                ]);
        }
    }

    private function addIndexIfPossible(string $table, string|array $columns, string $indexName): void
    {
        if (!Schema::hasTable($table) || $this->indexExists($table, $indexName)) {
            return;
        }

        foreach ((array) $columns as $column) {
            if (!Schema::hasColumn($table, $column)) {
                return;
            }
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName): void {
            $blueprint->index($columns, $indexName);
        });
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (!Schema::hasTable($table) || !$this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($indexName): void {
            $blueprint->dropIndex($indexName);
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $driver = DB::getDriverName();

        return match ($driver) {
            'pgsql' => DB::table('pg_indexes')
                ->whereRaw('schemaname = current_schema()')
                ->where('tablename', $table)
                ->where('indexname', $indexName)
                ->exists(),
            'mysql', 'mariadb' => DB::table('information_schema.statistics')
                ->whereRaw('table_schema = database()')
                ->where('table_name', $table)
                ->where('index_name', $indexName)
                ->exists(),
            'sqlite' => collect(DB::select("PRAGMA index_list('{$table}')"))
                ->contains(fn ($row) => ($row->name ?? null) === $indexName),
            default => false,
        };
    }
};
