<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Expiração de pontos
        Schema::table('pontos', function (Blueprint $table) {
            $table->boolean('expirado')->default(false)->after('tipo');
            $table->timestamp('expired_at')->nullable()->after('expirado');
        });

        // Programa de indicação
        Schema::table('users', function (Blueprint $table) {
            $table->string('referral_code', 12)->nullable()->unique()->after('posicao_ranking');
            $table->unsignedBigInteger('referred_by')->nullable()->after('referral_code');
            $table->foreign('referred_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pontos', function (Blueprint $table) {
            $table->dropColumn(['expirado', 'expired_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referred_by']);
            $table->dropColumn(['referral_code', 'referred_by']);
        });
    }
};
