<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Cria sistema completo de cobrança de empresas.
     */
    public function up(): void
    {
        // 1. Tabela de planos de assinatura
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();  // basic, professional, enterprise
            $table->string('display_name');    // Nome exibido
            $table->text('description')->nullable();
            $table->integer('monthly_price_cents');  // Preço em centavos
            $table->json('features');  // Lista de funcionalidades
            $table->integer('max_users')->nullable();  // Limite de usuários
            $table->integer('max_transactions_per_month')->nullable();  // Limite de transações
            $table->integer('trial_days')->default(14);  // Dias de trial
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Tabela de assinaturas de empresas
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('empresas')->onDelete('cascade');
            $table->foreignId('subscription_plan_id')->constrained()->onDelete('restrict');
            
            $table->enum('status', [
                'trial',        // Em período de trial
                'active',       // Ativa e paga
                'past_due',     // Atrasada (1-7 dias)
                'suspended',    // Suspensa (7+ dias)
                'canceled',     // Cancelada
            ])->default('trial')->index();
            
            $table->date('started_at');  // Data de início
            $table->date('trial_ends_at')->nullable();  // Fim do trial
            $table->date('current_period_start');
            $table->date('current_period_end');
            $table->date('canceled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            
            $table->integer('billing_day')->default(1);  // Dia do mês para cobrança (1-28)
            $table->integer('grace_period_days')->default(7);  // Dias de tolerância após vencimento
            
            $table->timestamps();
            
            $table->index(['company_id', 'status']);
            $table->index('current_period_end');
        });

        // 3. Tabela de faturas
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->constrained('empresas')->onDelete('cascade');
            
            $table->string('invoice_number')->unique();  // Número da fatura (ex: INV-2026-001)
            $table->integer('amount_cents');  // Valor em centavos
            $table->integer('discount_cents')->default(0);  // Desconto aplicado
            $table->integer('total_cents');  // Total a pagar
            
            $table->enum('status', [
                'pending',      // Aguardando pagamento
                'paid',         // Paga
                'overdue',      // Vencida
                'canceled',     // Cancelada
                'refunded',     // Reembolsada
            ])->default('pending')->index();
            
            $table->date('due_date');  // Vencimento
            $table->date('paid_at')->nullable();  // Data de pagamento
            $table->string('payment_method')->nullable();  // pix, credit_card, boleto
            $table->string('payment_id')->nullable();  // ID externo (MercadoPago, Stripe)
            $table->string('payment_url')->nullable();  // Link de pagamento
            $table->json('payment_metadata')->nullable();  // Detalhes do pagamento
            
            $table->text('notes')->nullable();  // Observações
            $table->timestamps();
            
            $table->index(['company_id', 'status']);
            $table->index('due_date');
        });

        // 4. Tabela de notificações de cobrança enviadas
        Schema::create('billing_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->constrained('empresas')->onDelete('cascade');
            
            $table->enum('type', [
                'reminder_3_days',   // Lembrete 3 dias antes
                'reminder_1_day',    // Lembrete 1 dia antes
                'due_date',          // No vencimento
                'overdue_3_days',    // 3 dias após vencimento
                'overdue_7_days',    // 7 dias - suspensão
            ]);
            
            $table->string('channel');  // email, push, sms
            $table->boolean('sent')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->text('error')->nullable();
            
            $table->timestamps();
            
            $table->index(['invoice_id', 'type']);
        });

        // 5. Seed de planos padrão
        $this->seedDefaultPlans();
    }

    protected function seedDefaultPlans(): void
    {
        DB::table('subscription_plans')->insert([
            [
                'name' => 'basic',
                'display_name' => 'Plano Básico',
                'description' => 'Ideal para pequenos estabelecimentos',
                'monthly_price_cents' => 9900,  // R$ 99,00
                'features' => json_encode([
                    'Até 500 transações/mês',
                    'Até 1.000 clientes cadastrados',
                    'App mobile cliente',
                    'QR Code para check-in',
                    'Relatórios básicos',
                    'Suporte por email',
                ]),
                'max_users' => 1000,
                'max_transactions_per_month' => 500,
                'trial_days' => 14,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'professional',
                'display_name' => 'Plano Profissional',
                'description' => 'Para empresas em crescimento',
                'monthly_price_cents' => 29900,  // R$ 299,00
                'features' => json_encode([
                    'Até 5.000 transações/mês',
                    'Clientes ilimitados',
                    'Campanhas e multiplicadores',
                    'Push notifications',
                    'Relatórios avançados',
                    'Integração com PDV',
                    'Suporte prioritário',
                ]),
                'max_users' => null,
                'max_transactions_per_month' => 5000,
                'trial_days' => 14,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'enterprise',
                'display_name' => 'Plano Enterprise',
                'description' => 'Solução completa para grandes redes',
                'monthly_price_cents' => 99900,  // R$ 999,00
                'features' => json_encode([
                    'Transações ilimitadas',
                    'Clientes ilimitados',
                    'Multi-lojas',
                    'API completa',
                    'White label',
                    'SLA 99.9%',
                    'Account manager dedicado',
                    'Customizações',
                ]),
                'max_users' => null,
                'max_transactions_per_month' => null,
                'trial_days' => 30,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_notifications');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('subscription_plans');
    }
};
