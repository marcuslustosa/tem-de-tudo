<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('promocoes')) {
            return;
        }

        Schema::table('promocoes', function (Blueprint $table) {
            if (!Schema::hasColumn('promocoes', 'notification_title')) {
                $table->string('notification_title', 80)->nullable()->after('titulo');
            }

            if (!Schema::hasColumn('promocoes', 'notification_body')) {
                $table->string('notification_body', 120)->nullable()->after('notification_title');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('promocoes')) {
            return;
        }

        Schema::table('promocoes', function (Blueprint $table) {
            $columns = [];

            if (Schema::hasColumn('promocoes', 'notification_title')) {
                $columns[] = 'notification_title';
            }

            if (Schema::hasColumn('promocoes', 'notification_body')) {
                $columns[] = 'notification_body';
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
