<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ponto_transacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('pontos'); // pode ser negativo (resgate)
            $table->enum('tipo', ['adicao', 'resgate'])->default('adicao');
            $table->string('descricao');
            $table->decimal('valor_compra', 10, 2)->nullable();
            $table->foreignId('estabelecimento_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ponto_transacoes');
    }
};
