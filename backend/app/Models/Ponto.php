<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ponto extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'empresa_id',
        'checkin_id',
        'coupon_id',
        'pontos',
        'descricao',
        'tipo'
    ];

    protected $casts = [
        'pontos' => 'integer'
    ];

    /**
     * Relacionamento com User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relacionamento com Empresa
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * Relacionamento com CheckIn
     */
    public function checkin(): BelongsTo
    {
        return $this->belongsTo(CheckIn::class);
    }

    /**
     * Relacionamento com Coupon
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Scopes
     */
    public function scopeGanhos($query)
    {
        return $query->where('pontos', '>', 0);
    }

    public function scopeResgates($query)
    {
        return $query->where('pontos', '<', 0);
    }

    public function scopeDoUsuario($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Accessors
     */
    public function getTipoLabelAttribute(): string
    {
        return match($this->tipo) {
            'earn' => 'Ganho',
            'redeem' => 'Resgate',
            'bonus' => 'Bônus',
            'adjustment' => 'Ajuste',
            default => 'Outros'
        };
    }

    public function getTipoColorAttribute(): string
    {
        return match($this->tipo) {
            'earn' => '#10b981',
            'redeem' => '#f59e0b',
            'bonus' => '#8b5cf6',
            'adjustment' => '#6b7280',
            default => '#6b7280'
        };
    }
}
