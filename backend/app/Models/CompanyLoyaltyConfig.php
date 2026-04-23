<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyLoyaltyConfig extends Model
{
    protected $fillable = [
        'company_id',
        'points_per_real',
        'scan_base_points',
        'redeem_points_per_currency',
        'min_redeem_points',
        'welcome_bonus_points',
        'is_active',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'points_per_real' => 'float',
        'scan_base_points' => 'integer',
        'redeem_points_per_currency' => 'integer',
        'min_redeem_points' => 'integer',
        'welcome_bonus_points' => 'integer',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'company_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

