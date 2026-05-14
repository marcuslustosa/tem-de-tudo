<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bonus_adesaos')) {
            Schema::create('bonus_adesaos', function (Blueprint $table) {
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

            return;
        }

        Schema::table('bonus_adesaos', function (Blueprint $table) {
            if (!Schema::hasColumn('bonus_adesaos', 'bonus_id')) {
                $table->foreignId('bonus_id')->nullable()->after('id')->constrained('bonus_adesao')->nullOnDelete();
            }
            if (!Schema::hasColumn('bonus_adesaos', 'status')) {
                $table->string('status', 20)->default('redeemed')->after('user_id');
            }
            if (!Schema::hasColumn('bonus_adesaos', 'validated_by')) {
                $table->foreignId('validated_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('bonus_adesaos', 'redeemed_at')) {
                $table->timestamp('redeemed_at')->nullable()->after('validated_by');
            }
            if (!Schema::hasColumn('bonus_adesaos', 'resgatado')) {
                $table->boolean('resgatado')->default(true)->after('redeemed_at');
            }
            if (!Schema::hasColumn('bonus_adesaos', 'data_resgate')) {
                $table->timestamp('data_resgate')->nullable()->after('resgatado');
            }
            if (!Schema::hasColumn('bonus_adesaos', 'pontos')) {
                $table->integer('pontos')->default(0)->after('data_resgate');
            }
        });

        DB::table('bonus_adesaos')
            ->whereNull('status')
            ->update(['status' => 'redeemed']);

        if (Schema::hasColumn('bonus_adesaos', 'redeemed_at') && Schema::hasColumn('bonus_adesaos', 'data_resgate')) {
            DB::statement('UPDATE bonus_adesaos SET redeemed_at = COALESCE(redeemed_at, data_resgate)');
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('bonus_adesaos')) {
            return;
        }

        Schema::table('bonus_adesaos', function (Blueprint $table) {
            $columns = collect(['bonus_id', 'status', 'validated_by', 'redeemed_at'])
                ->filter(fn (string $column) => Schema::hasColumn('bonus_adesaos', $column))
                ->values()
                ->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
