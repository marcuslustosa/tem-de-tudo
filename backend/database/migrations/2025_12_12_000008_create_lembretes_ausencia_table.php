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
        Schema::create('lembretes_ausencia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->integer('dias_ausencia'); // 45, 30 ou 20
            $table->string('titulo'); // Ex: "Sentimos sua falta!"
            $table->text('mensagem'); // Ex: "Venha aproveitar nossas promoções..."
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lembretes_ausencia');
    }
};
