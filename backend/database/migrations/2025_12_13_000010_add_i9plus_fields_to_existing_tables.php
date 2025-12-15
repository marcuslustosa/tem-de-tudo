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
        // Adicionar campos i9Plus à tabela promocoes
        if (Schema::hasTable('promocoes') && !Schema::hasColumn('promocoes', 'desconto')) {
            Schema::table('promocoes', function (Blueprint $table) {
                $table->decimal('desconto', 5, 2)->nullable()->after('pontos_necessarios');
                $table->date('data_inicio')->nullable()->after('data_fim');
                $table->enum('status', ['ativa', 'pausada', 'encerrada'])->default('ativa')->after('valido_ate');
                $table->integer('visualizacoes')->default(0)->after('status');
                $table->integer('resgates')->default(0)->after('visualizacoes');
                $table->integer('usos')->default(0)->after('resgates');
                
                $table->index('status');
                $table->index('data_inicio');
            });
        }
        
        // Adicionar campos i9Plus à tabela check_ins
        if (Schema::hasTable('check_ins') && !Schema::hasColumn('check_ins', 'pontos')) {
            Schema::table('check_ins', function (Blueprint $table) {
                $table->integer('pontos')->default(0)->after('empresa_id');
                $table->timestamp('data')->nullable()->after('created_at');
                
                $table->index('pontos');
                $table->index('data');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('promocoes')) {
            Schema::table('promocoes', function (Blueprint $table) {
                $table->dropColumn(['desconto', 'data_inicio', 'status', 'visualizacoes', 'resgates', 'usos']);
            });
        }
        
        if (Schema::hasTable('check_ins')) {
            Schema::table('check_ins', function (Blueprint $table) {
                $table->dropColumn(['pontos', 'data']);
            });
        }
    }
};
