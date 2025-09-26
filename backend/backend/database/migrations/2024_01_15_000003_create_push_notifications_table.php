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
        Schema::create('push_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->enum('user_type', ['client', 'company', 'admin'])->default('client');
            $table->string('title');
            $table->text('body');
            $table->json('data')->nullable();
            $table->string('type', 50)->default('general');
            $table->string('fcm_token', 255)->nullable();
            $table->boolean('is_sent')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            // Índices para performance
            $table->index(['user_id', 'user_type']);
            $table->index(['type', 'created_at']);
            $table->index(['is_sent', 'created_at']);
            $table->index('read_at');
            
            // Chave estrangeira
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('push_notifications');
    }
};