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
        Schema::create('pagamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('empresa_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('produto_id')->nullable(); // Temporariamente sem foreign key
            
            // Dados do Mercado Pago
            $table->string('mercadopago_payment_id')->nullable()->unique();
            $table->enum('status', [
                'pending',      // Aguardando pagamento
                'approved',     // Aprovado
                'authorized',   // Autorizado
                'in_process',   // Em processamento
                'in_mediation', // Em mediação
                'rejected',     // Rejeitado
                'cancelled',    // Cancelado
                'refunded',     // Estornado
                'charged_back'  // Chargeback
            ])->default('pending');
            
            // Valores (em centavos)
            $table->integer('valor'); // Valor original do produto
            $table->integer('valor_desconto')->default(0); // Desconto aplicado
            $table->integer('valor_final'); // Valor final a pagar
            $table->integer('pontos_gerados')->default(0);
            
            // Método de pagamento
            $table->string('metodo_pagamento')->nullable(); // pix, credit_card, etc
            $table->json('detalhes_pagamento')->nullable(); // Detalhes específicos do MP
            
            // QR Code e link de pagamento
            $table->text('qr_code_data')->nullable();
            $table->text('link_pagamento')->nullable();
            $table->timestamp('data_expiracao')->nullable();
            
            // Eventos de webhook
            $table->json('webhook_events')->nullable();
            
            $table->timestamps();
            
            // Índices
            $table->index(['user_id', 'status']);
            $table->index(['empresa_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index('mercadopago_payment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagamentos');
    }
};