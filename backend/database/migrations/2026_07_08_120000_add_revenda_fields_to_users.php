<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

/**
 * Campos para o perfil "revenda" (submaster): saldo em R$ (creditos),
 * whatsapp e vencimento do acesso. A prova de falha: qualquer erro e
 * registrado sem derrubar o deploy (Railway/pgsql).
 */
return new class extends Migration
{
    public function up(): void
    {
        try {
            if (!Schema::hasTable('users')) {
                return;
            }
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'creditos')) {
                    $table->decimal('creditos', 10, 2)->default(0)->after('perfil');
                }
                if (!Schema::hasColumn('users', 'whatsapp')) {
                    $table->string('whatsapp', 30)->nullable()->after('telefone');
                }
                if (!Schema::hasColumn('users', 'expires_at')) {
                    $table->timestamp('expires_at')->nullable()->after('creditos');
                }
            });
        } catch (\Throwable $e) {
            Log::warning('add_revenda_fields_to_users falhou: ' . $e->getMessage());
        }
    }

    public function down(): void
    {
        try {
            if (!Schema::hasTable('users')) {
                return;
            }
            Schema::table('users', function (Blueprint $table) {
                foreach (['creditos', 'whatsapp', 'expires_at'] as $col) {
                    if (Schema::hasColumn('users', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        } catch (\Throwable $e) {
            Log::warning('add_revenda_fields_to_users rollback falhou: ' . $e->getMessage());
        }
    }
};
