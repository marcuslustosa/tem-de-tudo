<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('promocao_resgates')) {
            return;
        }

        Schema::create('promocao_resgates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promocao_id')->constrained('promocoes')->onDelete('cascade');
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('status', 20)->default('redeemed');
            $table->timestamp('redeemed_at')->nullable();
            $table->unsignedBigInteger('validated_by')->nullable();
            $table->timestamps();

            $table->unique(['promocao_id', 'user_id'], 'promocao_resgates_promocao_user_unique');
            $table->index(['empresa_id', 'status'], 'promocao_resgates_empresa_status_idx');
            $table->foreign('validated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promocao_resgates');
    }
};
