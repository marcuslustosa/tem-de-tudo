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
        Schema::create('pontos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->onDelete('set null');
            $table->foreignId('check_in_id')->nullable()->constrained('check_ins')->onDelete('set null');
            $table->integer('pontos');
            $table->enum('tipo', ['ganho', 'resgate', 'bonus', 'ajuste'])->default('ganho');
            $table->string('descricao');
            $table->decimal('valor_original', 10, 2)->nullable();
            $table->decimal('multiplicador_usado', 3, 1)->default(1.0);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['empresa_id', 'created_at']);
            $table->index('tipo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pontos');
    }
};