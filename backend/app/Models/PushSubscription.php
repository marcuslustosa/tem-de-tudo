<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class PushSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'endpoint',
        'public_key',
        'auth_token',
        'content_encoding',
        'p256dh',
        'auth',
        'user_agent',
        'device_type',
        'last_seen_at',
        'revoked_at',
        'ip',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('revoked_at');
    }

    public function webPushPublicKey(): ?string
    {
        return $this->public_key ?: $this->p256dh;
    }

    public function webPushAuthToken(): ?string
    {
        return $this->auth_token ?: $this->auth;
    }

    public function webPushContentEncoding(): string
    {
        return $this->content_encoding ?: 'aes128gcm';
    }

    public function revoke(?Carbon $at = null): void
    {
        $this->forceFill([
            'revoked_at' => $at ?: now(),
        ])->save();
    }

    public static function detectDeviceType(?string $userAgent): ?string
    {
        $agent = strtolower(trim((string) $userAgent));
        if ($agent === '') {
            return null;
        }

        if (str_contains($agent, 'iphone') || str_contains($agent, 'ipad') || str_contains($agent, 'ipod')) {
            return 'ios';
        }

        if (str_contains($agent, 'android')) {
            return 'android';
        }

        if (str_contains($agent, 'windows')) {
            return 'windows';
        }

        if (str_contains($agent, 'macintosh') || str_contains($agent, 'mac os')) {
            return 'macos';
        }

        if (str_contains($agent, 'linux')) {
            return 'linux';
        }

        return 'desktop';
    }
}
