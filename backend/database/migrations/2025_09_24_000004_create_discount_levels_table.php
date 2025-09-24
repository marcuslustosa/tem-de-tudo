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
        Schema::create('discount_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->integer('points_required'); // Pontos necessários
            $table->decimal('discount_percentage', 5, 2); // Porcentagem de desconto (ex: 15.50%)
            $table->string('title', 50); // Ex: Bronze, Prata, Ouro
            $table->text('description')->nullable(); // Descrição do nível
            $table->boolean('is_active')->default(true); // Se está ativo
            $table->boolean('applies_to_all_products')->default(true); // Aplica a todos produtos
            $table->boolean('applies_to_all_services')->default(true); // Aplica a todos serviços
            $table->json('specific_categories')->nullable(); // Categorias específicas se não for "todos"
            $table->timestamps();

            // Índices
            $table->index(['empresa_id', 'points_required']);
            $table->index(['empresa_id', 'is_active']);
            
            // Constraint: uma empresa não pode ter dois níveis com mesma quantidade de pontos
            $table->unique(['empresa_id', 'points_required']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discount_levels');
    }
};