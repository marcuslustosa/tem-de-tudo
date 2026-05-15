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
        $this->ensureEmpresaStatusColumn();

        $this->addIndexIfPossible('pontos', 'user_id', 'pontos_user_id_index');
        $this->addIndexIfPossible('pontos', 'empresa_id', 'pontos_empresa_id_index');
        $this->addIndexIfPossible('pontos', 'created_at', 'pontos_created_at_index');

        $this->addIndexIfPossible('empresas', 'status', 'empresas_status_index');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropIndexIfExists('empresas', 'empresas_status_index');

        $this->dropIndexIfExists('pontos', 'pontos_created_at_index');
        $this->dropIndexIfExists('pontos', 'pontos_empresa_id_index');
        $this->dropIndexIfExists('pontos', 'pontos_user_id_index');
    }

    private function ensureEmpresaStatusColumn(): void
    {
        if (!Schema::hasTable('empresas')) {
            return;
        }

        if (!Schema::hasColumn('empresas', 'status')) {
            Schema::table('empresas', function (Blueprint $table): void {
                $table->string('status', 30)->default('active');
            });
        }

        DB::table('empresas')
            ->where(function ($query): void {
                $query->whereNull('status')
                    ->orWhere('status', '');
            })
            ->update([
                'status' => DB::raw("CASE WHEN COALESCE(ativo, true) = true THEN 'active' ELSE 'suspended' END"),
            ]);
    }

    private function addIndexIfPossible(string $table, string|array $columns, string $indexName): void
    {
        if (!Schema::hasTable($table) || $this->indexExists($table, $indexName)) {
            return;
        }

        $requiredColumns = (array) $columns;
        foreach ($requiredColumns as $column) {
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
