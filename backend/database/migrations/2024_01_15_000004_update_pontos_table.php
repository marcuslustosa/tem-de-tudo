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
        Schema::table('pontos', function (Blueprint $table) {
            // Renomear coluna points para pontos
            $table->renameColumn('points', 'pontos');
            
            // Adicionar novas colunas
            $table->foreignId('checkin_id')->nullable()->after('empresa_id')->constrained('check_ins')->onDelete('set null');
            $table->foreignId('coupon_id')->nullable()->after('checkin_id')->constrained('coupons')->onDelete('set null');
            $table->text('descricao')->nullable()->after('pontos');
            $table->enum('tipo', ['earn', 'redeem', 'bonus', 'adjustment'])->default('earn')->after('descricao');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pontos', function (Blueprint $table) {
            $table->dropForeign(['checkin_id']);
            $table->dropForeign(['coupon_id']);
            $table->dropColumn(['checkin_id', 'coupon_id', 'descricao', 'tipo']);
            $table->renameColumn('pontos', 'points');
        });
    }
};