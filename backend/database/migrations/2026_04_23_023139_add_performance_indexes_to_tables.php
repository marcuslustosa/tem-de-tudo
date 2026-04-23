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
        // Índices na tabela pontos (queries frequentes por user_id e empresa_id)
        if (Schema::hasTable('pontos')) {
            Schema::table('pontos', function (Blueprint $table) {
                $table->index('user_id', 'idx_pontos_user_id');
                $table->index('empresa_id', 'idx_pontos_empresa_id');
                $table->index(['user_id', 'empresa_id'], 'idx_pontos_user_empresa');
                $table->index('tipo', 'idx_pontos_tipo');
                $table->index('created_at', 'idx_pontos_created_at');
            });
        }

        // Índices na tabela empresas (filtros por status, categoria, ativo)
        if (Schema::hasTable('empresas')) {
            Schema::table('empresas', function (Blueprint $table) {
                $table->index('ativo', 'idx_empresas_ativo');
                $table->index('status', 'idx_empresas_status');
                $table->index('categoria_id', 'idx_empresas_categoria');
                $table->index(['ativo', 'status'], 'idx_empresas_ativo_status');
            });
        }

        // Índices na tabela check_ins (queries por empresa e user)
        if (Schema::hasTable('check_ins')) {
            Schema::table('check_ins', function (Blueprint $table) {
                $table->index('empresa_id', 'idx_checkins_empresa_id');
                $table->index('user_id', 'idx_checkins_user_id');
                $table->index('created_at', 'idx_checkins_created_at');
                $table->index(['empresa_id', 'created_at'], 'idx_checkins_empresa_date');
            });
        }

        // Índices na tabela ledger (append-only, queries por user)
        if (Schema::hasTable('ledger')) {
            Schema::table('ledger', function (Blueprint $table) {
                $table->index('user_id', 'idx_ledger_user_id');
                $table->index('company_id', 'idx_ledger_company_id');
                $table->index('transaction_type', 'idx_ledger_type');
                $table->index('created_at', 'idx_ledger_created_at');
                $table->index(['user_id', 'created_at'], 'idx_ledger_user_date');
            });
        }

        // Índices na tabela redemption_intents (PDV, queries por company_id e status)
        if (Schema::hasTable('redemption_intents')) {
            Schema::table('redemption_intents', function (Blueprint $table) {
                $table->index('company_id', 'idx_redemption_company_id');
                $table->index('user_id', 'idx_redemption_user_id');
                $table->index('status', 'idx_redemption_status');
                $table->index('expires_at', 'idx_redemption_expires_at');
                $table->index(['company_id', 'status'], 'idx_redemption_company_status');
            });
        }

        // Índices na tabela produtos (filtros por empresa e categoria)
        if (Schema::hasTable('produtos')) {
            Schema::table('produtos', function (Blueprint $table) {
                $table->index('empresa_id', 'idx_produtos_empresa_id');
                $table->index('categoria', 'idx_produtos_categoria');
                $table->index('ativo', 'idx_produtos_ativo');
                $table->index(['empresa_id', 'ativo'], 'idx_produtos_empresa_ativo');
            });
        }

        // Índices na tabela promocoes (filtros por empresa e status)
        if (Schema::hasTable('promocoes')) {
            Schema::table('promocoes', function (Blueprint $table) {
                $table->index('empresa_id', 'idx_promocoes_empresa_id');
                $table->index('ativo', 'idx_promocoes_ativo');
                $table->index('status', 'idx_promocoes_status');
                $table->index(['empresa_id', 'ativo', 'status'], 'idx_promocoes_empresa_ativo_status');
            });
        }

        // Índices na tabela avaliacoes (queries por empresa)
        if (Schema::hasTable('avaliacoes')) {
            Schema::table('avaliacoes', function (Blueprint $table) {
                $table->index('empresa_id', 'idx_avaliacoes_empresa_id');
                $table->index('user_id', 'idx_avaliacoes_user_id');
                $table->index('created_at', 'idx_avaliacoes_created_at');
            });
        }

        // Índices na tabela badges_usuarios (queries por user_id)
        if (Schema::hasTable('badges_usuarios')) {
            Schema::table('badges_usuarios', function (Blueprint $table) {
                $table->index('user_id', 'idx_badges_user_id');
                $table->index('badge_id', 'idx_badges_badge_id');
                $table->index('earned_at', 'idx_badges_earned_at');
            });
        }

        // Índices na tabela qr_codes (queries por empresa_id e code)
        if (Schema::hasTable('qr_codes')) {
            Schema::table('qr_codes', function (Blueprint $table) {
                $table->index('empresa_id', 'idx_qr_empresa_id');
                $table->index('active', 'idx_qr_active');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remover índices na ordem inversa
        if (Schema::hasTable('qr_codes')) {
            Schema::table('qr_codes', function (Blueprint $table) {
                $table->dropIndex('idx_qr_empresa_id');
                $table->dropIndex('idx_qr_active');
            });
        }

        if (Schema::hasTable('badges_usuarios')) {
            Schema::table('badges_usuarios', function (Blueprint $table) {
                $table->dropIndex('idx_badges_user_id');
                $table->dropIndex('idx_badges_badge_id');
                $table->dropIndex('idx_badges_earned_at');
            });
        }

        if (Schema::hasTable('avaliacoes')) {
            Schema::table('avaliacoes', function (Blueprint $table) {
                $table->dropIndex('idx_avaliacoes_empresa_id');
                $table->dropIndex('idx_avaliacoes_user_id');
                $table->dropIndex('idx_avaliacoes_created_at');
            });
        }

        if (Schema::hasTable('promocoes')) {
            Schema::table('promocoes', function (Blueprint $table) {
                $table->dropIndex('idx_promocoes_empresa_id');
                $table->dropIndex('idx_promocoes_ativo');
                $table->dropIndex('idx_promocoes_status');
                $table->dropIndex('idx_promocoes_empresa_ativo_status');
            });
        }

        if (Schema::hasTable('produtos')) {
            Schema::table('produtos', function (Blueprint $table) {
                $table->dropIndex('idx_produtos_empresa_id');
                $table->dropIndex('idx_produtos_categoria');
                $table->dropIndex('idx_produtos_ativo');
                $table->dropIndex('idx_produtos_empresa_ativo');
            });
        }

        if (Schema::hasTable('redemption_intents')) {
            Schema::table('redemption_intents', function (Blueprint $table) {
                $table->dropIndex('idx_redemption_company_id');
                $table->dropIndex('idx_redemption_user_id');
                $table->dropIndex('idx_redemption_status');
                $table->dropIndex('idx_redemption_expires_at');
                $table->dropIndex('idx_redemption_company_status');
            });
        }

        if (Schema::hasTable('ledger')) {
            Schema::table('ledger', function (Blueprint $table) {
                $table->dropIndex('idx_ledger_user_id');
                $table->dropIndex('idx_ledger_company_id');
                $table->dropIndex('idx_ledger_type');
                $table->dropIndex('idx_ledger_created_at');
                $table->dropIndex('idx_ledger_user_date');
            });
        }

        if (Schema::hasTable('check_ins')) {
            Schema::table('check_ins', function (Blueprint $table) {
                $table->dropIndex('idx_checkins_empresa_id');
                $table->dropIndex('idx_checkins_user_id');
                $table->dropIndex('idx_checkins_created_at');
                $table->dropIndex('idx_checkins_empresa_date');
            });
        }

        if (Schema::hasTable('empresas')) {
            Schema::table('empresas', function (Blueprint $table) {
                $table->dropIndex('idx_empresas_ativo');
                $table->dropIndex('idx_empresas_status');
                $table->dropIndex('idx_empresas_categoria');
                $table->dropIndex('idx_empresas_ativo_status');
            });
        }

        if (Schema::hasTable('pontos')) {
            Schema::table('pontos', function (Blueprint $table) {
                $table->dropIndex('idx_pontos_user_id');
                $table->dropIndex('idx_pontos_empresa_id');
                $table->dropIndex('idx_pontos_user_empresa');
                $table->dropIndex('idx_pontos_tipo');
                $table->dropIndex('idx_pontos_created_at');
            });
        }
    }
};
