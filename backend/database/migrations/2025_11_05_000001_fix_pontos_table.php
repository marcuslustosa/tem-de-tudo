<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Verifica se a tabela "pontos" existe
        if (!Schema::hasTable('pontos')) {

            // Se não existir, tenta ver se a antiga "points" existe
            if (Schema::hasTable('points')) {
                // Renomeia a tabela antiga para "pontos"
                Schema::rename('points', 'pontos');
            } else {
                // Se nenhuma existir, cria a tabela do zero
                Schema::create('pontos', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('user_id')->nullable();
                    $table->integer('pontos')->default(0);
                    $table->timestamps();
                });
            }
        }

        // Agora garante que a coluna esteja no formato correto
        if (Schema::hasTable('pontos')) {
            if (Schema::hasColumn('pontos', 'points') && !Schema::hasColumn('pontos', 'pontos')) {
                Schema::table('pontos', function (Blueprint $table) {
                    $table->renameColumn('points', 'pontos');
                });
            } elseif (!Schema::hasColumn('pontos', 'pontos')) {
                Schema::table('pontos', function (Blueprint $table) {
                    $table->integer('pontos')->default(0);
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pontos')) {
            Schema::table('pontos', function (Blueprint $table) {
                if (Schema::hasColumn('pontos', 'pontos')) {
                    $table->renameColumn('pontos', 'points');
                }
            });
        }
    }
};