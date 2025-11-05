<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Verifica se a tabela já existe
        if (!Schema::hasTable('sessions')) {
            // Cria a tabela apenas se ela não existir
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->text('payload');
                $table->integer('last_activity')->index();
            });
            return;
        }

        // Se a tabela existir, verifica e adiciona colunas faltantes
        Schema::table('sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('sessions', 'id')) {
                $table->string('id')->primary();
            }
            
            if (!Schema::hasColumn('sessions', 'user_id')) {
                $table->foreignId('user_id')->nullable()->index();
            }
            
            if (!Schema::hasColumn('sessions', 'ip_address')) {
                $table->string('ip_address', 45)->nullable();
            }
            
            if (!Schema::hasColumn('sessions', 'user_agent')) {
                $table->text('user_agent')->nullable();
            }
            
            if (!Schema::hasColumn('sessions', 'payload')) {
                $table->text('payload');
            }
            
            if (!Schema::hasColumn('sessions', 'last_activity')) {
                $table->integer('last_activity')->index();
            }
        });
    }

    public function down()
    {
        // Não removemos a tabela no rollback para evitar perda de dados
        // Se precisar remover, descomente a linha abaixo após fazer backup
        // Schema::dropIfExists('sessions');
    }
};