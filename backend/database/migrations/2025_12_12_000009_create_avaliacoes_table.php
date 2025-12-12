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
        if (!Schema::hasTable('avaliacoes')) {
            Schema::create('avaliacoes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Cliente
                $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
                $table->integer('estrelas'); // 1 a 5
                $table->text('comentario')->nullable();
                $table->timestamps();
                
                // Cliente pode avaliar cada empresa apenas 1x
                $table->unique(['user_id', 'empresa_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('avaliacoes');
    }
};
