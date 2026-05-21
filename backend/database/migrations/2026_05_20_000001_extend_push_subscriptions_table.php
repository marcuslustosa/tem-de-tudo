<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('push_subscriptions')) {
            return;
        }

        Schema::table('push_subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('push_subscriptions', 'public_key')) {
                $table->text('public_key')->nullable();
            }

            if (!Schema::hasColumn('push_subscriptions', 'auth_token')) {
                $table->text('auth_token')->nullable();
            }

            if (!Schema::hasColumn('push_subscriptions', 'content_encoding')) {
                $table->string('content_encoding', 32)->nullable()->default('aes128gcm');
            }

            if (!Schema::hasColumn('push_subscriptions', 'device_type')) {
                $table->string('device_type', 32)->nullable();
            }

            if (!Schema::hasColumn('push_subscriptions', 'last_seen_at')) {
                $table->timestamp('last_seen_at')->nullable();
            }

            if (!Schema::hasColumn('push_subscriptions', 'revoked_at')) {
                $table->timestamp('revoked_at')->nullable();
            }
        });

        if (Schema::hasColumn('push_subscriptions', 'public_key') && Schema::hasColumn('push_subscriptions', 'p256dh')) {
            DB::table('push_subscriptions')
                ->whereNull('public_key')
                ->whereNotNull('p256dh')
                ->update(['public_key' => DB::raw('p256dh')]);
        }

        if (Schema::hasColumn('push_subscriptions', 'auth_token') && Schema::hasColumn('push_subscriptions', 'auth')) {
            DB::table('push_subscriptions')
                ->whereNull('auth_token')
                ->whereNotNull('auth')
                ->update(['auth_token' => DB::raw('auth')]);
        }

        if (Schema::hasColumn('push_subscriptions', 'content_encoding')) {
            DB::table('push_subscriptions')
                ->whereNull('content_encoding')
                ->update(['content_encoding' => 'aes128gcm']);
        }

        $this->createIndexIfMissing('push_subscriptions', ['user_id', 'revoked_at'], 'push_subscriptions_user_revoked_idx');
        $this->createIndexIfMissing('push_subscriptions', ['last_seen_at'], 'push_subscriptions_last_seen_idx');
        $this->createIndexIfMissing('push_subscriptions', ['device_type'], 'push_subscriptions_device_type_idx');
    }

    public function down(): void
    {
        if (!Schema::hasTable('push_subscriptions')) {
            return;
        }

        Schema::table('push_subscriptions', function (Blueprint $table) {
            $this->dropIndexIfExists($table, 'push_subscriptions_user_revoked_idx');
            $this->dropIndexIfExists($table, 'push_subscriptions_last_seen_idx');
            $this->dropIndexIfExists($table, 'push_subscriptions_device_type_idx');

            foreach (['public_key', 'auth_token', 'content_encoding', 'device_type', 'last_seen_at', 'revoked_at'] as $column) {
                if (Schema::hasColumn('push_subscriptions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function createIndexIfMissing(string $table, array $columns, string $indexName): void
    {
        try {
            Schema::table($table, function (Blueprint $tableBlueprint) use ($columns, $indexName) {
                $tableBlueprint->index($columns, $indexName);
            });
        } catch (\Throwable) {
            // Indice ja existente ou nao suportado pelo banco atual.
        }
    }

    private function dropIndexIfExists(Blueprint $table, string $indexName): void
    {
        try {
            $table->dropIndex($indexName);
        } catch (\Throwable) {
            // Indice inexistente no banco atual.
        }
    }
};
