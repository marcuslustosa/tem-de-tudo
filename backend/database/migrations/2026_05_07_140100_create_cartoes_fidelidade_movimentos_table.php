<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cartoes_fidelidade_movimentos')) {
            return;
        }

        Schema::create('cartoes_fidelidade_movimentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cartao_fidelidade_id')->constrained('cartoes_fidelidade')->onDelete('cascade');
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedInteger('pontos')->default(0);
            $table->string('tipo', 20);
            $table->string('descricao', 280)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['empresa_id', 'user_id', 'created_at'], 'cartao_mov_empresa_user_created_idx');
            $table->index(['cartao_fidelidade_id', 'tipo'], 'cartao_mov_card_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cartoes_fidelidade_movimentos');
    }
};
