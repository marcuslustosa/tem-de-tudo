<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('redemption_intents', function (Blueprint $table) {
            $table->id();
            $table->string('intent_id')->unique(); // UUID público
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('empresas')->onDelete('cascade');
            $table->foreignId('reserved_ledger_id')->nullable()->constrained('ledger')->onDelete('set null');
            $table->foreignId('confirmed_ledger_id')->nullable()->constrained('ledger')->onDelete('set null');
            $table->foreignId('reversal_ledger_id')->nullable()->constrained('ledger')->onDelete('set null');
            
            $table->integer('points_requested'); // Pontos solicitados
            $table->integer('points_confirmed')->nullable(); // Pode ser diferente se ajustado
            $table->string('status')->default('pending'); // pending, reserved, confirmed, canceled, reversed
            
            $table->string('redemption_type')->default('product'); // product, discount, cashback
            $table->json('metadata')->nullable(); // Produto, valor, PDV operator, etc
            
            $table->foreignId('pdv_operator_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('pdv_terminal_id')->nullable();
            
            $table->timestamp('requested_at');
            $table->timestamp('reserved_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // Reserva expira em X minutos
            
            $table->text('cancellation_reason')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
            
            $table->index(['status', 'created_at']);
            $table->index('user_id');
            $table->index('company_id');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redemption_intents');
    }
};
