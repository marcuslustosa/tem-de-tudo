<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_id',
        'status',
        'starts_at',
        'ends_at',
        'canceled_at',
        'amount'
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'canceled_at' => 'datetime',
        'amount' => 'decimal:2'
    ];

    /**
     * Relacionamento com usuário
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relacionamento com plano
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Verificar se a assinatura está ativa
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && 
               Carbon::now()->between($this->starts_at, $this->ends_at);
    }

    /**
     * Verificar se a assinatura expirou
     */
    public function isExpired(): bool
    {
        return Carbon::now()->gt($this->ends_at) || $this->status === 'expired';
    }

    /**
     * Verificar se a assinatura foi cancelada
     */
    public function isCanceled(): bool
    {
        return $this->status === 'canceled' && !is_null($this->canceled_at);
    }

    /**
     * Cancelar assinatura
     */
    public function cancel(): bool
    {
        return $this->update([
            'status' => 'canceled',
            'canceled_at' => now()
        ]);
    }

    /**
     * Renovar assinatura
     */
    public function renew(int $months = 1): bool
    {
        $newEndDate = $this->ends_at->addMonths($months);
        
        return $this->update([
            'status' => 'active',
            'ends_at' => $newEndDate,
            'canceled_at' => null
        ]);
    }

    /**
     * Suspender assinatura
     */
    public function suspend(): bool
    {
        return $this->update(['status' => 'suspended']);
    }

    /**
     * Reativar assinatura suspensa
     */
    public function reactivate(): bool
    {
        if ($this->status === 'suspended' && !$this->isExpired()) {
            return $this->update(['status' => 'active']);
        }
        
        return false;
    }

    /**
     * Calcular dias restantes
     */
    public function getDaysRemainingAttribute(): int
    {
        if ($this->isExpired()) {
            return 0;
        }
        
        return Carbon::now()->diffInDays($this->ends_at);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('starts_at', '<=', now())
                    ->where('ends_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('ends_at', '<', now())
                    ->orWhere('status', 'expired');
    }

    public function scopeCanceled($query)
    {
        return $query->where('status', 'canceled');
    }

    /**
     * Boot model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($subscription) {
            if (!$subscription->starts_at) {
                $subscription->starts_at = now();
            }
            
            if (!$subscription->ends_at) {
                $subscription->ends_at = now()->addMonth();
            }
        });
    }
}