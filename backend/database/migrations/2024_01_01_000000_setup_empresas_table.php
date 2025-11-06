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

        // Adiciona ou atualiza as colunas empresa_id em todas as tabelas relacionadas
        if (Schema::hasTable('check_ins') && !Schema::hasColumn('check_ins', 'empresa_id')) {
            Schema::table('check_ins', function (Blueprint $table) {
                $table->foreignId('empresa_id')->nullable()->after('id');
            });
        }
        
        if (Schema::hasTable('coupons') && !Schema::hasColumn('coupons', 'empresa_id')) {
            Schema::table('coupons', function (Blueprint $table) {
                $table->foreignId('empresa_id')->nullable()->after('id');
            });
        }
        
        if (Schema::hasTable('qr_codes') && !Schema::hasColumn('qr_codes', 'empresa_id')) {
            Schema::table('qr_codes', function (Blueprint $table) {
                $table->foreignId('empresa_id')->nullable()->after('id');
            });
        }

        if (Schema::hasTable('pontos') && !Schema::hasColumn('pontos', 'empresa_id')) {
            Schema::table('pontos', function (Blueprint $table) {
                $table->foreignId('empresa_id')->nullable()->after('id');
            });
        }

        // Agora adiciona as foreign keys
        if (Schema::hasTable('check_ins')) {
            Schema::table('check_ins', function (Blueprint $table) {
                if (!Schema::hasColumn('check_ins', 'empresa_id_foreign')) {
                    $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
                }
            });
        }
        
        if (Schema::hasTable('coupons')) {
            Schema::table('coupons', function (Blueprint $table) {
                if (!Schema::hasColumn('coupons', 'empresa_id_foreign')) {
                    $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
                }
            });
        }
        
        if (Schema::hasTable('qr_codes')) {
            Schema::table('qr_codes', function (Blueprint $table) {
                if (!Schema::hasColumn('qr_codes', 'empresa_id_foreign')) {
                    $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
                }
            });
        }

        if (Schema::hasTable('pontos')) {
            Schema::table('pontos', function (Blueprint $table) {
                if (!Schema::hasColumn('pontos', 'empresa_id_foreign')) {
                    $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
                }
            });
        }
    }

    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS empresas CASCADE');
    }
};