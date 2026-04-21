<?php

namespace App\Services;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

class ClienteQrCodeService
{
    private const PREFIX_V2 = 'CLIENT_V2_';
    private const PREFIX_LEGACY = 'CLIENT_';

    public const DEFAULT_TTL_SECONDS = 3600;

    public function gerar(User $user, int $ttlSeconds = self::DEFAULT_TTL_SECONDS): array
    {
        $issuedAt = CarbonImmutable::now();
        $expiresAt = $issuedAt->addSeconds(max(60, $ttlSeconds));

        $payload = [
            'uid' => (int) $user->id,
            'iat' => $issuedAt->timestamp,
            'exp' => $expiresAt->timestamp,
            'nonce' => Str::random(12),
        ];

        $encodedPayload = $this->base64UrlEncode((string) json_encode($payload, JSON_UNESCAPED_SLASHES));
        $signature = hash_hmac('sha256', $encodedPayload, $this->signatureKey());

        return [
            'code' => self::PREFIX_V2 . $encodedPayload . '.' . $signature,
            'issued_at' => $issuedAt,
            'expires_at' => $expiresAt,
            'version' => 'v2',
        ];
    }

    public function decodificar(string $code): ?array
    {
        $code = trim($code);
        if ($code === '') {
            return null;
        }

        if (str_starts_with($code, self::PREFIX_V2)) {
            return $this->decodificarV2($code);
        }

        if (str_starts_with($code, self::PREFIX_LEGACY)) {
            return $this->decodificarLegado($code);
        }

        return null;
    }

    private function decodificarV2(string $code): ?array
    {
        $withoutPrefix = substr($code, strlen(self::PREFIX_V2));
        $parts = explode('.', $withoutPrefix, 2);

        if (count($parts) !== 2) {
            return null;
        }

        [$encodedPayload, $signature] = $parts;
        if ($encodedPayload === '' || !preg_match('/^[a-f0-9]{64}$/i', $signature)) {
            return null;
        }

        $expectedSignature = hash_hmac('sha256', $encodedPayload, $this->signatureKey());
        if (!hash_equals($expectedSignature, strtolower($signature))) {
            return null;
        }

        $payloadJson = $this->base64UrlDecode($encodedPayload);
        if ($payloadJson === null) {
            return null;
        }

        $payload = json_decode($payloadJson, true);
        if (!is_array($payload)) {
            return null;
        }

        $userId = filter_var($payload['uid'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $expiresAt = filter_var($payload['exp'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $issuedAt = filter_var($payload['iat'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if (!$userId || !$expiresAt || !$issuedAt) {
            return null;
        }

        if (CarbonImmutable::now()->timestamp > $expiresAt) {
            return null;
        }

        return [
            'user_id' => (int) $userId,
            'issued_at' => CarbonImmutable::createFromTimestamp((int) $issuedAt)->toIso8601String(),
            'expires_at' => CarbonImmutable::createFromTimestamp((int) $expiresAt)->toIso8601String(),
            'version' => 'v2',
        ];
    }

    private function decodificarLegado(string $code): ?array
    {
        if (!preg_match('/^CLIENT_(\d+)_([a-f0-9]{32})$/i', $code, $matches)) {
            return null;
        }

        $userId = (int) $matches[1];
        $signature = strtolower($matches[2]);
        $user = User::find($userId);

        if (!$user) {
            return null;
        }

        $legacyHash = md5((string) $user->email);
        if (!hash_equals($legacyHash, $signature)) {
            return null;
        }

        return [
            'user_id' => $userId,
            'issued_at' => null,
            'expires_at' => null,
            'version' => 'legacy',
        ];
    }

    private function signatureKey(): string
    {
        $key = (string) config('app.key', '');

        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);
            if ($decoded !== false && $decoded !== '') {
                return $decoded;
            }
        }

        if ($key !== '') {
            return $key;
        }

        return 'tem-de-tudo-qr-fallback-key';
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): ?string
    {
        $pad = strlen($value) % 4;
        if ($pad > 0) {
            $value .= str_repeat('=', 4 - $pad);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return $decoded === false ? null : $decoded;
    }
}
