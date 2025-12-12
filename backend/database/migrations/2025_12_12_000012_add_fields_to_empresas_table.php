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
            if (!Schema::hasColumn('empresas', 'ramo')) {
                $table->string('ramo')->nullable()->after('nome'); // Restaurante, Bar, Salão, etc
            }
            if (!Schema::hasColumn('empresas', 'whatsapp')) {
                $table->string('whatsapp')->nullable()->after('telefone');
            }
            if (!Schema::hasColumn('empresas', 'instagram')) {
                $table->string('instagram')->nullable()->after('whatsapp');
            }
            if (!Schema::hasColumn('empresas', 'facebook')) {
                $table->string('facebook')->nullable()->after('instagram');
            }
            if (!Schema::hasColumn('empresas', 'avaliacao_media')) {
                $table->decimal('avaliacao_media', 3, 2)->default(0)->after('facebook'); // 0.00 a 5.00
            }
            if (!Schema::hasColumn('empresas', 'total_avaliacoes')) {
                $table->integer('total_avaliacoes')->default(0)->after('avaliacao_media');
            }
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
