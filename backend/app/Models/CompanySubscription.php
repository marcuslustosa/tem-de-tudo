<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class CompanySubscription extends Model
{
    use HasFactory;

    protected $table = 'subscriptions';

    protected $fillable = [
        'company_id',
        'subscription_plan_id',
        'status',
        'started_at',
        'trial_ends_at',
        'current_period_start',
        'current_period_end',
        'canceled_at',
        'cancellation_reason',
        'billing_day',
        'grace_period_days',
    ];

    protected $casts = [
        'started_at' => 'date',
        'trial_ends_at' => 'date',
        'current_period_start' => 'date',
        'current_period_end' => 'date',
        'canceled_at' => 'date',
        'billing_day' => 'integer',
        'grace_period_days' => 'integer',
    ];

    /**
     * Status possíveis
     */
    const STATUS_TRIAL = 'trial';
    const STATUS_ACTIVE = 'active';
    const STATUS_PAST_DUE = 'past_due';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_CANCELED = 'canceled';

    /**
     * Relacionamentos
     */
    public function company()
    {
        return $this->belongsTo(Empresa::class, 'company_id');
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'subscription_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_TRIAL, self::STATUS_ACTIVE]);
    }

    public function scopeBlocked($query)
    {
        return $query->whereIn('status', [self::STATUS_SUSPENDED, self::STATUS_CANCELED]);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_PAST_DUE);
    }

    /**
     * Helpers
     */
    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_TRIAL, self::STATUS_ACTIVE]);
    }

    public function isBlocked(): bool
    {
        return in_array($this->status, [self::STATUS_SUSPENDED, self::STATUS_CANCELED]);
    }

    public function isTrial(): bool
    {
        return $this->status === self::STATUS_TRIAL 
            && $this->trial_ends_at 
            && Carbon::parse($this->trial_ends_at)->isFuture();
    }

    public function trialDaysRemaining(): int
    {
        if (!$this->isTrial()) {
            return 0;
        }

        return Carbon::now()->diffInDays($this->trial_ends_at, false);
    }

    public function gracePeriodExpired(): bool
    {
        if ($this->status !== self::STATUS_PAST_DUE) {
            return false;
        }

        $latestInvoice = $this->invoices()
            ->where('status', 'overdue')
            ->orderBy('due_date', 'desc')
            ->first();

        if (!$latestInvoice) {
            return false;
        }

        $daysSinceDue = Carbon::now()->diffInDays($latestInvoice->due_date);
        return $daysSinceDue > $this->grace_period_days;
    }
}
