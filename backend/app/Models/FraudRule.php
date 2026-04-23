<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FraudRule extends Model
{
    protected $fillable = [
        'name',
        'description',
        'rule_type',
        'config',
        'is_active',
        'severity',
        'action',
    ];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
        'severity' => 'integer',
    ];

    // Rule types
    const TYPE_DEVICE = 'device';
    const TYPE_IP = 'ip';
    const TYPE_GEO = 'geo';
    const TYPE_VELOCITY = 'velocity';
    const TYPE_PATTERN = 'pattern';

    // Actions
    const ACTION_BLOCK = 'block';
    const ACTION_ALERT = 'alert';
    const ACTION_REVIEW = 'review';

    public function alerts(): HasMany
    {
        return $this->hasMany(FraudAlert::class, 'rule_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('rule_type', $type);
    }
}
