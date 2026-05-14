<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('lembretes_ausencia')) {
            return;
        }

        Schema::table('lembretes_ausencia', function (Blueprint $table) {
            if (!Schema::hasColumn('lembretes_ausencia', 'dias_sem_visita')) {
                $table->unsignedInteger('dias_sem_visita')->nullable()->after('empresa_id');
            }
        });

        if (Schema::hasColumn('lembretes_ausencia', 'dias_ausencia')) {
            DB::table('lembretes_ausencia')
                ->select('id', 'dias_ausencia', 'dias_sem_visita')
                ->orderBy('id')
                ->chunkById(100, function ($rows): void {
                    foreach ($rows as $row) {
                        if ($row->dias_sem_visita !== null) {
                            continue;
                        }

                        DB::table('lembretes_ausencia')
                            ->where('id', $row->id)
                            ->update([
                                'dias_sem_visita' => $row->dias_ausencia !== null
                                    ? max(1, (int) $row->dias_ausencia)
                                    : null,
                            ]);
                    }
                });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('lembretes_ausencia')) {
            return;
        }

        Schema::table('lembretes_ausencia', function (Blueprint $table) {
            if (Schema::hasColumn('lembretes_ausencia', 'dias_sem_visita')) {
                $table->dropColumn('dias_sem_visita');
            }
        });
    }
};
