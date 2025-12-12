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
        if (!Schema::hasTable('cartoes_fidelidade_progresso')) {
            Schema::create('cartoes_fidelidade_progresso', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Cliente
                $table->foreignId('cartao_fidelidade_id')->constrained('cartoes_fidelidade')->onDelete('cascade');
                $table->integer('pontos_atuais')->default(0); // Ex: 3
                $table->integer('vezes_resgatado')->default(0); // Quantas vezes completou o cartão
                $table->timestamp('ultimo_ponto')->nullable(); // Última vez que ganhou ponto
                $table->timestamps();
                
                // Cliente tem 1 progresso por cartão
                $table->unique(['user_id', 'cartao_fidelidade_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cartoes_fidelidade_progresso');
    }
};
