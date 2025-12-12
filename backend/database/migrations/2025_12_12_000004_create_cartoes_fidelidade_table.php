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
        if (!Schema::hasTable('cartoes_fidelidade')) {
            Schema::create('cartoes_fidelidade', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
                $table->string('titulo'); // Ex: "GANHE 1 PONTO A CADA VISITA"
                $table->text('descricao'); // Ex: "Com 15 pontos: Ganhe 1 Porção..."
                $table->integer('meta_pontos'); // Ex: 15
                $table->string('recompensa'); // Ex: "1 Porção de Fritas ou 1 Lanche"
                $table->boolean('ativo')->default(true);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cartoes_fidelidade');
    }
};
