<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('discount_levels')) {
            Schema::create('discount_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->integer('points_required');
            $table->decimal('discount_percentage', 5, 2);
            $table->string('title');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('applies_to_all_products')->default(true);
            $table->boolean('applies_to_all_services')->default(true);
            $table->json('specific_categories')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'is_active']);
            $table->index(['points_required', 'is_active']);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('discount_levels');
    }
};
