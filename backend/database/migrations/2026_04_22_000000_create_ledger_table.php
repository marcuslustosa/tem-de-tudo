<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cria tabela ledger - Fonte única e imutável de verdade para pontos.
     * 
     * Princípios:
     * - Append-only (nunca UPDATE ou DELETE)
     * - Idempotência via idempotency_key
     * - Balance snapshot em cada transação
     * - Auditoria completa com metadata
     */
    public function up(): void
    {
        Schema::create('ledger', function (Blueprint $table) {
            $table->id();
            
            // Idempotência - previne duplicação em retry/webhook
            $table->string('idempotency_key')->unique();
            
            // Usuário e empresa
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->nullable()->constrained('empresas')->onDelete('set null');
            
            // Tipo de transação
            $table->enum('transaction_type', [
                'earn',           // Ganho normal (compra, check-in)
                'earn_bonus',     // Bônus (aniversário, campanha)
                'redeem',         // Resgate confirmado
                'reserved',       // Pontos reservados (PDV pendente)
                'released',       // Liberação de reserva (cancelamento)
                'adjustment',     // Ajuste manual (admin)
                'reversal',       // Estorno/reversão
                'expiration',     // Expiração de pontos
                'migration',      // Migração de sistema legado
            ])->index();
            
            // Valores
            $table->integer('points');  // Pode ser negativo (débito)
            $table->integer('balance_before')->default(0);  // Saldo anterior
            $table->integer('balance_after')->default(0);   // Saldo após transação
            
            // Descrição e contexto
            $table->string('description', 500);
            $table->json('metadata')->nullable();  // Dados variáveis (empresa_nome, campanha_id, etc)
            
            // Rastreabilidade
            $table->foreignId('related_ledger_id')->nullable()->constrained('ledger')->onDelete('set null'); // Para reversões
            $table->string('source')->default('system'); // system, admin, webhook, api
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null'); // Quem criou
            
            // Timestamps (imutável - apenas created_at)
            $table->timestamp('created_at')->useCurrent();
            
            // Índices para performance
            $table->index(['user_id', 'created_at']);
            $table->index(['company_id', 'created_at']);
            $table->index(['transaction_type', 'created_at']);
            $table->index('created_at');
        });
        
        // View materializada para saldo atual (opcional, para performance extrema)
        DB::statement('
            CREATE VIEW ledger_balances AS
            SELECT 
                user_id,
                MAX(id) as last_ledger_id,
                MAX(balance_after) as current_balance,
                MAX(created_at) as last_transaction_at
            FROM ledger
            GROUP BY user_id
        ');
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS ledger_balances');
        Schema::dropIfExists('ledger');
    }
};
