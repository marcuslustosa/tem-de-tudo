<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'company_id',
        'invoice_number',
        'amount_cents',
        'discount_cents',
        'total_cents',
        'status',
        'due_date',
        'paid_at',
        'payment_method',
        'payment_id',
        'payment_url',
        'payment_metadata',
        'retry_count',
        'last_retry_at',
        'next_retry_at',
        'last_failure_reason',
        'reconciliation_status',
        'external_status',
        'reconciled_at',
        'notes',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'discount_cents' => 'integer',
        'total_cents' => 'integer',
        'due_date' => 'date',
        'paid_at' => 'date',
        'payment_metadata' => 'array',
        'retry_count' => 'integer',
        'last_retry_at' => 'datetime',
        'next_retry_at' => 'datetime',
        'reconciled_at' => 'datetime',
    ];

    /**
     * Status possíveis
     */
    const STATUS_PENDING = 'pending';
    const STATUS_PAID = 'paid';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_CANCELED = 'canceled';
    const STATUS_REFUNDED = 'refunded';

    /**
     * Relacionamentos
     */
    public function subscription()
    {
        return $this->belongsTo(CompanySubscription::class, 'subscription_id');
    }

    public function company()
    {
        return $this->belongsTo(Empresa::class, 'company_id');
    }

    public function notifications()
    {
        return $this->hasMany(BillingNotification::class);
    }

    public function billingEvents()
    {
        return $this->hasMany(BillingEvent::class, 'invoice_id');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_OVERDUE);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /**
     * Helpers
     */
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_OVERDUE 
            || ($this->status === self::STATUS_PENDING && Carbon::parse($this->due_date)->isPast());
    }

    public function daysSinceDue(): int
    {
        if (!$this->isOverdue()) {
            return 0;
        }

        return Carbon::now()->diffInDays($this->due_date);
    }

    public function daysUntilDue(): int
    {
        if ($this->isOverdue()) {
            return 0;
        }

        return Carbon::now()->diffInDays($this->due_date, false);
    }

    public function getFormattedTotalAttribute(): string
    {
        return 'R$ ' . number_format($this->total_cents / 100, 2, ',', '.');
    }

    public function getFormattedAmountAttribute(): string
    {
        return 'R$ ' . number_format($this->amount_cents / 100, 2, ',', '.');
    }

    public function getFormattedDiscountAttribute(): string
    {
        return 'R$ ' . number_format($this->discount_cents / 100, 2, ',', '.');
    }

    /**
     * Gera número de fatura único
     */
    public static function generateInvoiceNumber(): string
    {
        $year = date('Y');
        $lastInvoice = self::where('invoice_number', 'like', "INV-{$year}-%")
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastInvoice 
            ? ((int) substr($lastInvoice->invoice_number, -6)) + 1 
            : 1;

        return sprintf('INV-%s-%06d', $year, $nextNumber);
    }
}
