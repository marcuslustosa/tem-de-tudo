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
                $table->string('titulo', 100);
                $table->text('descricao');
                $table->string('imagem')->nullable(); // Tornando opcional
                
                // Campos de pontos e desconto
                $table->integer('pontos_necessarios')->default(100);
                $table->decimal('desconto_percentual', 5, 2)->nullable();
                $table->decimal('desconto_valor', 10, 2)->nullable();
                
                // Campos de controle
                $table->date('validade')->nullable();
                $table->integer('quantidade_disponivel')->nullable();
                $table->text('termos_condicoes')->nullable();
                $table->boolean('ativo')->default(true);
                
                // Campos de notificação
                $table->timestamp('data_envio')->nullable();
                $table->integer('total_envios')->default(0);
                
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
