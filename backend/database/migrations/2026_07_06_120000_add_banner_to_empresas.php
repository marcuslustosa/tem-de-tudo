<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

/**
 * Adiciona a coluna `banner` (capa horizontal do estabelecimento) à tabela empresas.
 * À prova de falha: qualquer erro é registrado sem derrubar o deploy (Railway/pgsql).
 */
return new class extends Migration
{
    public function up(): void
    {
        try {
            if (Schema::hasTable('empresas') && !Schema::hasColumn('empresas', 'banner')) {
                Schema::table('empresas', function (Blueprint $table) {
                    $table->string('banner', 500)->nullable()->after('logo');
                });
            }
        } catch (\Throwable $e) {
            Log::warning('add_banner_to_empresas falhou: ' . $e->getMessage());
        }
    }

    public function down(): void
    {
        try {
            if (Schema::hasTable('empresas') && Schema::hasColumn('empresas', 'banner')) {
                Schema::table('empresas', function (Blueprint $table) {
                    $table->dropColumn('banner');
                });
            }
        } catch (\Throwable $e) {
            Log::warning('add_banner_to_empresas rollback falhou: ' . $e->getMessage());
        }
    }
};
