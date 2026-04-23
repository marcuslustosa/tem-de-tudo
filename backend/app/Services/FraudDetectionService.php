<?php

namespace App\Services;

use App\Models\DeviceFingerprint;
use App\Models\FraudAlert;
use App\Models\FraudRule;
use App\Models\Ledger;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class FraudDetectionService
{
    /**
     * @param array $context ['device_id', 'ip|ip_address', 'lat|latitude', 'long|longitude']
     */
    public function validateTransaction(int $userId, array $context): array
    {
        $context = $this->normalizeContext($context);

        $alerts = [];
        $riskScore = 0;
        $allowed = true;
        $reason = '';

        $device = $this->registerDevice($userId, $context);

        if ($this->isBlacklisted($context)) {
            return [
                'allowed' => false,
                'risk_score' => 100,
                'alerts' => [],
                'reason' => 'Dispositivo ou IP bloqueado por seguranca',
            ];
        }

        $rules = FraudRule::active()->orderBy('severity', 'desc')->get();

        foreach ($rules as $rule) {
            $result = $this->checkRule($rule, $userId, $device, $context);

            if (!$result['violated']) {
                continue;
            }

            $alert = $this->createAlert($userId, $device?->id, $rule, $result, $context);
            $alerts[] = $alert;
            $riskScore += $result['risk_score'];

            if ($rule->action === FraudRule::ACTION_BLOCK) {
                $allowed = false;
                $reason = $result['reason'];
                break;
            }
        }

        if ($device && $allowed) {
            $device->incrementTransactionCount();
        }

        return [
            'allowed' => $allowed,
            'risk_score' => min($riskScore, 100),
            'alerts' => $alerts,
            'reason' => $reason,
        ];
    }

    protected function registerDevice(int $userId, array $context): ?DeviceFingerprint
    {
        if (!isset($context['device_id'])) {
            return null;
        }

        $deviceInfo = [
            'os' => $context['os'] ?? 'unknown',
            'model' => $context['model'] ?? 'unknown',
            'app_version' => $context['app_version'] ?? 'unknown',
            'user_agent' => $context['user_agent'] ?? 'unknown',
        ];

        $fingerprintHash = hash('sha256', json_encode($deviceInfo) . ($context['ip'] ?? ''));

        $device = DeviceFingerprint::firstOrCreate(
            ['device_id' => $context['device_id']],
            [
                'fingerprint_hash' => $fingerprintHash,
                'user_id' => $userId,
                'status' => DeviceFingerprint::STATUS_TRUSTED,
                'device_info' => $deviceInfo,
                'first_seen' => now(),
            ]
        );

        $device->update([
            'last_ip' => $context['ip'] ?? null,
            'last_lat' => $context['lat'] ?? null,
            'last_long' => $context['long'] ?? null,
            'last_seen' => now(),
        ]);

        return $device;
    }

    protected function isBlacklisted(array $context): bool
    {
        $checks = [];

        if (isset($context['device_id'])) {
            $checks[] = ['type' => 'device', 'value' => $context['device_id']];
        }

        if (isset($context['ip'])) {
            $checks[] = ['type' => 'ip', 'value' => $context['ip']];
        }

        foreach ($checks as $check) {
            $blocked = DB::table('fraud_blacklist')
                ->where('type', $check['type'])
                ->where('value', $check['value'])
                ->where(function ($q) {
                    $q->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->exists();

            if ($blocked) {
                return true;
            }
        }

        return false;
    }

    protected function checkRule(FraudRule $rule, int $userId, ?DeviceFingerprint $device, array $context): array
    {
        $config = $rule->config ?? [];

        return match ($rule->rule_type) {
            FraudRule::TYPE_DEVICE => $this->checkDeviceRule($rule, $device, $config),
            FraudRule::TYPE_IP => $this->checkIpRule($rule, $context, $config),
            FraudRule::TYPE_GEO => $this->checkGeoRule($rule, $device, $context, $config),
            FraudRule::TYPE_VELOCITY => $this->checkVelocityRule($rule, $userId, $config),
            default => ['violated' => false, 'risk_score' => 0, 'reason' => ''],
        };
    }

    protected function checkDeviceRule(FraudRule $rule, ?DeviceFingerprint $device, array $config): array
    {
        if (!$device) {
            return ['violated' => false, 'risk_score' => 0, 'reason' => ''];
        }

        $timeWindow = (int) ($config['time_window'] ?? 60);
        $maxTransactions = (int) ($config['max_transactions'] ?? 10);

        $count = Ledger::where('created_at', '>=', now()->subMinutes($timeWindow))
            ->whereJsonContains('metadata->device_id', $device->device_id)
            ->count();

        if ($count >= $maxTransactions) {
            return [
                'violated' => true,
                'risk_score' => $rule->severity * 10,
                'reason' => "Limite de {$maxTransactions} transacoes por dispositivo excedido",
            ];
        }

        return ['violated' => false, 'risk_score' => 0, 'reason' => ''];
    }

    protected function checkIpRule(FraudRule $rule, array $context, array $config): array
    {
        if (!isset($context['ip'])) {
            return ['violated' => false, 'risk_score' => 0, 'reason' => ''];
        }

        $timeWindow = (int) ($config['time_window'] ?? 60);
        $maxTransactions = (int) ($config['max_transactions'] ?? 20);
        $ip = (string) $context['ip'];

        $cacheKey = "fraud:ip:{$ip}:count";
        $count = Cache::remember($cacheKey, $timeWindow * 60, function () use ($ip, $timeWindow) {
            return Ledger::where('created_at', '>=', now()->subMinutes($timeWindow))
                ->whereJsonContains('metadata->ip', $ip)
                ->count();
        });

        if ($count >= $maxTransactions) {
            return [
                'violated' => true,
                'risk_score' => $rule->severity * 10,
                'reason' => "Limite de {$maxTransactions} transacoes por IP excedido",
            ];
        }

        return ['violated' => false, 'risk_score' => 0, 'reason' => ''];
    }

    protected function checkGeoRule(FraudRule $rule, ?DeviceFingerprint $device, array $context, array $config): array
    {
        if (!$device || !isset($context['lat'], $context['long'])) {
            return ['violated' => false, 'risk_score' => 0, 'reason' => ''];
        }

        $maxKmPerHour = (float) ($config['max_km_per_hour'] ?? 100);

        $lastTransaction = Ledger::whereJsonContains('metadata->device_id', $device->device_id)
            ->whereNotNull('metadata->lat')
            ->whereNotNull('metadata->long')
            ->latest()
            ->first();

        if (!$lastTransaction) {
            return ['violated' => false, 'risk_score' => 0, 'reason' => ''];
        }

        $lastLat = (float) ($lastTransaction->metadata['lat'] ?? 0);
        $lastLong = (float) ($lastTransaction->metadata['long'] ?? 0);
        if ($lastLat === 0.0 || $lastLong === 0.0) {
            return ['violated' => false, 'risk_score' => 0, 'reason' => ''];
        }

        $distance = $this->calculateDistance($lastLat, $lastLong, (float) $context['lat'], (float) $context['long']);
        $secondsDiff = max(1, now()->diffInSeconds($lastTransaction->created_at));
        $hoursDiff = $secondsDiff / 3600;
        $speed = $distance / $hoursDiff;

        if ($speed > $maxKmPerHour) {
            return [
                'violated' => true,
                'risk_score' => $rule->severity * 10,
                'reason' => "Movimento geografico impossivel: {$distance}km em {$hoursDiff}h",
            ];
        }

        return ['violated' => false, 'risk_score' => 0, 'reason' => ''];
    }

    protected function checkVelocityRule(FraudRule $rule, int $userId, array $config): array
    {
        $minSeconds = (int) ($config['min_seconds_between'] ?? 10);

        $lastTransaction = Ledger::where('user_id', $userId)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->latest()
            ->first();

        if ($lastTransaction) {
            $secondsSince = now()->diffInSeconds($lastTransaction->created_at);
            if ($secondsSince < $minSeconds) {
                return [
                    'violated' => true,
                    'risk_score' => $rule->severity * 10,
                    'reason' => "Transacoes muito rapidas (intervalo: {$secondsSince}s)",
                ];
            }
        }

        return ['violated' => false, 'risk_score' => 0, 'reason' => ''];
    }

    protected function createAlert(int $userId, ?int $deviceId, FraudRule $rule, array $result, array $context): FraudAlert
    {
        return FraudAlert::create([
            'user_id' => $userId,
            'device_id' => $deviceId,
            'rule_id' => $rule->id,
            'alert_type' => $rule->rule_type,
            'risk_score' => $result['risk_score'],
            'status' => FraudAlert::STATUS_PENDING,
            'context' => $context,
            'details' => $result['reason'],
            'action_taken' => $rule->action,
        ]);
    }

    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

    public function addToBlacklist(string $type, string $value, string $reason, int $addedBy, ?Carbon $expiresAt = null): void
    {
        DB::table('fraud_blacklist')->insert([
            'type' => $type,
            'value' => $value,
            'reason' => $reason,
            'added_by' => $addedBy,
            'expires_at' => $expiresAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function removeFromBlacklist(string $type, string $value): void
    {
        DB::table('fraud_blacklist')
            ->where('type', $type)
            ->where('value', $value)
            ->delete();
    }

    private function normalizeContext(array $context): array
    {
        if (isset($context['ip_address']) && !isset($context['ip'])) {
            $context['ip'] = $context['ip_address'];
        }

        if (isset($context['latitude']) && !isset($context['lat'])) {
            $context['lat'] = $context['latitude'];
        }

        if (isset($context['longitude']) && !isset($context['long'])) {
            $context['long'] = $context['longitude'];
        }

        return $context;
    }
}

