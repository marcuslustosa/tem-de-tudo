<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'terms_accepted_at')) {
                $table->timestamp('terms_accepted_at')->nullable()->after('remember_token');
            }

            if (!Schema::hasColumn('users', 'privacy_policy_accepted_at')) {
                $table->timestamp('privacy_policy_accepted_at')->nullable()->after('terms_accepted_at');
            }

            if (!Schema::hasColumn('users', 'data_processing_consent_at')) {
                $table->timestamp('data_processing_consent_at')->nullable()->after('privacy_policy_accepted_at');
            }

            if (!Schema::hasColumn('users', 'marketing_consent')) {
                $table->boolean('marketing_consent')->default(false)->after('data_processing_consent_at');
            }

            if (!Schema::hasColumn('users', 'consent_version')) {
                $table->string('consent_version', 20)->nullable()->after('marketing_consent');
            }
        });

        Schema::create('data_privacy_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('request_type', 30); // export, delete_account, consent_update
            $table->string('status', 30)->default('pending'); // pending, processing, completed, failed
            $table->text('reason')->nullable();
            $table->string('file_path')->nullable();
            $table->json('payload')->nullable();
            $table->string('requested_ip', 64)->nullable();
            $table->text('requested_user_agent')->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'request_type']);
            $table->index(['status', 'requested_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_privacy_requests');

        Schema::table('users', function (Blueprint $table) {
            $columns = [
                'terms_accepted_at',
                'privacy_policy_accepted_at',
                'data_processing_consent_at',
                'marketing_consent',
                'consent_version',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

