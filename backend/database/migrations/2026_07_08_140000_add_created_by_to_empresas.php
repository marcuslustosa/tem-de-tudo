<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

/**
 * `created_by`: id do usuario (revenda) que criou a empresa. Usado para a
 * revenda enxergar apenas os estabelecimentos que ela cadastrou. Nullable
 * (empresas antigas / criadas pelo master ficam null). A prova de falha.
 */
return new class extends Migration
{
    public function up(): void
    {
        try {
            if (Schema::hasTable('empresas') && !Schema::hasColumn('empresas', 'created_by')) {
                Schema::table('empresas', function (Blueprint $table) {
                    $table->unsignedBigInteger('created_by')->nullable()->after('owner_id');
                });
            }
        } catch (\Throwable $e) {
            Log::warning('add_created_by_to_empresas falhou: ' . $e->getMessage());
        }
    }

    public function down(): void
    {
        try {
            if (Schema::hasTable('empresas') && Schema::hasColumn('empresas', 'created_by')) {
                Schema::table('empresas', function (Blueprint $table) {
                    $table->dropColumn('created_by');
                });
            }
        } catch (\Throwable $e) {
            Log::warning('add_created_by_to_empresas rollback falhou: ' . $e->getMessage());
        }
    }
};
