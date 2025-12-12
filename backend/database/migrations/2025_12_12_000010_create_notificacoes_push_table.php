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
        if (!Schema::hasTable('notificacoes_push')) {
            Schema::create('notificacoes_push', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Quem recebeu
                $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade'); // De qual empresa
                $table->enum('tipo', ['promocao', 'aniversario', 'lembrete']); // Tipo
                $table->string('titulo');
                $table->text('mensagem');
                $table->string('imagem')->nullable();
                $table->boolean('enviado')->default(false); // Foi enviado?
                $table->timestamp('data_envio')->nullable(); // Quando foi enviado
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notificacoes_push');
    }
};
