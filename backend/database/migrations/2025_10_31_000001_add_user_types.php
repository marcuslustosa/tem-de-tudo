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
        Schema::table('users', function (Blueprint $table) {
            // Adiciona campo tipo após o campo email
            $table->string('type')->default('client')->after('email'); // valores: client, company, admin
            
            // Campos específicos para empresas
            $table->string('cnpj')->nullable()->after('type');
            $table->string('phone')->nullable()->after('cnpj');
            $table->text('address')->nullable()->after('phone');
            
            // Campos específicos para clientes
            $table->string('cpf')->nullable()->after('address');
            $table->date('birth_date')->nullable()->after('cpf');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['type', 'cnpj', 'phone', 'address', 'cpf', 'birth_date']);
        });
    }
};