<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bonus_aniversario_resgates')) {
            return;
        }

        Schema::create('bonus_aniversario_resgates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bonus_aniversario_id')->constrained('bonus_aniversario')->cascadeOnDelete();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedSmallInteger('ano');
            $table->string('status', 20)->default('redeemed');
            $table->timestamp('redeemed_at')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['bonus_aniversario_id', 'user_id', 'ano'],
                'bonus_aniversario_resgates_bonus_user_ano_unique'
            );
            $table->unique(
                ['empresa_id', 'user_id', 'ano'],
                'bonus_aniversario_resgates_empresa_user_ano_unique'
            );
            $table->index(['empresa_id', 'status'], 'bonus_aniversario_resgates_empresa_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bonus_aniversario_resgates');
    }
};
