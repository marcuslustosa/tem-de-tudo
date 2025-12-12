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
        Schema::create('bonus_adesao', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->string('titulo'); // Ex: "10% de desconto"
            $table->text('descricao')->nullable();
            $table->enum('tipo_desconto', ['porcentagem', 'valor_fixo']); // % ou R$
            $table->decimal('valor_desconto', 10, 2); // 10 ou 5.00
            $table->string('imagem')->nullable(); // Path da imagem
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bonus_adesao');
    }
};
