<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckIn extends Model
{
    use HasFactory;

    protected $table = 'check_ins';

    protected $fillable = [
        'user_id',
        'empresa_id',
        'valor_compra',
        'pontos_calculados',
        'foto_cupom',
        'latitude',
        'longitude',
        'observacoes',
        'status',
        'codigo_validacao',
        'aprovado_em',
        'aprovado_por',
        'rejeitado_em',
        'rejeitado_por',
        'motivo_rejeicao'
    ];

    protected $casts = [
        'valor_compra' => 'decimal:2',
        'latitude' => 'decimal:8,6',
        'longitude' => 'decimal:8,6',
        'aprovado_em' => 'datetime',
        'rejeitado_em' => 'datetime'
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
     * Relacionamento com o usuário que aprovou
     */
    public function aprovadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprovado_por');
    }

    /**
     * Relacionamento com o usuário que rejeitou
     */
    public function rejeitadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejeitado_por');
    }

    /**
     * Scopes
     */
    public function scopePendentes($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAprovados($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejeitados($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Acessors
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Pendente',
            'approved' => 'Aprovado',
            'rejected' => 'Rejeitado',
            default => 'Desconhecido'
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => '#f59e0b',
            'approved' => '#10b981',
            'rejected' => '#ef4444',
            default => '#6b7280'
        };
    }

    /**
     * URL da foto do cupom
     */
    public function getFotoCupomUrlAttribute(): ?string
    {
        return $this->foto_cupom ? asset('storage/' . $this->foto_cupom) : null;
    }
}