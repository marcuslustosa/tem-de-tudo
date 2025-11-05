<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove a tabela se existir (seguro no PostgreSQL)
        Schema::dropIfExists('sessions');

        // Cria a tabela do zero
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('payload');
            $table->integer('last_activity')->index();
        });

    public function down()
    {
        // Não removemos a tabela no rollback para evitar perda de dados
        // Se precisar remover, descomente a linha abaixo após fazer backup
        // Schema::dropIfExists('sessions');
    }
};