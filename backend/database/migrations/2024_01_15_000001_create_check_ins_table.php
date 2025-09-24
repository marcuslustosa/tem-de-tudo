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
        Schema::create('check_ins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->foreignId('qr_code_id')->nullable()->constrained('qr_codes')->onDelete('set null');
            $table->decimal('valor_compra', 10, 2);
            $table->integer('pontos_calculados');
            $table->string('foto_cupom')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->text('observacoes')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('codigo_validacao', 20)->unique();
            $table->timestamp('aprovado_em')->nullable();
            $table->foreignId('aprovado_por')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('rejeitado_em')->nullable();
            $table->foreignId('rejeitado_por')->nullable()->constrained('users')->onDelete('set null');
            $table->text('motivo_rejeicao')->nullable();
            $table->json('bonus_applied')->nullable(); // bônus aplicados do QR
            $table->timestamps();

            // Índices
            $table->index(['user_id', 'empresa_id']);
            $table->index(['status']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('check_ins');
    }
};