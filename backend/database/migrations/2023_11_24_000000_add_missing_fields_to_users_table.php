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
            $table->string('role')->nullable()->after('telefone');
            $table->string('nivel_acesso')->default('usuario_comum')->after('role');
            $table->json('permissions')->nullable()->after('nivel_acesso');
            $table->boolean('is_active')->default(true)->after('permissions');
            $table->integer('pontos')->default(0)->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'nivel_acesso', 'permissions', 'is_active', 'pontos']);
        });
    }
};
