<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

/**
 * Adiciona a coluna `brinde` (item oferecido como brinde) à tabela promocoes.
 * À prova de falha: erros são registrados sem derrubar o deploy (Railway/pgsql).
 */
return new class extends Migration
{
    public function up(): void
    {
        try {
            if (Schema::hasTable('promocoes') && !Schema::hasColumn('promocoes', 'brinde')) {
                Schema::table('promocoes', function (Blueprint $table) {
                    $table->string('brinde', 80)->nullable()->after('descricao');
                });
            }
        } catch (\Throwable $e) {
            Log::warning('add_brinde_to_promocoes falhou: ' . $e->getMessage());
        }
    }

    public function down(): void
    {
        try {
            if (Schema::hasTable('promocoes') && Schema::hasColumn('promocoes', 'brinde')) {
                Schema::table('promocoes', function (Blueprint $table) {
                    $table->dropColumn('brinde');
                });
            }
        } catch (\Throwable $e) {
            Log::warning('add_brinde_to_promocoes rollback falhou: ' . $e->getMessage());
        }
    }
};
