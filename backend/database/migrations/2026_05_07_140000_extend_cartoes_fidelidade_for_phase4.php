<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cartoes_fidelidade')) {
            return;
        }

        Schema::table('cartoes_fidelidade', function (Blueprint $table) {
            if (!Schema::hasColumn('cartoes_fidelidade', 'regra_ganho')) {
                $table->string('regra_ganho', 160)->nullable()->after('descricao');
            }

            if (!Schema::hasColumn('cartoes_fidelidade', 'pontos_por_visita')) {
                $table->unsignedInteger('pontos_por_visita')->default(1)->after('regra_ganho');
            }

            if (!Schema::hasColumn('cartoes_fidelidade', 'pontos_necessarios')) {
                $table->unsignedInteger('pontos_necessarios')->default(1)->after('pontos_por_visita');
            }

            if (!Schema::hasColumn('cartoes_fidelidade', 'recompensa_descricao')) {
                $table->string('recompensa_descricao', 280)->nullable()->after('pontos_necessarios');
            }

            if (!Schema::hasColumn('cartoes_fidelidade', 'data_expiracao')) {
                $table->date('data_expiracao')->nullable()->after('recompensa_descricao');
            }
        });

        $rows = DB::table('cartoes_fidelidade')->get();
        foreach ($rows as $row) {
            $metaPontos = max(1, (int) ($row->meta_pontos ?? 1));
            $reward = trim((string) ($row->recompensa ?? ''));
            $descricao = trim((string) ($row->descricao ?? ''));
            $regraGanho = trim((string) ($row->regra_ganho ?? ''));
            $validade = $row->validade ?? null;

            DB::table('cartoes_fidelidade')
                ->where('id', $row->id)
                ->update([
                    'pontos_por_visita' => max(1, (int) ($row->pontos_por_visita ?? 1)),
                    'pontos_necessarios' => max(1, (int) ($row->pontos_necessarios ?? $metaPontos)),
                    'recompensa_descricao' => trim((string) ($row->recompensa_descricao ?? $reward)) !== ''
                        ? $row->recompensa_descricao
                        : ($reward !== '' ? $reward : $descricao),
                    'regra_ganho' => $regraGanho !== ''
                        ? $regraGanho
                        : 'Ganhe 1 ponto a cada visita.',
                    'data_expiracao' => $row->data_expiracao ?? $validade,
                ]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('cartoes_fidelidade')) {
            return;
        }

        Schema::table('cartoes_fidelidade', function (Blueprint $table) {
            foreach (['regra_ganho', 'pontos_por_visita', 'pontos_necessarios', 'recompensa_descricao', 'data_expiracao'] as $column) {
                if (Schema::hasColumn('cartoes_fidelidade', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
