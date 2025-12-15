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
        if (!Schema::hasTable('cartao_fidelidades')) {
            Schema::create('cartao_fidelidades', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('categoria');
                $table->integer('carimbos_atual')->default(0);
                $table->integer('carimbos_necessarios')->default(10);
                $table->date('validade')->nullable();
                $table->timestamps();
                
                // Índices
                $table->index('user_id');
                $table->index('categoria');
                $table->index('validade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cartao_fidelidades');
    }
};
