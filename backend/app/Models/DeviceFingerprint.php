<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeviceFingerprint extends Model
{
    protected $fillable = [
        'device_id',
        'fingerprint_hash',
        'user_id',
        'status',
        'device_info',
        'last_ip',
        'last_lat',
        'last_long',
        'first_seen',
        'last_seen',
        'transaction_count',
    ];

    protected $casts = [
        'device_info' => 'array',
        'last_lat' => 'float',
        'last_long' => 'float',
        'first_seen' => 'datetime',
        'last_seen' => 'datetime',
        'transaction_count' => 'integer',
    ];

    // Status
    const STATUS_TRUSTED = 'trusted';
    const STATUS_SUSPICIOUS = 'suspicious';
    const STATUS_BLOCKED = 'blocked';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(FraudAlert::class, 'device_id');
    }

    public function scopeTrusted($query)
    {
        return $query->where('status', self::STATUS_TRUSTED);
    }

    public function scopeSuspicious($query)
    {
        return $query->where('status', self::STATUS_SUSPICIOUS);
    }

    public function scopeBlocked($query)
    {
        return $query->where('status', self::STATUS_BLOCKED);
    }

    public function isTrusted(): bool
    {
        return $this->status === self::STATUS_TRUSTED;
    }

    public function isBlocked(): bool
    {
        return $this->status === self::STATUS_BLOCKED;
    }

    public function incrementTransactionCount(): void
    {
        $this->increment('transaction_count');
        $this->update(['last_seen' => now()]);
    }
}
