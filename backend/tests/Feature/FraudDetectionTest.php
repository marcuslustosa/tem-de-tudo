<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\FraudDetectionService;
use App\Services\LedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FraudDetectionTest extends TestCase
{
    use RefreshDatabase;

    private FraudDetectionService $fraudService;
    private LedgerService $ledgerService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fraudService = app(FraudDetectionService::class);
        $this->ledgerService = app(LedgerService::class);
    }

    public function test_valid_transaction_passes_all_rules(): void
    {
        $user = User::factory()->create();

        $result = $this->fraudService->validateTransaction($user->id, [
            'device_id' => 'device-ok-123',
            'ip' => '192.168.1.1',
            'lat' => -23.5505,
            'long' => -46.6333,
        ]);

        $this->assertTrue($result['allowed']);
        $this->assertSame(0, $result['risk_score']);
        $this->assertEmpty($result['alerts']);
    }

    public function test_device_limit_rule_blocks_excessive_transactions(): void
    {
        $user = User::factory()->create();
        $deviceId = 'device-abuse-' . uniqid();

        for ($i = 0; $i < 10; $i++) {
            $this->ledgerService->credit($user->id, 10, 'Tx', [
                'metadata' => ['device_id' => $deviceId, 'ip' => '192.168.1.10'],
            ]);
        }

        $result = $this->fraudService->validateTransaction($user->id, [
            'device_id' => $deviceId,
            'ip' => '192.168.1.10',
        ]);

        $this->assertFalse($result['allowed']);
        $this->assertGreaterThan(0, $result['risk_score']);
    }

    public function test_geo_anomaly_detects_impossible_travel(): void
    {
        $user = User::factory()->create();
        $deviceId = 'device-geo-' . uniqid();

        $ledger = $this->ledgerService->credit($user->id, 20, 'Primeira transacao', [
            'metadata' => [
                'device_id' => $deviceId,
                'ip' => '192.168.1.20',
                'lat' => -23.5505, // Sao Paulo
                'long' => -46.6333,
            ],
        ]);

        DB::table('ledger')->where('id', $ledger->id)->update([
            'created_at' => now()->subMinutes(5),
        ]);

        $result = $this->fraudService->validateTransaction($user->id, [
            'device_id' => $deviceId,
            'ip' => '192.168.1.20',
            'lat' => -22.9068, // Rio
            'long' => -43.1729,
        ]);

        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('impossivel', mb_strtolower($result['reason']));
    }

    public function test_velocity_rule_blocks_bot_behavior(): void
    {
        $user = User::factory()->create();

        $this->ledgerService->credit($user->id, 10, 'Tx recente');

        $result = $this->fraudService->validateTransaction($user->id, [
            'device_id' => 'device-bot-' . uniqid(),
            'ip' => '10.0.0.1',
        ]);

        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('rapidas', mb_strtolower($result['reason']));
    }

    public function test_blacklist_blocks_device(): void
    {
        $user = User::factory()->create();
        $deviceId = 'blacklisted-device-' . uniqid();

        $this->fraudService->addToBlacklist('device', $deviceId, 'Fraude', $user->id, now()->addDays(30));

        $result = $this->fraudService->validateTransaction($user->id, [
            'device_id' => $deviceId,
            'ip' => '192.168.1.100',
        ]);

        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('bloqueado', mb_strtolower($result['reason']));
    }

    public function test_blacklist_blocks_ip(): void
    {
        $user = User::factory()->create();
        $ip = '10.0.0.66';

        $this->fraudService->addToBlacklist('ip', $ip, 'Bot network', $user->id, now()->addDays(7));

        $result = $this->fraudService->validateTransaction($user->id, [
            'device_id' => 'device-123',
            'ip' => $ip,
        ]);

        $this->assertFalse($result['allowed']);
    }

    public function test_fraud_alert_created_on_detection(): void
    {
        $user = User::factory()->create();
        $deviceId = 'device-alert-' . uniqid();

        $lastLedgerId = null;
        for ($i = 0; $i < 10; $i++) {
            $ledger = $this->ledgerService->credit($user->id, 5, 'Tx', [
                'metadata' => ['device_id' => $deviceId, 'ip' => '192.168.1.1'],
            ]);
            $lastLedgerId = $ledger->id;
        }

        if ($lastLedgerId) {
            DB::table('ledger')->where('id', '<=', $lastLedgerId)->update([
                'created_at' => now()->subMinutes(2),
            ]);
        }

        $this->fraudService->validateTransaction($user->id, [
            'device_id' => $deviceId,
            'ip' => '192.168.1.1',
        ]);

        $this->assertDatabaseHas('fraud_alerts', [
            'user_id' => $user->id,
            'status' => 'pending',
        ]);
    }

    public function test_haversine_distance_calculation(): void
    {
        $distance = $this->fraudService->calculateDistance(
            -23.5505,
            -46.6333,
            -22.9068,
            -43.1729
        );

        $this->assertGreaterThan(350, $distance);
        $this->assertLessThan(500, $distance);
    }
}
