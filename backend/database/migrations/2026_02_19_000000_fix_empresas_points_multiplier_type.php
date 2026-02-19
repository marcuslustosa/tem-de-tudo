<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Para PostgreSQL: converter points_multiplier de decimal para float
        if (Schema::hasTable('empresas') && Schema::hasColumn('empresas', 'points_multiplier')) {
            if (DB::getDriverName() === 'pgsql') {
                // Converter decimal para double precision (float)
                DB::statement('ALTER TABLE empresas ALTER COLUMN points_multiplier TYPE DOUBLE PRECISION USING points_multiplier::double precision');
                DB::statement('ALTER TABLE empresas ALTER COLUMN points_multiplier SET DEFAULT 1.0');
            } else {
                // Para MySQL e outros
                Schema::table('empresas', function (Blueprint $table) {
                    $table->float('points_multiplier')->default(1.0)->change();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverte para decimal
        if (Schema::hasTable('empresas') && Schema::hasColumn('empresas', 'points_multiplier')) {
            Schema::table('empresas', function (Blueprint $table) {
                $table->decimal('points_multiplier', 3, 2)->default(1.00)->change();
            });
        }
    }
};
