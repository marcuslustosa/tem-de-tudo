<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('banners')) {
            Schema::create('banners', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('image_url')->nullable();
                $table->string('link')->nullable();
                $table->boolean('active')->default(true);
                $table->integer('position')->default(0);
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->json('payload')->nullable();
                $table->timestamps();

                $table->index(['active', 'position']);
            });
        }

        if (!Schema::hasTable('categorias')) {
            Schema::create('categorias', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->boolean('active')->default(true);
                $table->integer('position')->default(0);
                $table->timestamps();

                $table->index(['active', 'position']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
        Schema::dropIfExists('categorias');
    }
};

