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
        Schema::table('users', function (Blueprint $table) {
            // Campos para sistema de níveis VIP
            $table->integer('pontos_lifetime')->default(0); // Total de pontos já ganhos
            $table->integer('valor_gasto_total')->default(0); // Total gasto em centavos
            $table->integer('dias_consecutivos')->default(0); // Dias consecutivos com check-in
            $table->date('ultimo_checkin')->nullable(); // Data do último check-in
            $table->integer('empresas_visitadas')->default(0); // Contador de empresas únicas visitadas
            $table->decimal('multiplicador_pontos', 3, 2)->default(1.00); // Multiplicador baseado no nível
            
            // Sistema de ranking
            $table->integer('posicao_ranking')->nullable(); // Posição no ranking geral
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'pontos_lifetime',
                'valor_gasto_total', 
                'dias_consecutivos',
                'ultimo_checkin',
                'empresas_visitadas',
                'multiplicador_pontos',
                'posicao_ranking'
            ]);
        });
    }
};