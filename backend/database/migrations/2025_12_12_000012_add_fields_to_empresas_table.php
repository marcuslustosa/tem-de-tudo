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
        Schema::table('empresas', function (Blueprint $table) {
            $table->string('ramo')->nullable()->after('nome'); // Restaurante, Bar, Salão, etc
            $table->string('whatsapp')->nullable()->after('telefone');
            $table->string('instagram')->nullable()->after('whatsapp');
            $table->string('facebook')->nullable()->after('instagram');
            $table->decimal('avaliacao_media', 3, 2)->default(0)->after('facebook'); // 0.00 a 5.00
            $table->integer('total_avaliacoes')->default(0)->after('avaliacao_media');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn(['ramo', 'whatsapp', 'instagram', 'facebook', 'avaliacao_media', 'total_avaliacoes']);
        });
    }
};
