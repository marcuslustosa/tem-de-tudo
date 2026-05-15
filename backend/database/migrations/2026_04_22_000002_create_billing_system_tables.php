<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cria sistema completo de cobranca de empresas.
     */
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->integer('monthly_price_cents');
            $table->json('features');
            $table->integer('max_users')->nullable();
            $table->integer('max_transactions_per_month')->nullable();
            $table->integer('trial_days')->default(14);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('empresas')->onDelete('cascade');
            $table->foreignId('subscription_plan_id')->constrained()->onDelete('restrict');
            $table->enum('status', ['trial', 'active', 'past_due', 'suspended', 'canceled'])->default('trial')->index();
            $table->date('started_at');
            $table->date('trial_ends_at')->nullable();
            $table->date('current_period_start');
            $table->date('current_period_end');
            $table->date('canceled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->integer('billing_day')->default(1);
            $table->integer('grace_period_days')->default(7);
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index('current_period_end');
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->constrained('empresas')->onDelete('cascade');
            $table->string('invoice_number')->unique();
            $table->integer('amount_cents');
            $table->integer('discount_cents')->default(0);
            $table->integer('total_cents');
            $table->enum('status', ['pending', 'paid', 'overdue', 'canceled', 'refunded'])->default('pending')->index();
            $table->date('due_date');
            $table->date('paid_at')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_id')->nullable();
            $table->string('payment_url')->nullable();
            $table->json('payment_metadata')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index('due_date');
        });

        Schema::create('billing_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->constrained('empresas')->onDelete('cascade');
            $table->enum('type', [
                'reminder_3_days',
                'reminder_1_day',
                'due_date',
                'overdue_3_days',
                'overdue_7_days',
            ]);
            $table->string('channel');
            $table->boolean('sent')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['invoice_id', 'type']);
        });

        $this->seedDefaultPlans();
    }

    protected function seedDefaultPlans(): void
    {
        foreach ([
            [
                'name' => 'basic',
                'display_name' => 'Plano Basico',
                'description' => 'Ideal para pequenos estabelecimentos',
                'monthly_price_cents' => 9900,
                'features' => json_encode([
                    'Ate 500 transacoes/mes',
                    'Ate 1.000 clientes cadastrados',
                    'App mobile cliente',
                    'QR Code para check-in',
                    'Relatorios basicos',
                    'Suporte por email',
                ]),
                'max_users' => 1000,
                'max_transactions_per_month' => 500,
                'trial_days' => 14,
            ],
            [
                'name' => 'professional',
                'display_name' => 'Plano Profissional',
                'description' => 'Para empresas em crescimento',
                'monthly_price_cents' => 29900,
                'features' => json_encode([
                    'Ate 5.000 transacoes/mes',
                    'Clientes ilimitados',
                    'Campanhas e multiplicadores',
                    'Push notifications',
                    'Relatorios avancados',
                    'Integracao com PDV',
                    'Suporte prioritario',
                ]),
                'max_users' => null,
                'max_transactions_per_month' => 5000,
                'trial_days' => 14,
            ],
            [
                'name' => 'enterprise',
                'display_name' => 'Plano Enterprise',
                'description' => 'Solucao completa para grandes redes',
                'monthly_price_cents' => 99900,
                'features' => json_encode([
                    'Transacoes ilimitadas',
                    'Clientes ilimitados',
                    'Multi-lojas',
                    'API completa',
                    'White label',
                    'SLA 99.9%',
                    'Account manager dedicado',
                    'Customizacoes',
                ]),
                'max_users' => null,
                'max_transactions_per_month' => null,
                'trial_days' => 30,
            ],
        ] as $plan) {
            DB::table('subscription_plans')->insert(array_merge($plan, [
                // O default da coluna cobre o "true" sem forcar cast boolean no PostgreSQL.
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_notifications');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('subscription_plans');
    }
};
