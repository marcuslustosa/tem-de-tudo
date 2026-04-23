<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class RedemptionIntent extends Model
{
    protected $fillable = [
        'intent_id',
        'user_id',
        'company_id',
        'reserved_ledger_id',
        'confirmed_ledger_id',
        'reversal_ledger_id',
        'points_requested',
        'points_confirmed',
        'status',
        'redemption_type',
        'metadata',
        'pdv_operator_id',
        'pdv_terminal_id',
        'requested_at',
        'reserved_at',
        'confirmed_at',
        'canceled_at',
        'reversed_at',
        'expires_at',
        'cancellation_reason',
        'reversal_reason',
        'reversed_by',
    ];

    protected $casts = [
        'metadata' => 'array',
        'points_requested' => 'integer',
        'points_confirmed' => 'integer',
        'requested_at' => 'datetime',
        'reserved_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'canceled_at' => 'datetime',
        'reversed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    // Status
    const STATUS_PENDING = 'pending'; // Solicitado, aguardando reserva
    const STATUS_RESERVED = 'reserved'; // Pontos reservados (bloqueados)
    const STATUS_CONFIRMED = 'confirmed'; // Resgate confirmado (pontos debitados)
    const STATUS_CANCELED = 'canceled'; // Cancelado antes de confirmar
    const STATUS_REVERSED = 'reversed'; // Estornado após confirmação
    const STATUS_EXPIRED = 'expired'; // Reserva expirou

    // Redemption types
    const TYPE_PRODUCT = 'product';
    const TYPE_DISCOUNT = 'discount';
    const TYPE_CASHBACK = 'cashback';

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (!$model->intent_id) {
                $model->intent_id = (string) Str::uuid();
            }
            if (!$model->requested_at) {
                $model->requested_at = now();
            }
        });
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'company_id');
    }

    public function pdvOperator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pdv_operator_id');
    }

    public function reservedLedger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'reserved_ledger_id');
    }

    public function confirmedLedger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'confirmed_ledger_id');
    }

    public function reversalLedger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'reversal_ledger_id');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeReserved($query)
    {
        return $query->where('status', self::STATUS_RESERVED);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', self::STATUS_RESERVED)
            ->where('expires_at', '<', now());
    }

    // Helpers
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isReserved(): bool
    {
        return $this->status === self::STATUS_RESERVED;
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isCanceled(): bool
    {
        return $this->status === self::STATUS_CANCELED;
    }

    public function isReversed(): bool
    {
        return $this->status === self::STATUS_REVERSED;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_RESERVED && 
               $this->expires_at && 
               $this->expires_at->isPast();
    }

    public function canBeConfirmed(): bool
    {
        return $this->status === self::STATUS_RESERVED && !$this->isExpired();
    }

    public function canBeCanceled(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_RESERVED]);
    }

    public function canBeReversed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }
}
