<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Primeiro remove qualquer vestígio de tabelas antigas
        Schema::dropIfExists('pontos');
        Schema::dropIfExists('points');

        // 2. Cria a tabela com a estrutura correta
        Schema::create('pontos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->onDelete('set null');
            $table->foreignId('check_in_id')->nullable()->constrained('check_ins')->onDelete('set null');
            $table->integer('pontos')->default(0);
            $table->enum('tipo', ['ganho', 'resgate', 'bonus', 'ajuste'])->default('ganho');
            $table->string('descricao')->nullable();
            $table->decimal('valor_original', 10, 2)->nullable();
            $table->decimal('multiplicador_usado', 3, 1)->default(1.0);
            $table->timestamps();

            // Índices para melhor performance
            $table->index(['user_id', 'created_at']);
            $table->index(['empresa_id', 'created_at']);
            $table->index('tipo');
        });
    }

    public function down(): void
    {
        // Não remove a tabela no rollback para segurança
        // Schema::dropIfExists('pontos');
    }
};