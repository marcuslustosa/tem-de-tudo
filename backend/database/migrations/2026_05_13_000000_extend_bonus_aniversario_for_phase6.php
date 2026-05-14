<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bonus_aniversario')) {
            return;
        }

        Schema::table('bonus_aniversario', function (Blueprint $table) {
            if (!Schema::hasColumn('bonus_aniversario', 'dias_validade')) {
                $table->unsignedInteger('dias_validade')->nullable()->after('imagem');
            }

            if (!Schema::hasColumn('bonus_aniversario', 'notification_title')) {
                $table->string('notification_title', 80)->nullable()->after('dias_validade');
            }

            if (!Schema::hasColumn('bonus_aniversario', 'notification_body')) {
                $table->string('notification_body', 120)->nullable()->after('notification_title');
            }
        });

        DB::table('bonus_aniversario')
            ->select('id', 'titulo', 'descricao', 'notification_title', 'notification_body')
            ->orderBy('id')
            ->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    $updates = [];

                    if ($row->notification_title === null) {
                        $updates['notification_title'] = trim((string) ($row->titulo ?? '')) ?: null;
                    }

                    if ($row->notification_body === null) {
                        $descricao = trim((string) ($row->descricao ?? ''));
                        $updates['notification_body'] = $descricao !== '' ? Str::limit($descricao, 120, '') : null;
                    }

                    if ($updates !== []) {
                        DB::table('bonus_aniversario')
                            ->where('id', $row->id)
                            ->update($updates);
                    }
                }
            });
    }

    public function down(): void
    {
        if (!Schema::hasTable('bonus_aniversario')) {
            return;
        }

        Schema::table('bonus_aniversario', function (Blueprint $table) {
            foreach (['notification_body', 'notification_title', 'dias_validade'] as $column) {
                if (Schema::hasColumn('bonus_aniversario', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
