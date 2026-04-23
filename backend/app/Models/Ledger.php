<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Ledger - Fonte única e imutável de verdade para pontos.
 * 
 * REGRAS CRÍTICAS:
 * - NUNCA fazer UPDATE ou DELETE direto nesta tabela
 * - Sempre usar LedgerService para criar registros
 * - Idempotency key obrigatória para prevenir duplicação
 */
class Ledger extends Model
{
    use HasFactory;

    /**
     * Tabela sem updated_at (append-only)
     */
    const UPDATED_AT = null;

    protected $table = 'ledger';

    /**
     * Campos permitidos para mass assignment
     */
    protected $fillable = [
        'idempotency_key',
        'user_id',
        'company_id',
        'transaction_type',
        'points',
        'balance_before',
        'balance_after',
        'description',
        'metadata',
        'related_ledger_id',
        'source',
        'created_by',
    ];

    /**
     * Casts
     */
    protected $casts = [
        'points' => 'integer',
        'balance_before' => 'integer',
        'balance_after' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Tipos de transação válidos
     */
    const TYPE_EARN = 'earn';
    const TYPE_EARN_BONUS = 'earn_bonus';
    const TYPE_REDEEM = 'redeem';
    const TYPE_RESERVED = 'reserved';
    const TYPE_RELEASED = 'released';
    const TYPE_ADJUSTMENT = 'adjustment';
    const TYPE_REVERSAL = 'reversal';
    const TYPE_EXPIRATION = 'expiration';
    const TYPE_MIGRATION = 'migration';

    /**
     * Relacionamentos
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Empresa::class, 'company_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function relatedTransaction()
    {
        return $this->belongsTo(Ledger::class, 'related_ledger_id');
    }

    public function reversals()
    {
        return $this->hasMany(Ledger::class, 'related_ledger_id');
    }

    /**
     * Scopes
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('transaction_type', $type);
    }

    public function scopeEarnings($query)
    {
        return $query->whereIn('transaction_type', [self::TYPE_EARN, self::TYPE_EARN_BONUS]);
    }

    public function scopeRedemptions($query)
    {
        return $query->where('transaction_type', self::TYPE_REDEEM);
    }

    public function scopeAfterDate($query, $date)
    {
        return $query->where('created_at', '>=', $date);
    }

    /**
     * Métodos auxiliares
     */
    public function isCredit(): bool
    {
        return $this->points > 0;
    }

    public function isDebit(): bool
    {
        return $this->points < 0;
    }

    public function isReversal(): bool
    {
        return $this->transaction_type === self::TYPE_REVERSAL;
    }

    public function canBeReversed(): bool
    {
        // Só pode reverter earn e redeem, e se não foi revertido ainda
        if (!in_array($this->transaction_type, [self::TYPE_EARN, self::TYPE_REDEEM])) {
            return false;
        }

        // Verifica se já existe reversão
        return $this->reversals()->count() === 0;
    }

    /**
     * Formatar pontos para exibição
     */
    public function getFormattedPointsAttribute(): string
    {
        $sign = $this->points >= 0 ? '+' : '';
        return $sign . number_format($this->points, 0, ',', '.');
    }

    /**
     * Previne UPDATE e DELETE acidentais
     */
    public function update(array $attributes = [], array $options = [])
    {
        throw new \Exception('Ledger é imutável. Use LedgerService para criar reversão.');
    }

    public function delete()
    {
        throw new \Exception('Ledger é imutável. Use LedgerService para criar reversão.');
    }
}
