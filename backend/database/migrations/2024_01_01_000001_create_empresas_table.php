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
        // Verifica se a tabela já existe
        if (!Schema::hasTable('empresas')) {
            Schema::create('empresas', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('cnpj')->unique();
                $table->text('address');
                $table->string('phone');
                $table->string('email')->unique();
                $table->json('photos')->nullable();
                $table->json('services')->nullable();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->timestamps();

                // Índices para melhor performance no PostgreSQL
                $table->index('cnpj');
                $table->index('email');
            });
            return;
        }

        // Se a tabela existir, verifica e adiciona colunas faltantes
        Schema::table('empresas', function (Blueprint $table) {
            if (!Schema::hasColumn('empresas', 'name')) {
                $table->string('name');
            }
            if (!Schema::hasColumn('empresas', 'cnpj')) {
                $table->string('cnpj')->unique();
            }
            if (!Schema::hasColumn('empresas', 'address')) {
                $table->text('address');
            }
            if (!Schema::hasColumn('empresas', 'phone')) {
                $table->string('phone');
            }
            if (!Schema::hasColumn('empresas', 'email')) {
                $table->string('email')->unique();
            }
            if (!Schema::hasColumn('empresas', 'photos')) {
                $table->json('photos')->nullable();
            }
            if (!Schema::hasColumn('empresas', 'services')) {
                $table->json('services')->nullable();
            }
            if (!Schema::hasColumn('empresas', 'user_id')) {
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     * Não remove a tabela para evitar perda de dados em produção
     */
    public function down(): void
    {
        // Schema::dropIfExists('empresas');
    }
};