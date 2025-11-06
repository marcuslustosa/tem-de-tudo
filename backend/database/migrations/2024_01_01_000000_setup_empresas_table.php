<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Drop com CASCADE para remover todas as constraints automaticamente
        DB::statement('DROP TABLE IF EXISTS empresas CASCADE');
            
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('cnpj')->unique();
            $table->text('address');
            $table->string('phone');
            $table->string('email');
            $table->json('photos')->nullable();
            $table->json('services')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('qr_code')->nullable();
            $table->string('plan')->default('free');
            $table->json('settings')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // Recria as foreign keys para empresas
        if (Schema::hasTable('check_ins')) {
            Schema::table('check_ins', function (Blueprint $table) {
                $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            });
        }
        
        if (Schema::hasTable('coupons')) {
            Schema::table('coupons', function (Blueprint $table) {
                $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            });
        }
        
        if (Schema::hasTable('qr_codes')) {
            Schema::table('qr_codes', function (Blueprint $table) {
                $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            });
        }

        if (Schema::hasTable('pontos')) {
            Schema::table('pontos', function (Blueprint $table) {
                $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS empresas CASCADE');
    }
};