<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'monthly_price_cents',
        'features',
        'max_users',
        'max_transactions_per_month',
        'trial_days',
        'is_active',
    ];

    protected $casts = [
        'features' => 'array',
        'monthly_price_cents' => 'integer',
        'max_users' => 'integer',
        'max_transactions_per_month' => 'integer',
        'trial_days' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Relacionamentos
     */
    public function subscriptions()
    {
        return $this->hasMany(CompanySubscription::class, 'subscription_plan_id');
    }

    /**
     * Helpers
     */
    public function getFormattedPriceAttribute(): string
    {
        return 'R$ ' . number_format($this->monthly_price_cents / 100, 2, ',', '.');
    }

    public function isUnlimited(string $feature): bool
    {
        return match ($feature) {
            'users' => $this->max_users === null,
            'transactions' => $this->max_transactions_per_month === null,
            default => false,
        };
    }
}
