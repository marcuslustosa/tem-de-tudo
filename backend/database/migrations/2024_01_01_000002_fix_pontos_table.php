<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Verifica se a tabela pontos não existe
        if (!Schema::hasTable('pontos')) {
            // Verifica se existe a tabela points para renomear
            if (Schema::hasTable('points')) {
                Schema::rename('points', 'pontos');
            } else {
                // Se nenhuma tabela existir, cria a nova tabela pontos
                Schema::create('pontos', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('user_id')->constrained()->onDelete('cascade');
                    $table->integer('pontos')->default(0);
                    $table->timestamps();
                });
            }
        }

        // Se a tabela pontos existe, verifica as colunas
        if (Schema::hasTable('pontos')) {
            Schema::table('pontos', function (Blueprint $table) {
                // Verifica se existe a coluna points mas não existe pontos
                if (Schema::hasColumn('pontos', 'points') && !Schema::hasColumn('pontos', 'pontos')) {
                    // Renomeia points para pontos
                    $table->renameColumn('points', 'pontos');
                }
                // Se não existe nem points nem pontos, cria a coluna pontos
                if (!Schema::hasColumn('pontos', 'points') && !Schema::hasColumn('pontos', 'pontos')) {
                    $table->integer('pontos')->default(0);
                }
            });
        }
    }

    public function down()
    {
        // Não fazemos nada no down para evitar perda de dados
    }
};