<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabela de campanhas de multiplicador temporário de pontos
        Schema::create('campanhas_multiplicador', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->decimal('multiplicador', 5, 2)->default(2.0);
            $table->dateTime('data_inicio');
            $table->dateTime('data_fim');
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index(['empresa_id', 'ativo', 'data_inicio', 'data_fim']);
        });

        // Colunas de controle de estoque e limite por usuário nas promoções
        Schema::table('promocoes', function (Blueprint $table) {
            if (!Schema::hasColumn('promocoes', 'qtd_disponivel')) {
                $table->unsignedInteger('qtd_disponivel')->nullable()->after('status')
                    ->comment('null = ilimitado');
            }
            if (!Schema::hasColumn('promocoes', 'qtd_resgatada')) {
                $table->unsignedInteger('qtd_resgatada')->default(0)->after('qtd_disponivel');
            }
            if (!Schema::hasColumn('promocoes', 'limite_por_usuario')) {
                $table->unsignedInteger('limite_por_usuario')->default(1)->after('qtd_resgatada')
                    ->comment('Cantidad máxima de resgates por usuário');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campanhas_multiplicador');

        Schema::table('promocoes', function (Blueprint $table) {
            $table->dropColumn(['qtd_disponivel', 'qtd_resgatada', 'limite_por_usuario']);
        });
    }
};
