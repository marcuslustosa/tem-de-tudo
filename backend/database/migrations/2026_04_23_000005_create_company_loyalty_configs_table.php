<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('company_loyalty_configs')) {
            return;
        }

        Schema::create('company_loyalty_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('empresas')->onDelete('cascade');
            $table->decimal('points_per_real', 8, 2)->nullable();
            $table->integer('scan_base_points')->nullable();
            $table->integer('redeem_points_per_currency')->default(10);
            $table->integer('min_redeem_points')->default(50);
            $table->integer('welcome_bonus_points')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('company_id');
            $table->index(['is_active', 'company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_loyalty_configs');
    }
};

