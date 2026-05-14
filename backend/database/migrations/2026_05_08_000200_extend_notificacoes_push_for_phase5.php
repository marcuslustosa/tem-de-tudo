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
            if (!Schema::hasColumn('notificacoes_push', 'promocao_id')) {
                $table->unsignedBigInteger('promocao_id')->nullable()->after('empresa_id');
            }

            if (!Schema::hasColumn('notificacoes_push', 'status')) {
                $table->string('status', 30)->default('pending')->after('imagem');
            }

            if (!Schema::hasColumn('notificacoes_push', 'erro')) {
                $table->text('erro')->nullable()->after('status');
            }
        });

        Schema::table('notificacoes_push', function (Blueprint $table) {
            $foreignName = 'notificacoes_push_promocao_id_foreign';
            $indexName = 'notificacoes_push_empresa_tipo_status_idx';

            if (Schema::hasColumn('notificacoes_push', 'promocao_id')) {
                $table->index(['empresa_id', 'tipo', 'status'], $indexName);
                $table->foreign('promocao_id', $foreignName)->references('id')->on('promocoes')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('notificacoes_push')) {
            return;
        }

        Schema::table('notificacoes_push', function (Blueprint $table) {
            try {
                $table->dropForeign('notificacoes_push_promocao_id_foreign');
            } catch (\Throwable) {
            }

            try {
                $table->dropIndex('notificacoes_push_empresa_tipo_status_idx');
            } catch (\Throwable) {
            }

            $columns = [];
            foreach (['promocao_id', 'status', 'erro'] as $column) {
                if (Schema::hasColumn('notificacoes_push', $column)) {
                    $columns[] = $column;
                }
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
