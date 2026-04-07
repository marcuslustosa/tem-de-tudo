<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SQLite não suporta ALTER COLUMN direto — recriamos a tabela
        DB::statement('PRAGMA foreign_keys=OFF');

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

        DB::statement('PRAGMA foreign_keys=ON');
    }

    public function down(): void
    {
        // Não revertemos — seria perigoso tornar NOT NULL novamente com dados existentes
    }
};
