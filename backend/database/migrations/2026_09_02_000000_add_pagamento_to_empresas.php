<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Opcao B do fluxo de empresa: campo para o admin master registrar/ver
 * a confirmacao de pagamento antes de aprovar a empresa.
 * Bulletproof: nunca lanca (nao derruba o deploy no Railway).
 */
return new class extends Migration
{
    public function up(): void
    {
        try {
            if (Schema::hasTable('empresas') && !Schema::hasColumn('empresas', 'pagamento_confirmado')) {
                Schema::table('empresas', function (Blueprint $table) {
                    $table->boolean('pagamento_confirmado')->default(false);
                    $table->timestamp('pagamento_confirmado_em')->nullable();
                });
            }
        } catch (\Throwable $e) {
            Log::warning('Migration add_pagamento_to_empresas falhou: ' . $e->getMessage());
        }
    }

    public function down(): void
    {
        // No-op proposital (nao derruba coluna em uso).
    }
};
