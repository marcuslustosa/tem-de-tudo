<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lembrete_envios')) {
            return;
        }

        Schema::create('lembrete_envios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lembrete_id')->constrained('lembretes_ausencia')->cascadeOnDelete();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('reference_last_visit_at');
            $table->string('status', 32)->default('pending');
            $table->text('erro')->nullable();
            $table->timestamps();

            $table->unique(
                ['lembrete_id', 'user_id', 'reference_last_visit_at'],
                'lembrete_envios_lembrete_user_visit_unique'
            );
            $table->index(['empresa_id', 'status'], 'lembrete_envios_empresa_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lembrete_envios');
    }
};
