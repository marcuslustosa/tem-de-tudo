<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // 1. Primeiro remove todas as foreign keys que apontam para empresas
        $tables = [
            'check_ins',
            'coupons',
            'qr_codes',
            'pontos',
            'resgates',
            'ofertas',
            'discount_levels'
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) {
                    // Remove a constraint da foreign key se existir
                    $foreignKey = "{$table->getTable()}_empresa_id_foreign";
                    if (DB::getSchemaBuilder()->getConnection()->getDoctrineSchemaManager()->listTableForeignKeys($table->getTable())) {
                        $table->dropForeign($foreignKey);
                    }
                });
            }
        }

        // 2. Agora podemos recriar a tabela empresas
        Schema::dropIfExists('empresas');
        
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

        // 3. Por fim, recria as foreign keys
        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreign('empresa_id')
                          ->references('id')
                          ->on('empresas')
                          ->onDelete('cascade');
                });
            }
        }
    }

    public function down()
    {
        // Remove foreign keys primeiro
        $tables = [
            'check_ins',
            'coupons',
            'qr_codes',
            'pontos',
            'resgates',
            'ofertas',
            'discount_levels'
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropForeign(["{$table->getTable()}_empresa_id_foreign"]);
                });
            }
        }

        // Então remove a tabela
        Schema::dropIfExists('empresas');
    }
};