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
        if (!Schema::hasTable('pontos') || !Schema::hasColumn('pontos', 'empresa_id')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE pontos ALTER COLUMN empresa_id DROP NOT NULL');

            return;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE pontos MODIFY empresa_id BIGINT UNSIGNED NULL');

            return;
        }

        if ($driver !== 'sqlite') {
            Schema::table('pontos', function (Blueprint $table): void {
                $table->unsignedBigInteger('empresa_id')->nullable()->change();
            });

            return;
        }

        DB::statement('PRAGMA foreign_keys=OFF');

        try {
            DB::statement('CREATE TABLE pontos_new (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                user_id INTEGER NOT NULL,
                empresa_id INTEGER NULL,
                checkin_id INTEGER NULL,
                coupon_id INTEGER NULL,
                pontos INTEGER NOT NULL,
                descricao TEXT NOT NULL,
                tipo TEXT NOT NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL
            )');

            DB::statement('INSERT INTO pontos_new SELECT id, user_id, empresa_id, checkin_id, coupon_id, pontos, descricao, tipo, created_at, updated_at FROM pontos');
            DB::statement('DROP TABLE pontos');
            DB::statement('ALTER TABLE pontos_new RENAME TO pontos');
        } finally {
            DB::statement('PRAGMA foreign_keys=ON');
        }
    }

    public function down(): void
    {
        // Nao revertemos: voltar a NOT NULL poderia falhar com dados existentes.
    }
};
