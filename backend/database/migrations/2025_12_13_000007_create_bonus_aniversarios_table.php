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
        if (!Schema::hasTable('bonus_aniversarios')) {
            Schema::create('bonus_aniversarios', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->integer('pontos')->default(100);
                $table->date('data_resgate');
                $table->integer('ano');
                $table->timestamps();
                
                // Índices
                $table->index('user_id');
                $table->index('data_resgate');
                $table->unique(['user_id', 'ano']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bonus_aniversarios');
    }
};
