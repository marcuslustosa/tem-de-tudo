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
        Schema::table('check_ins', function (Blueprint $table) {
            // Verificar se colunas já existem antes de adicionar
            if (!Schema::hasColumn('check_ins', 'pontos_ganhos')) {
                $table->integer('pontos_ganhos')->default(0)->after('empresa_id');
            }
            if (!Schema::hasColumn('check_ins', 'pontos_base')) {
                $table->integer('pontos_base')->default(0)->after('pontos_ganhos');
            }
            if (!Schema::hasColumn('check_ins', 'multiplicador')) {
                $table->decimal('multiplicador', 3, 2)->default(1.00)->after('pontos_base');
            }
            if (!Schema::hasColumn('check_ins', 'valor_compra')) {
                $table->decimal('valor_compra', 10, 2)->nullable()->after('multiplicador');
            }
            if (!Schema::hasColumn('check_ins', 'detalhes_calculo')) {
                $table->text('detalhes_calculo')->nullable()->after('valor_compra');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('check_ins', function (Blueprint $table) {
            $table->dropColumn(['pontos_ganhos', 'pontos_base', 'multiplicador', 'valor_compra', 'detalhes_calculo']);
        });
    }
};
