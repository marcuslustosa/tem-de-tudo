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
        Schema::table('users', function (Blueprint $table) {
            $table->string('fcm_token', 255)->nullable()->after('remember_token');
            $table->boolean('email_notifications')->default(true)->after('fcm_token');
            $table->boolean('points_notifications')->default(true)->after('email_notifications');
            $table->boolean('security_notifications')->default(true)->after('points_notifications');
            $table->boolean('promotional_notifications')->default(false)->after('security_notifications');
            
            // Índice para FCM token
            $table->index('fcm_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'fcm_token',
                'email_notifications',
                'points_notifications', 
                'security_notifications',
                'promotional_notifications'
            ]);
        });
    }
};