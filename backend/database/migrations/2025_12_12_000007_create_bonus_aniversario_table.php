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
        Schema::create('bonus_aniversario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->string('titulo'); // Ex: "Parabéns! Você ganhou 10% de desconto"
            $table->text('descricao'); // Ex: "Comemore seu aniversário conosco..."
            $table->string('presente'); // Ex: "10% de desconto"
            $table->string('imagem')->nullable(); // Imagem do card "Feliz Aniversário"
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bonus_aniversario');
    }
};
