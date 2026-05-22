<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('lembretes_ausencia')) {
            return;
        }

        Schema::table('lembretes_ausencia', function (Blueprint $table) {
            if (!Schema::hasColumn('lembretes_ausencia', 'imagem_url')) {
                $table->string('imagem_url', 2048)->nullable()->after('mensagem');
            }

            if (!Schema::hasColumn('lembretes_ausencia', 'notification_title')) {
                $table->string('notification_title', 80)->nullable()->after('imagem_url');
            }

            if (!Schema::hasColumn('lembretes_ausencia', 'notification_body')) {
                $table->string('notification_body', 120)->nullable()->after('notification_title');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('lembretes_ausencia')) {
            return;
        }

        Schema::table('lembretes_ausencia', function (Blueprint $table) {
            $columns = [];

            foreach (['notification_body', 'notification_title', 'imagem_url'] as $column) {
                if (Schema::hasColumn('lembretes_ausencia', $column)) {
                    $columns[] = $column;
                }
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
