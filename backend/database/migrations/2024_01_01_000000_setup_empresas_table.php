<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Desabilita verificação de foreign keys temporariamente
        Schema::disableForeignKeyConstraints();

        try {
            // Recria a tabela empresas do zero
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

        } finally {
            // Sempre reativa as foreign keys, mesmo se houver erro
            Schema::enableForeignKeyConstraints();
        }
    }

    public function down()
    {
        Schema::dropIfExists('empresas');
    }
};