<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('produtos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->decimal('preco', 10, 2);
            $table->string('categoria')->nullable();
            $table->string('imagem')->nullable();
            $table->boolean('ativo')->default(true);
            $table->integer('estoque')->nullable();
            $table->integer('pontos_gerados')->nullable(); // Pontos específicos do produto
            $table->timestamps();

            $table->index(['empresa_id', 'ativo']);
            $table->index('categoria');
            $table->index('ativo');
        });

        // Adicionar foreign key na tabela pagamentos após produtos existir
        if (Schema::hasTable('pagamentos')) {
            Schema::table('pagamentos', function (Blueprint $table) {
                $table->foreign('produto_id')->references('id')->on('produtos')->onDelete('set null');
            });
        }
    }

    public function down()
    {
        // Remover foreign key primeiro se existir
        if (Schema::hasTable('pagamentos')) {
            Schema::table('pagamentos', function (Blueprint $table) {
                $table->dropForeign(['produto_id']);
            });
        }
        
        Schema::dropIfExists('produtos');
    }
};