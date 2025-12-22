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
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role')->nullable()->after('telefone');
            }
            if (!Schema::hasColumn('users', 'nivel')) {
                $table->string('nivel')->default('Bronze')->after('role');
            }
            if (!Schema::hasColumn('users', 'nivel_acesso')) {
                $table->string('nivel_acesso')->default('usuario_comum')->after('nivel');
            }
            if (!Schema::hasColumn('users', 'permissions')) {
                $table->json('permissions')->nullable()->after('nivel_acesso');
            }
            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('permissions');
            }
            if (!Schema::hasColumn('users', 'pontos')) {
                $table->integer('pontos')->default(0)->after('is_active');
            }
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
