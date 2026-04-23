<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FraudAlert extends Model
{
    protected $fillable = [
        'user_id',
        'device_id',
        'rule_id',
        'alert_type',
        'risk_score',
        'status',
        'context',
        'details',
        'action_taken',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'context' => 'array',
        'risk_score' => 'integer',
        'reviewed_at' => 'datetime',
    ];

    // Status
    const STATUS_PENDING = 'pending';
    const STATUS_REVIEWED = 'reviewed';
    const STATUS_FALSE_POSITIVE = 'false_positive';
    const STATUS_CONFIRMED = 'confirmed';

    // Alert types
    const TYPE_VELOCITY = 'velocity';
    const TYPE_GEO_ANOMALY = 'geo_anomaly';
    const TYPE_DEVICE_MISMATCH = 'device_mismatch';
    const TYPE_IP_BLACKLIST = 'ip_blacklist';
    const TYPE_TRANSACTION_LIMIT = 'transaction_limit';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(DeviceFingerprint::class, 'device_id');
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(FraudRule::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeHighRisk($query, int $threshold = 70)
    {
        return $query->where('risk_score', '>=', $threshold);
    }

    public function isHighRisk(): bool
    {
        return $this->risk_score >= 70;
    }
}
