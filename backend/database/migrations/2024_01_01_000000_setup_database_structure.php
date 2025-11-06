<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Desativa verificação de chaves estrangeiras
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Drop todas as tabelas existentes com CASCADE para limpar tudo
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

        foreach ($tables as $table) {
            DB::statement("DROP TABLE IF EXISTS $table CASCADE");
        }

        // 1. Cria tabela users (base do sistema)
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->integer('pontos')->default(0);
            $table->integer('pontos_pendentes')->default(0);
            $table->string('nivel')->default('Bronze');
            $table->string('type')->default('client');
            $table->rememberToken();
            $table->timestamps();
        });

        // 2. Cria tabela password_reset_tokens
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // 3. Cria tabela sessions
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('payload');
            $table->integer('last_activity')->index();
        });

        // 4. Cria tabelas de jobs
        Schema::create('jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });

        // 5. Cria tabela empresas
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('cnpj')->unique();
            $table->text('address');
            $table->string('phone');
            $table->string('email');
            $table->json('photos')->nullable();
            $table->json('services')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('qr_code')->nullable();
            $table->string('plan')->default('free');
            $table->json('settings')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // 6. Cria tabela pontos
        Schema::create('pontos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->integer('pontos')->default(0);
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
            $table->index(['empresa_id', 'created_at']);
        });

        // 7. Cria tabela check_ins
        Schema::create('check_ins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->string('status')->default('pending');
            $table->integer('pontos_calculados')->default(0);
            $table->timestamps();
            
            $table->index(['status', 'created_at']);
        });

        // 8. Cria tabela coupons
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->integer('custo_pontos')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('created_at');
        });

        // 9. Cria tabela qr_codes
        Schema::create('qr_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->string('code')->unique();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // 10. Cria tabela audit_logs
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('action');
            $table->json('details')->nullable();
            $table->timestamp('created_at');
            
            $table->index(['action', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });

        // 11. Cria tabela push_notifications
        Schema::create('push_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('body');
            $table->json('data')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        // Reativa verificação de chaves estrangeiras
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
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