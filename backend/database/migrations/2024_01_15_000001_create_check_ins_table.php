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
        // Garante que a tabela empresas existe antes
        if (!Schema::hasTable('empresas')) {
            throw new \Exception('A tabela empresas precisa ser criada antes de check_ins');
        }

        if (!Schema::hasTable('check_ins')) {
            Schema::create('check_ins', function (Blueprint $table) {
                $table->id();
                
                // Relacionamentos com outras tabelas (com verificação de existência)
                if (Schema::hasTable('users')) {
                    $table->foreignId('user_id')
                        ->constrained()
                        ->onDelete('cascade');
                }
                
                $table->foreignId('empresa_id')
                    ->constrained('empresas')
                    ->onDelete('cascade');
                
                if (Schema::hasTable('qr_codes')) {
                    $table->foreignId('qr_code_id')
                        ->nullable()
                        ->constrained('qr_codes')
                        ->onDelete('set null');
                }
                
                // Campos do check-in
                $table->decimal('valor_compra', 10, 2);
                $table->integer('pontos_calculados');
                $table->string('foto_cupom')->nullable();
                $table->decimal('latitude', 10, 8)->nullable();
                $table->decimal('longitude', 11, 8)->nullable();
                $table->text('observacoes')->nullable();
                
                // Status e validação
                $table->enum('status', ['pending', 'approved', 'rejected'])
                    ->default('pending');
                $table->string('codigo_validacao', 20)->unique();
                
                // Campos de aprovação/rejeição
                $table->timestamp('aprovado_em')->nullable();
                $table->foreignId('aprovado_por')
                    ->nullable()
                    ->constrained('users')
                    ->onDelete('set null');
                    
                $table->timestamp('rejeitado_em')->nullable();
                $table->foreignId('rejeitado_por')
                    ->nullable()
                    ->constrained('users')
                    ->onDelete('set null');
                $table->text('motivo_rejeicao')->nullable();
                
                // Campos adicionais
                $table->json('bonus_applied')->nullable();
                $table->timestamps();

                // Índices otimizados para PostgreSQL
                $table->index(['user_id', 'empresa_id']);
                $table->index(['status', 'created_at']); // índice composto para queries comuns
                $table->index(['codigo_validacao']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('check_ins');
    }
};