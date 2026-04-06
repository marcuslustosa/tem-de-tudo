<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Índices para pontos - lookups frequentes
        Schema::table('pontos', function (Blueprint $table) {
            $table->index('user_id', 'pontos_user_id_index');
            $table->index('empresa_id', 'pontos_empresa_id_index');
            $table->index('created_at', 'pontos_created_at_index');
        });

        // Índices para empresas - filtros e buscas
        Schema::table('empresas', function (Blueprint $table) {
            $table->index('status', 'empresas_status_index');
            $table->index('cpf_cnpj', 'empresas_cpf_cnpj_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropIndex('empresas_cpf_cnpj_index');
            $table->dropIndex('empresas_status_index');
        });

        Schema::table('pontos', function (Blueprint $table) {
            $table->dropIndex('pontos_created_at_index');
            $table->dropIndex('pontos_empresa_id_index');
            $table->dropIndex('pontos_user_id_index');
        });
    }
};
