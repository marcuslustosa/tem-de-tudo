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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->onDelete('cascade');
            $table->foreignId('checkin_id')->nullable()->constrained('check_ins')->onDelete('set null');
            $table->string('codigo', 20)->unique();
            $table->string('tipo');
            $table->string('descricao');
            $table->integer('custo_pontos');
            $table->decimal('valor_desconto', 10, 2)->nullable();
            $table->integer('porcentagem_desconto')->nullable();
            $table->enum('status', ['active', 'used', 'expired'])->default('active');
            $table->timestamp('expira_em')->nullable();
            $table->timestamp('usado_em')->nullable();
            $table->json('dados_extra')->nullable();
            $table->timestamps();

            // Índices
            $table->index(['user_id', 'status']);
            $table->index(['tipo']);
            $table->index(['expira_em']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};