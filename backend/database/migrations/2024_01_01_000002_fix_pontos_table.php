<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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
                return; // Retorna aqui pois a tabela foi criada com a estrutura correta
            }
        }

        // Verifica se a tabela pontos existe mas não tem a coluna pontos
        if (Schema::hasTable('pontos') && !Schema::hasColumn('pontos', 'pontos')) {
            Schema::table('pontos', function (Blueprint $table) {
                // Adiciona a coluna pontos se ela não existir
                if (!Schema::hasColumn('pontos', 'pontos')) {
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