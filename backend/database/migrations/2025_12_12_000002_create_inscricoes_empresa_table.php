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
        Schema::create('inscricoes_empresa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Cliente
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade'); // Empresa
            $table->timestamp('data_inscricao')->useCurrent(); // Quando se inscreveu
            $table->timestamp('ultima_visita')->nullable(); // Última vez que ganhou pontos
            $table->boolean('bonus_adesao_resgatado')->default(false); // Já resgatou bônus de adesão?
            $table->timestamps();
            
            // Cliente só pode se inscrever 1x em cada empresa
            $table->unique(['user_id', 'empresa_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inscricoes_empresa');
    }
};
