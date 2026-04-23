<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabela de regras de anti-fraude
        Schema::create('fraud_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Ex: max_transactions_per_device_hour
            $table->string('description');
            $table->string('rule_type'); // device, ip, geo, velocity, pattern
            $table->json('config'); // Configurações flexíveis
            $table->boolean('is_active')->default(true);
            $table->integer('severity')->default(5); // 1-10
            $table->string('action'); // block, alert, review
            $table->timestamps();
        });

        // Tabela de dispositivos com fingerprint
        Schema::create('device_fingerprints', function (Blueprint $table) {
            $table->id();
            $table->string('device_id')->unique(); // UUID do app
            $table->string('fingerprint_hash'); // Hash de (device_info + IP + user_agent)
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('status')->default('trusted'); // trusted, suspicious, blocked
            $table->json('device_info'); // OS, modelo, versão, etc
            $table->string('last_ip')->nullable();
            $table->decimal('last_lat', 10, 7)->nullable(); // Latitude
            $table->decimal('last_long', 10, 7)->nullable(); // Longitude
            $table->timestamp('first_seen')->nullable();
            $table->timestamp('last_seen')->nullable();
            $table->integer('transaction_count')->default(0);
            $table->timestamps();
            
            $table->index('device_id');
            $table->index('status');
            $table->index('user_id');
        });

        // Tabela de alertas de fraude
        Schema::create('fraud_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('device_id')->nullable()->constrained('device_fingerprints')->onDelete('set null');
            $table->foreignId('rule_id')->constrained('fraud_rules')->onDelete('cascade');
            $table->string('alert_type'); // velocity, geo_anomaly, device_mismatch, etc
            $table->integer('risk_score')->default(0); // 0-100
            $table->string('status')->default('pending'); // pending, reviewed, false_positive, confirmed
            $table->json('context'); // Dados da transação, IP, localização, etc
            $table->text('details')->nullable();
            $table->string('action_taken')->nullable(); // blocked, flagged, allowed_with_warning
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'created_at']);
            $table->index('risk_score');
        });

        // Tabela de blacklist
        Schema::create('fraud_blacklist', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // device, ip, email, phone
            $table->string('value'); // O valor bloqueado
            $table->string('reason');
            $table->foreignId('added_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            $table->unique(['type', 'value']);
            $table->index('type');
        });

        // Seed de regras básicas
        DB::table('fraud_rules')->insert([
            [
                'name' => 'max_transactions_per_device_hour',
                'description' => 'Limite de transações por dispositivo por hora',
                'rule_type' => 'device',
                'config' => json_encode(['max_transactions' => 10, 'time_window' => 60]),
                'is_active' => true,
                'severity' => 7,
                'action' => 'block',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'max_transactions_per_ip_hour',
                'description' => 'Limite de transações por IP por hora',
                'rule_type' => 'ip',
                'config' => json_encode(['max_transactions' => 20, 'time_window' => 60]),
                'is_active' => true,
                'severity' => 6,
                'action' => 'alert',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'geo_distance_anomaly',
                'description' => 'Detecta transações em locais fisicamente impossíveis',
                'rule_type' => 'geo',
                'config' => json_encode(['max_km_per_hour' => 100]),
                'is_active' => true,
                'severity' => 9,
                'action' => 'block',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'velocity_check',
                'description' => 'Detecta múltiplas transações em sequência muito rápida',
                'rule_type' => 'velocity',
                'config' => json_encode(['min_seconds_between' => 10]),
                'is_active' => true,
                'severity' => 8,
                'action' => 'block',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'suspicious_location',
                'description' => 'Transação fora da área geográfica permitida',
                'rule_type' => 'geo',
                'config' => json_encode([
                    'allowed_states' => ['RJ', 'SP', 'MG', 'ES'], // Exemplo
                    'radius_km' => null, // Ou usar raio ao invés de estados
                ]),
                'is_active' => false, // Ativar quando tiver geofencing
                'severity' => 5,
                'action' => 'alert',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('fraud_blacklist');
        Schema::dropIfExists('fraud_alerts');
        Schema::dropIfExists('device_fingerprints');
        Schema::dropIfExists('fraud_rules');
    }
};
