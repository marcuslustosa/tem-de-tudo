<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fraud_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description');
            $table->string('rule_type');
            $table->json('config');
            $table->boolean('is_active')->default(true);
            $table->integer('severity')->default(5);
            $table->string('action');
            $table->timestamps();
        });

        Schema::create('device_fingerprints', function (Blueprint $table) {
            $table->id();
            $table->string('device_id')->unique();
            $table->string('fingerprint_hash');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('status')->default('trusted');
            $table->json('device_info');
            $table->string('last_ip')->nullable();
            $table->decimal('last_lat', 10, 7)->nullable();
            $table->decimal('last_long', 10, 7)->nullable();
            $table->timestamp('first_seen')->nullable();
            $table->timestamp('last_seen')->nullable();
            $table->integer('transaction_count')->default(0);
            $table->timestamps();

            $table->index('device_id');
            $table->index('status');
            $table->index('user_id');
        });

        Schema::create('fraud_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('device_id')->nullable()->constrained('device_fingerprints')->onDelete('set null');
            $table->foreignId('rule_id')->constrained('fraud_rules')->onDelete('cascade');
            $table->string('alert_type');
            $table->integer('risk_score')->default(0);
            $table->string('status')->default('pending');
            $table->json('context');
            $table->text('details')->nullable();
            $table->string('action_taken')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('risk_score');
        });

        Schema::create('fraud_blacklist', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('value');
            $table->string('reason');
            $table->foreignId('added_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['type', 'value']);
            $table->index('type');
        });

        $this->seedDefaultRules();
    }

    private function seedDefaultRules(): void
    {
        foreach ([
            [
                'name' => 'max_transactions_per_device_hour',
                'description' => 'Limite de transacoes por dispositivo por hora',
                'rule_type' => 'device',
                'config' => json_encode(['max_transactions' => 10, 'time_window' => 60]),
                'severity' => 7,
                'action' => 'block',
            ],
            [
                'name' => 'max_transactions_per_ip_hour',
                'description' => 'Limite de transacoes por IP por hora',
                'rule_type' => 'ip',
                'config' => json_encode(['max_transactions' => 20, 'time_window' => 60]),
                'severity' => 6,
                'action' => 'alert',
            ],
            [
                'name' => 'geo_distance_anomaly',
                'description' => 'Detecta transacoes em locais fisicamente impossiveis',
                'rule_type' => 'geo',
                'config' => json_encode(['max_km_per_hour' => 100]),
                'severity' => 9,
                'action' => 'block',
            ],
            [
                'name' => 'velocity_check',
                'description' => 'Detecta multiplas transacoes em sequencia muito rapida',
                'rule_type' => 'velocity',
                'config' => json_encode(['min_seconds_between' => 10]),
                'severity' => 8,
                'action' => 'block',
            ],
            [
                'name' => 'suspicious_location',
                'description' => 'Transacao fora da area geografica permitida',
                'rule_type' => 'geo',
                'config' => json_encode([
                    'allowed_states' => ['RJ', 'SP', 'MG', 'ES'],
                    'radius_km' => null,
                ]),
                'severity' => 5,
                'action' => 'alert',
                'is_active' => $this->booleanValue(false),
            ],
        ] as $rule) {
            DB::table('fraud_rules')->insert(array_merge([
                'created_at' => now(),
                'updated_at' => now(),
            ], $rule));
        }
    }

    private function booleanValue(bool $value): mixed
    {
        if (DB::getDriverName() === 'pgsql') {
            return DB::raw($value ? 'TRUE' : 'FALSE');
        }

        return $value;
    }

    public function down(): void
    {
        Schema::dropIfExists('fraud_blacklist');
        Schema::dropIfExists('fraud_alerts');
        Schema::dropIfExists('device_fingerprints');
        Schema::dropIfExists('fraud_rules');
    }
};
