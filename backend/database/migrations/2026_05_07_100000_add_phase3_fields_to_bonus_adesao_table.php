<?php

use App\Models\BonusAdesao;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bonus_adesao')) {
            return;
        }

        Schema::table('bonus_adesao', function (Blueprint $table) {
            if (!Schema::hasColumn('bonus_adesao', 'data_expiracao')) {
                $table->timestamp('data_expiracao')->nullable()->after('imagem');
            }
            if (!Schema::hasColumn('bonus_adesao', 'limite_por_cliente')) {
                $table->integer('limite_por_cliente')->default(1)->after('data_expiracao');
            }
            if (!Schema::hasColumn('bonus_adesao', 'tipo')) {
                $table->string('tipo', 40)->default(BonusAdesao::TYPE_ADHESION_BONUS)->after('limite_por_cliente');
            }
            if (!Schema::hasColumn('bonus_adesao', 'ordem')) {
                $table->integer('ordem')->default(1)->after('tipo');
            }
            if (!Schema::hasColumn('bonus_adesao', 'termos')) {
                $table->text('termos')->nullable()->after('ordem');
            }
        });

        DB::table('bonus_adesao')->update([
            'limite_por_cliente' => 1,
            'tipo' => BonusAdesao::TYPE_ADHESION_BONUS,
        ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('bonus_adesao')) {
            return;
        }

        Schema::table('bonus_adesao', function (Blueprint $table) {
            $columns = collect(['data_expiracao', 'limite_por_cliente', 'tipo', 'ordem', 'termos'])
                ->filter(fn (string $column) => Schema::hasColumn('bonus_adesao', $column))
                ->values()
                ->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
