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
        if (!Schema::hasTable('promocoes')) {
            Schema::create('promocoes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
                $table->string('titulo', 100); // Limite de caracteres
                $table->text('descricao'); // Limite será validado no backend (ex: 500 chars)
                $table->string('imagem'); // OBRIGATÓRIO
                $table->boolean('ativo')->default(true);
                $table->timestamp('data_envio')->nullable(); // Quando foi enviada a notificação
                $table->integer('total_envios')->default(0); // Quantos clientes receberam
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promocoes');
    }
};
