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
        if (!Schema::hasTable('bonus_adesaos')) {
            Schema::create('bonus_adesaos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('empresa_id')->nullable()->constrained('empresas')->onDelete('set null');
                $table->integer('pontos')->default(50);
                $table->boolean('resgatado')->default(false);
                $table->timestamp('data_resgate')->nullable();
                $table->timestamps();
                
                // Índices
                $table->index('user_id');
                $table->index('empresa_id');
                $table->index('resgatado');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bonus_adesaos');
    }
};
