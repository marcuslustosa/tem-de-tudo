<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'retry_count')) {
                $table->unsignedTinyInteger('retry_count')->default(0)->after('payment_metadata');
            }

            if (!Schema::hasColumn('invoices', 'last_retry_at')) {
                $table->timestamp('last_retry_at')->nullable()->after('retry_count');
            }

            if (!Schema::hasColumn('invoices', 'next_retry_at')) {
                $table->timestamp('next_retry_at')->nullable()->after('last_retry_at');
            }

            if (!Schema::hasColumn('invoices', 'last_failure_reason')) {
                $table->text('last_failure_reason')->nullable()->after('next_retry_at');
            }

            if (!Schema::hasColumn('invoices', 'reconciliation_status')) {
                $table->string('reconciliation_status', 30)->default('pending')->after('last_failure_reason');
            }

            if (!Schema::hasColumn('invoices', 'external_status')) {
                $table->string('external_status', 50)->nullable()->after('reconciliation_status');
            }

            if (!Schema::hasColumn('invoices', 'reconciled_at')) {
                $table->timestamp('reconciled_at')->nullable()->after('external_status');
            }

            $table->index(['status', 'next_retry_at'], 'idx_invoices_status_next_retry');
            $table->index(['reconciliation_status', 'updated_at'], 'idx_invoices_reconciliation_status');
        });

        Schema::create('billing_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('empresas')->onDelete('cascade');
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->string('event_type', 80);
            $table->string('level', 20)->default('info');
            $table->json('payload')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['company_id', 'event_type']);
            $table->index('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_events');

        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'next_retry_at')) {
                $table->dropIndex('idx_invoices_status_next_retry');
            }

            if (Schema::hasColumn('invoices', 'reconciliation_status')) {
                $table->dropIndex('idx_invoices_reconciliation_status');
            }

            $columns = [
                'retry_count',
                'last_retry_at',
                'next_retry_at',
                'last_failure_reason',
                'reconciliation_status',
                'external_status',
                'reconciled_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('invoices', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

