<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ============ 1. STREAK: colunas na tabela users ============
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'streak_atual')) {
                $table->unsignedInteger('streak_atual')->default(0)->after('dias_consecutivos');
            }
            if (!Schema::hasColumn('users', 'streak_maximo')) {
                $table->unsignedInteger('streak_maximo')->default(0)->after('streak_atual');
            }
        });

        // ============ 2. DESAFIOS / MISSÕES ============
        Schema::create('desafios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->onDelete('cascade');
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->enum('tipo', ['checkins', 'pontos', 'resgates', 'streak', 'indicacoes'])->default('checkins');
            $table->unsignedInteger('meta')->default(1);
            $table->unsignedInteger('recompensa_pontos')->default(0);
            $table->string('recompensa_descricao')->nullable();
            $table->dateTime('data_inicio');
            $table->dateTime('data_fim');
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index(['ativo', 'data_inicio', 'data_fim']);
        });

        Schema::create('desafio_progresso', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('desafio_id')->constrained('desafios')->onDelete('cascade');
            $table->unsignedInteger('progresso_atual')->default(0);
            $table->boolean('concluido')->default(false);
            $table->timestamp('concluido_em')->nullable();
            $table->boolean('recompensa_dada')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'desafio_id']);
            $table->index(['user_id', 'concluido']);
        });

        // ============ 3. NPS pós-resgate ============
        Schema::create('nps_respostas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->onDelete('set null');
            $table->foreignId('promocao_id')->nullable();
            $table->unsignedTinyInteger('nota'); // 0-10
            $table->text('comentario')->nullable();
            $table->string('contexto')->default('resgate'); // resgate, checkin, geral
            $table->timestamps();

            $table->index(['empresa_id', 'created_at']);
            $table->index(['user_id', 'empresa_id']);
        });

        // ============ 4. SEGMENTAÇÃO de clientes ============
        Schema::create('segmentos', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->json('criterios'); // {nivel: ['ouro','platina'], pontos_min: 100, ...}
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('segmento_usuarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('segmento_id')->constrained('segmentos')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('adicionado_em')->useCurrent();

            $table->unique(['segmento_id', 'user_id']);
        });

        // ============ 5. WEBHOOK de saída ============
        Schema::create('webhooks_saida', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->onDelete('cascade');
            $table->string('url');
            $table->string('segredo')->nullable(); // HMAC secret
            $table->json('eventos'); // ['checkin', 'resgate', 'nivel_up', ...]
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_id')->constrained('webhooks_saida')->onDelete('cascade');
            $table->string('evento');
            $table->json('payload');
            $table->unsignedSmallInteger('status_http')->nullable();
            $table->text('resposta')->nullable();
            $table->boolean('sucesso')->default(false);
            $table->unsignedTinyInteger('tentativas')->default(1);
            $table->timestamp('enviado_em')->useCurrent();
        });

        // ============ 6. AJUSTE MANUAL de pontos (log) ============
        Schema::create('ajustes_pontos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('admin_id')->nullable()->constrained('users')->onDelete('set null');
            $table->integer('pontos'); // positivo = crédito, negativo = débito
            $table->string('motivo');
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        // ============ 7. PONTOS UNIFICADOS multi-empresa (saldo rede) ============
        // Adiciona flag de rede compartilhada nas empresas
        Schema::table('empresas', function (Blueprint $table) {
            if (!Schema::hasColumn('empresas', 'rede_compartilhada')) {
                $table->boolean('rede_compartilhada')->default(false)->after('ativo');
            }
            if (!Schema::hasColumn('empresas', 'rede_nome')) {
                $table->string('rede_nome')->nullable()->after('rede_compartilhada');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['streak_atual', 'streak_maximo']);
        });

        Schema::dropIfExists('desafio_progresso');
        Schema::dropIfExists('desafios');
        Schema::dropIfExists('nps_respostas');
        Schema::dropIfExists('segmento_usuarios');
        Schema::dropIfExists('segmentos');
        Schema::dropIfExists('webhook_logs');
        Schema::dropIfExists('webhooks_saida');
        Schema::dropIfExists('ajustes_pontos');

        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn(['rede_compartilhada', 'rede_nome']);
        });
    }
};
