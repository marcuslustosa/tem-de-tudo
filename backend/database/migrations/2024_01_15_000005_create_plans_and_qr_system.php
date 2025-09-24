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
        // Tabela de planos
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->decimal('price', 10, 2);
            $table->integer('establishments_limit')->nullable(); // null = ilimitado
            $table->integer('checkins_limit')->nullable(); // null = ilimitado
            $table->integer('benefits_limit')->nullable(); // null = ilimitado
            $table->decimal('points_multiplier', 3, 2)->default(1.0);
            $table->json('features'); // array de features incluídas
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // Tabela de assinaturas
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('plan_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['active', 'canceled', 'suspended', 'expired'])->default('active');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->timestamp('canceled_at')->nullable();
            $table->decimal('amount', 10, 2);
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        // Tabela de QR Codes dinâmicos
        Schema::create('qr_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->string('code', 32)->unique(); // código único do QR
            $table->string('name')->nullable(); // nome da mesa/local (ex: Mesa 1, Balcão)
            $table->string('location')->nullable(); // localização específica
            $table->json('active_offers')->nullable(); // ofertas ativas neste QR
            $table->integer('usage_count')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'active']);
            $table->index('code');
        });

        // Atualizar tabela empresas
        Schema::table('empresas', function (Blueprint $table) {
            $table->foreignId('subscription_id')->nullable()->after('id')->constrained()->onDelete('set null');
            $table->decimal('points_multiplier', 3, 2)->default(1.0)->after('categoria');
            $table->json('benefits_config')->nullable()->after('points_multiplier'); // configuração dos benefícios
            $table->boolean('active')->default(true)->after('benefits_config');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropForeign(['subscription_id']);
            $table->dropColumn(['subscription_id', 'points_multiplier', 'benefits_config', 'active']);
        });

        Schema::dropIfExists('qr_codes');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');
    }
};