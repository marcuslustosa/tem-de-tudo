<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('notificacoes_push')) {
            return;
        }

        Schema::table('notificacoes_push', function (Blueprint $table) {
            if (!Schema::hasColumn('notificacoes_push', 'bonus_aniversario_id')) {
                $table->foreignId('bonus_aniversario_id')->nullable()->after('promocao_id');
            }

            if (!Schema::hasColumn('notificacoes_push', 'lembrete_id')) {
                $table->foreignId('lembrete_id')->nullable()->after('bonus_aniversario_id');
            }
        });

        Schema::table('notificacoes_push', function (Blueprint $table) {
            if (Schema::hasColumn('notificacoes_push', 'bonus_aniversario_id')) {
                $table->foreign('bonus_aniversario_id', 'notificacoes_push_bonus_aniversario_id_foreign')
                    ->references('id')
                    ->on('bonus_aniversario')
                    ->nullOnDelete();
                $table->index('bonus_aniversario_id', 'notificacoes_push_bonus_aniversario_id_idx');
            }

            if (Schema::hasColumn('notificacoes_push', 'lembrete_id')) {
                $table->foreign('lembrete_id', 'notificacoes_push_lembrete_id_foreign')
                    ->references('id')
                    ->on('lembretes_ausencia')
                    ->nullOnDelete();
                $table->index('lembrete_id', 'notificacoes_push_lembrete_id_idx');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('notificacoes_push')) {
            return;
        }

        Schema::table('notificacoes_push', function (Blueprint $table) {
            if (Schema::hasColumn('notificacoes_push', 'bonus_aniversario_id')) {
                $table->dropForeign('notificacoes_push_bonus_aniversario_id_foreign');
                $table->dropIndex('notificacoes_push_bonus_aniversario_id_idx');
                $table->dropColumn('bonus_aniversario_id');
            }

            if (Schema::hasColumn('notificacoes_push', 'lembrete_id')) {
                $table->dropForeign('notificacoes_push_lembrete_id_foreign');
                $table->dropIndex('notificacoes_push_lembrete_id_idx');
                $table->dropColumn('lembrete_id');
            }
        });
    }
};
