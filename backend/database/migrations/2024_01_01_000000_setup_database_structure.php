<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // No PostgreSQL, o CASCADE no DROP já cuida das dependências
        $tables = [
            'users',
            'password_reset_tokens',
            'sessions',
            'failed_jobs',
            'jobs',
            'empresas',
            'pontos',
            'check_ins',
            'coupons',
            'qr_codes',
            'audit_logs',
            'push_notifications',
            'migrations'
        ];

        // Para PostgreSQL no Render, vamos apenas criar as tabelas sem tentar dropar
        // DB::statement('SET session_replication_role = replica');

        // foreach ($tables as $table) {
        //     DB::statement("DROP TABLE IF EXISTS $table CASCADE");
        // }

        // 1. Cria tabela users (base do sistema) - apenas se não existir
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->integer('pontos')->default(0);
                $table->integer('pontos_pendentes')->default(0);
                $table->string('nivel')->default('Bronze');
                $table->string('perfil')->default('cliente'); // cliente, empresa, admin
                $table->string('telefone')->nullable();
                $table->string('status')->default('ativo');
                $table->string('fcm_token')->nullable();
                $table->boolean('email_notifications')->default(true);
                $table->boolean('points_notifications')->default(true);
                $table->boolean('security_notifications')->default(true);
                $table->boolean('promotional_notifications')->default(false);
                $table->timestamp('ultimo_login')->nullable();
                $table->string('ip_ultimo_login')->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
        }

        // 2. Cria tabela password_reset_tokens - apenas se não existir
        if (!Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        // 3. Cria tabela sessions - apenas se não existir
        if (!Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->text('payload');
                $table->integer('last_activity')->index();
            });
        }

        // 4. Cria tabelas de jobs - apenas se não existir
        if (!Schema::hasTable('jobs')) {
            Schema::create('jobs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('queue')->index();
                $table->longText('payload');
                $table->unsignedTinyInteger('attempts');
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
            });
        }

        if (!Schema::hasTable('failed_jobs')) {
            Schema::create('failed_jobs', function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->text('connection');
                $table->text('queue');
                $table->longText('payload');
                $table->longText('exception');
                $table->timestamp('failed_at')->useCurrent();
            });
        }

        // 5. Cria tabela empresas - apenas se não existir
        if (!Schema::hasTable('empresas')) {
            Schema::create('empresas', function (Blueprint $table) {
                $table->id();
                $table->string('nome');
                $table->string('endereco');
                $table->string('telefone');
                $table->string('cnpj')->unique();
                $table->string('logo')->nullable();
                $table->text('descricao')->nullable();
                $table->decimal('points_multiplier', 3, 2)->default(1.00);
                $table->boolean('ativo')->default(true);
                $table->foreignId('owner_id')->references('id')->on('users')->onDelete('cascade');
                $table->timestamps();

                $table->index(['ativo', 'created_at']);
                $table->index('cnpj');
            });
        }

        // 6. Cria tabela qr_codes - apenas se não existir (movida para antes de check_ins)
        if (!Schema::hasTable('qr_codes')) {
            Schema::create('qr_codes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
                $table->string('name');
                $table->string('code')->unique();
                $table->text('location')->nullable();
                $table->boolean('active')->default(true);
                $table->json('active_offers')->nullable();
                $table->integer('usage_count')->default(0);
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();

                $table->index(['empresa_id', 'active']);
            });
        }

        // 7. Cria tabela check_ins - apenas se não existir
        if (!Schema::hasTable('check_ins')) {
            Schema::create('check_ins', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
                $table->foreignId('qr_code_id')->nullable()->references('id')->on('qr_codes')->onDelete('set null');
                $table->decimal('valor_compra', 10, 2);
                $table->integer('pontos_calculados');
                $table->string('foto_cupom')->nullable();
                $table->decimal('latitude', 10, 8)->nullable();
                $table->decimal('longitude', 11, 8)->nullable();
                $table->text('observacoes')->nullable();
                $table->string('status')->default('pending'); // pending, approved, rejected
                $table->string('codigo_validacao')->nullable();
                $table->timestamp('aprovado_em')->nullable();
                $table->foreignId('aprovado_por')->nullable()->references('id')->on('users')->onDelete('set null');
                $table->timestamp('rejeitado_em')->nullable();
                $table->foreignId('rejeitado_por')->nullable()->references('id')->on('users')->onDelete('set null');
                $table->text('motivo_rejeicao')->nullable();
                $table->boolean('bonus_applied')->default(false);
                $table->timestamps();

                $table->index(['status', 'created_at']);
                $table->index(['user_id', 'created_at']);
                $table->index(['empresa_id', 'created_at']);
            });
        }

        // 8. Cria tabela coupons - apenas se não existir
        if (!Schema::hasTable('coupons')) {
            Schema::create('coupons', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('empresa_id')->nullable()->references('id')->on('empresas')->onDelete('set null');
                $table->foreignId('checkin_id')->nullable()->references('id')->on('check_ins')->onDelete('set null');
                $table->string('codigo')->unique();
                $table->string('tipo')->default('discount');
                $table->string('descricao');
                $table->integer('custo_pontos');
                $table->decimal('valor_desconto', 10, 2)->nullable();
                $table->decimal('porcentagem_desconto', 5, 2)->nullable();
                $table->string('status')->default('active'); // active, used, expired
                $table->timestamp('expira_em')->nullable();
                $table->timestamp('usado_em')->nullable();
                $table->json('dados_extra')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
                $table->index('codigo');
                $table->index('created_at');
            });
        }

        // 9. Cria tabela pontos - apenas se não existir
        if (!Schema::hasTable('pontos')) {
            Schema::create('pontos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
                $table->foreignId('checkin_id')->nullable()->references('id')->on('check_ins')->onDelete('set null');
                $table->foreignId('coupon_id')->nullable()->references('id')->on('coupons')->onDelete('set null');
                $table->integer('pontos');
                $table->string('descricao');
                $table->string('tipo'); // earn, redeem, bonus, adjustment
                $table->timestamps();

                $table->index(['user_id', 'created_at']);
                $table->index(['empresa_id', 'created_at']);
            });
        }

        // 10. Cria tabela audit_logs - apenas se não existir (removida referência a admins)
        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
                $table->unsignedBigInteger('admin_id')->nullable();
                $table->string('action');
                $table->string('ip_address')->nullable();
                $table->text('user_agent')->nullable();
                $table->json('details')->nullable();
                $table->timestamp('created_at');

                $table->index(['action', 'created_at']);
                $table->index(['user_id', 'created_at']);
            });
        }

        // 11. Cria tabela push_notifications - apenas se não existir (removida referência a admins)
        if (!Schema::hasTable('push_notifications')) {
            Schema::create('push_notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
                $table->unsignedBigInteger('admin_id')->nullable();
                $table->string('user_type')->default('client'); // client, admin
                $table->string('title');
                $table->text('body');
                $table->json('data')->nullable();
                $table->string('type')->default('general');
                $table->string('fcm_token')->nullable();
                $table->boolean('is_sent')->default(false);
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('read_at')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'user_type']);
                $table->index(['is_sent', 'created_at']);
            });
        }

        // Reativa as restrições (comentado para Render)
        // DB::statement('SET session_replication_role = DEFAULT');
    }

    public function down()
    {
        $tables = [
            'push_notifications',
            'audit_logs',
            'qr_codes',
            'coupons',
            'check_ins',
            'pontos',
            'empresas',
            'failed_jobs',
            'jobs',
            'sessions',
            'password_reset_tokens',
            'users'
        ];

        // Ordem reversa para respeitar dependências
        foreach (array_reverse($tables) as $table) {
            Schema::dropIfExists($table);
        }
    }
};