<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // 1. Primeiro removemos todas as tabelas e constraints relacionadas
        DB::statement('DROP TABLE IF EXISTS pontos CASCADE');

        // 2. Criamos a tabela pontos do zero
        Schema::create('pontos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained()
                  ->onDelete('cascade');
            $table->foreignId('empresa_id')
                  ->nullable()
                  ->references('id')
                  ->on('empresas')
                  ->onDelete('cascade');
            $table->integer('pontos')->default(0);
            $table->timestamps();
        });

        // 3. Adicionamos campos relacionados a pontos em outras tabelas
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'pontos')) {
            Schema::table('users', function (Blueprint $table) {
                $table->integer('pontos')->default(0)->after('email');
                $table->integer('pontos_pendentes')->default(0)->after('pontos');
                $table->string('nivel')->default('Bronze')->after('pontos_pendentes');
            });
        }

        // 4. Atualiza campos em outras tabelas
        if (Schema::hasTable('check_ins') && !Schema::hasColumn('check_ins', 'pontos_calculados')) {
            Schema::table('check_ins', function (Blueprint $table) {
                $table->integer('pontos_calculados')->default(0);
            });
        }

        if (Schema::hasTable('coupons') && !Schema::hasColumn('coupons', 'custo_pontos')) {
            Schema::table('coupons', function (Blueprint $table) {
                $table->integer('custo_pontos')->default(0);
            });
        }
    }

    public function down()
    {
        // Remove campos relacionados primeiro
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn(['pontos', 'pontos_pendentes', 'nivel']);
            });
        }

        if (Schema::hasTable('check_ins')) {
            Schema::table('check_ins', function (Blueprint $table) {
                $table->dropColumn('pontos_calculados');
            });
        }

        if (Schema::hasTable('coupons')) {
            Schema::table('coupons', function (Blueprint $table) {
                $table->dropColumn('custo_pontos');
            });
        }

        // Por fim, remove a tabela principal
        Schema::dropIfExists('pontos');
    }
};