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
        Schema::create('qr_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // Código único do QR
            $table->enum('type', ['empresa', 'cliente']); // Tipo: empresa ou cliente
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->onDelete('cascade'); // Se for QR de empresa
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade'); // Se for QR de cliente
            $table->text('qr_image')->nullable(); // Base64 da imagem do QR Code
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            
            // Garantir que apenas um campo (empresa_id OU user_id) esteja preenchido
            $table->index(['type', 'empresa_id']);
            $table->index(['type', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qr_codes');
    }
};
