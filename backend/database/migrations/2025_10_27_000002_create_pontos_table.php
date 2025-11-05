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
        // Verifica dependências
        if (!Schema::hasTable('users')) {
            throw new \Exception('A tabela users precisa existir antes de criar pontos');
        }
        
        if (!Schema::hasTable('empresas')) {
            throw new \Exception('A tabela empresas precisa existir antes de criar pontos');
        }
        
        if (!Schema::hasTable('check_ins')) {
            throw new \Exception('A tabela check_ins precisa existir antes de criar pontos');
        }

        // Cria a tabela se não existir
        if (!Schema::hasTable('pontos')) {
            Schema::create('pontos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('empresa_id')->nullable()->constrained('empresas')->onDelete('set null');
                $table->foreignId('check_in_id')->nullable()->constrained('check_ins')->onDelete('set null');
                $table->integer('pontos');
                $table->enum('tipo', ['ganho', 'resgate', 'bonus', 'ajuste'])->default('ganho');
                $table->string('descricao');
                $table->decimal('valor_original', 10, 2)->nullable();
                $table->decimal('multiplicador_usado', 3, 1)->default(1.0);
                $table->timestamps();

                // Índices para melhor performance no PostgreSQL
                $table->index(['user_id', 'created_at']);
                $table->index(['empresa_id', 'created_at']);
                $table->index('tipo');
            });
            return;
        }

        // Se a tabela existir, verifica e adiciona colunas faltantes
        Schema::table('pontos', function (Blueprint $table) {
            if (!Schema::hasColumn('pontos', 'user_id')) {
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
            }
            if (!Schema::hasColumn('pontos', 'empresa_id')) {
                $table->foreignId('empresa_id')->nullable()->constrained('empresas')->onDelete('set null');
            }
            if (!Schema::hasColumn('pontos', 'check_in_id')) {
                $table->foreignId('check_in_id')->nullable()->constrained('check_ins')->onDelete('set null');
            }
            if (!Schema::hasColumn('pontos', 'pontos')) {
                $table->integer('pontos');
            }
            if (!Schema::hasColumn('pontos', 'tipo')) {
                $table->enum('tipo', ['ganho', 'resgate', 'bonus', 'ajuste'])->default('ganho');
            }
            if (!Schema::hasColumn('pontos', 'descricao')) {
                $table->string('descricao');
            }
            if (!Schema::hasColumn('pontos', 'valor_original')) {
                $table->decimal('valor_original', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('pontos', 'multiplicador_usado')) {
                $table->decimal('multiplicador_usado', 3, 1)->default(1.0);
            }
            
            // Adiciona índices se não existirem
            if (!Schema::hasIndex('pontos', ['user_id', 'created_at'])) {
                $table->index(['user_id', 'created_at']);
            }
            if (!Schema::hasIndex('pontos', ['empresa_id', 'created_at'])) {
                $table->index(['empresa_id', 'created_at']);
            }
            if (!Schema::hasIndex('pontos', ['tipo'])) {
                $table->index('tipo');
            }
        });
    }

    /**
     * Reverse the migrations.
     * Desabilitado para segurança em produção
     */
    public function down(): void
    {
        // Schema::dropIfExists('pontos');
    }
};