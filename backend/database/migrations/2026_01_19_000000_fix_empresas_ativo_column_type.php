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
        // Verificar se a coluna ativo é do tipo errado
        if (Schema::hasTable('empresas') && Schema::hasColumn('empresas', 'ativo')) {
            // Para PostgreSQL: forçar alteração do tipo para boolean
            if (DB::getDriverName() === 'pgsql') {
                DB::statement('ALTER TABLE empresas ALTER COLUMN ativo TYPE BOOLEAN USING ativo::boolean');
                DB::statement('ALTER TABLE empresas ALTER COLUMN ativo SET DEFAULT true');
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Não faz nada no down
    }
};
