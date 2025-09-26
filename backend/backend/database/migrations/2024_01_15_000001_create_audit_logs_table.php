<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action', 100);
            $table->ipAddress('ip_address');
            $table->text('user_agent')->nullable();
            $table->json('details')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at')->nullable();

            // Índices para performance
            $table->index(['action', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('ip_address');
            $table->index('created_at');

            // Chave estrangeira opcional (pode ser admin ou user regular)
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};