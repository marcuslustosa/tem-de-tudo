<?php

use App\Models\BonusAdesaoResgate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable(BonusAdesaoResgate::TABLE_CANONICAL)) {
            if (Schema::hasTable(BonusAdesaoResgate::TABLE_LEGACY)) {
                Schema::rename(BonusAdesaoResgate::TABLE_LEGACY, BonusAdesaoResgate::TABLE_CANONICAL);
            } else {
                Schema::create(BonusAdesaoResgate::TABLE_CANONICAL, function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('bonus_id')->nullable()->constrained('bonus_adesao')->nullOnDelete();
                    $table->foreignId('empresa_id')->nullable()->constrained('empresas')->nullOnDelete();
                    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                    $table->string('status', 20)->default('redeemed');
                    $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
                    $table->timestamp('redeemed_at')->nullable();
                    $table->boolean('resgatado')->default(true);
                    $table->timestamp('data_resgate')->nullable();
                    $table->integer('pontos')->default(0);
                    $table->timestamps();
                });
            }
        }

        Schema::table(BonusAdesaoResgate::TABLE_CANONICAL, function (Blueprint $table) {
            if (!Schema::hasColumn(BonusAdesaoResgate::TABLE_CANONICAL, 'bonus_id')) {
                $table->foreignId('bonus_id')->nullable()->after('id')->constrained('bonus_adesao')->nullOnDelete();
            }
            if (!Schema::hasColumn(BonusAdesaoResgate::TABLE_CANONICAL, 'empresa_id')) {
                $table->foreignId('empresa_id')->nullable()->after('bonus_id')->constrained('empresas')->nullOnDelete();
            }
            if (!Schema::hasColumn(BonusAdesaoResgate::TABLE_CANONICAL, 'status')) {
                $table->string('status', 20)->default('redeemed')->after('user_id');
            }
            if (!Schema::hasColumn(BonusAdesaoResgate::TABLE_CANONICAL, 'validated_by')) {
                $table->foreignId('validated_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn(BonusAdesaoResgate::TABLE_CANONICAL, 'redeemed_at')) {
                $table->timestamp('redeemed_at')->nullable()->after('validated_by');
            }
            if (!Schema::hasColumn(BonusAdesaoResgate::TABLE_CANONICAL, 'resgatado')) {
                $table->boolean('resgatado')->default(true)->after('redeemed_at');
            }
            if (!Schema::hasColumn(BonusAdesaoResgate::TABLE_CANONICAL, 'data_resgate')) {
                $table->timestamp('data_resgate')->nullable()->after('resgatado');
            }
            if (!Schema::hasColumn(BonusAdesaoResgate::TABLE_CANONICAL, 'pontos')) {
                $table->integer('pontos')->default(0)->after('data_resgate');
            }
        });

        DB::table(BonusAdesaoResgate::TABLE_CANONICAL)
            ->whereNull('status')
            ->update(['status' => BonusAdesaoResgate::STATUS_REDEEMED]);

        if (
            Schema::hasColumn(BonusAdesaoResgate::TABLE_CANONICAL, 'redeemed_at')
            && Schema::hasColumn(BonusAdesaoResgate::TABLE_CANONICAL, 'data_resgate')
        ) {
            DB::statement(
                'UPDATE ' . BonusAdesaoResgate::TABLE_CANONICAL . ' SET redeemed_at = COALESCE(redeemed_at, data_resgate)'
            );
        }

        $duplicates = DB::table(BonusAdesaoResgate::TABLE_CANONICAL)
            ->select('bonus_id', 'user_id', DB::raw('MIN(id) as keep_id'), DB::raw('COUNT(*) as total'))
            ->whereNotNull('bonus_id')
            ->groupBy('bonus_id', 'user_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            DB::table(BonusAdesaoResgate::TABLE_CANONICAL)
                ->where('bonus_id', $duplicate->bonus_id)
                ->where('user_id', $duplicate->user_id)
                ->where('id', '!=', $duplicate->keep_id)
                ->delete();
        }

        Schema::table(BonusAdesaoResgate::TABLE_CANONICAL, function (Blueprint $table) {
            $table->unique(['bonus_id', 'user_id'], 'bonus_adesao_resgates_bonus_id_user_id_unique');
            $table->index(['empresa_id', 'user_id'], 'bonus_adesao_resgates_empresa_id_user_id_index');
            $table->index('status', 'bonus_adesao_resgates_status_index');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable(BonusAdesaoResgate::TABLE_CANONICAL)) {
            return;
        }

        Schema::table(BonusAdesaoResgate::TABLE_CANONICAL, function (Blueprint $table) {
            $table->dropUnique('bonus_adesao_resgates_bonus_id_user_id_unique');
            $table->dropIndex('bonus_adesao_resgates_empresa_id_user_id_index');
            $table->dropIndex('bonus_adesao_resgates_status_index');
        });

        if (!Schema::hasTable(BonusAdesaoResgate::TABLE_LEGACY)) {
            Schema::rename(BonusAdesaoResgate::TABLE_CANONICAL, BonusAdesaoResgate::TABLE_LEGACY);
        }
    }
};
