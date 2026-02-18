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
        Schema::table('promocoes', function (Blueprint $table) {
            // Campos de desconto e pontos
            if (!Schema::hasColumn('promocoes', 'desconto')) {
                $table->decimal('desconto', 5, 2)->default(0)->after('descricao'); // % de desconto
            }
            if (!Schema::hasColumn('promocoes', 'pontos_necessarios')) {
                $table->integer('pontos_necessarios')->default(100)->after('desconto');
            }
            
            // Datas de validade
            if (!Schema::hasColumn('promocoes', 'data_inicio')) {
                $table->date('data_inicio')->nullable()->after('pontos_necessarios');
            }
            if (!Schema::hasColumn('promocoes', 'validade')) {
                $table->date('validade')->nullable()->after('data_inicio');
            }
            
            // Estatísticas
            if (!Schema::hasColumn('promocoes', 'visualizacoes')) {
                $table->integer('visualizacoes')->default(0)->after('validade');
            }
            if (!Schema::hasColumn('promocoes', 'resgates')) {
                $table->integer('resgates')->default(0)->after('visualizacoes');
            }
            if (!Schema::hasColumn('promocoes', 'usos')) {
                $table->integer('usos')->default(0)->after('resgates');
            }
            
            // Status (pode ser 'ativa', 'pausada', 'expirada')
            if (!Schema::hasColumn('promocoes', 'status')) {
                $table->string('status', 20)->default('ativa')->after('usos');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promocoes', function (Blueprint $table) {
            $columns = ['desconto', 'pontos_necessarios', 'data_inicio', 'validade', 
                       'visualizacoes', 'resgates', 'usos', 'status'];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('promocoes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
