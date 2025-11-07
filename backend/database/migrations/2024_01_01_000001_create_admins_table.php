<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('admins')) {
            Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('email')->unique();
            $table->string('telefone')->nullable();
            $table->string('password');
            $table->string('nivel')->default('Operador'); // Operador, Gerente, Master
            $table->string('empresa')->nullable();
            $table->string('cnpj')->nullable();
            $table->json('permissoes')->nullable();
            $table->foreignId('criado_por')->nullable()->references('id')->on('admins')->onDelete('set null');
            $table->string('status')->default('ativo'); // ativo, inativo, suspenso
            $table->string('senha_temporaria')->nullable();
            $table->timestamp('ultimo_login')->nullable();
            $table->string('ip_ultimo_login')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->index(['status', 'nivel']);
            $table->index('email');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('admins');
    }
};
