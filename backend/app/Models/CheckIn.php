<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckIn extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'empresa_id',
        'qr_code_id',
        'pontos',
        'data',
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
        'motivo_rejeicao',
        'bonus_applied'
    ];

    protected $casts = [
        'pontos' => 'integer',
        'data' => 'datetime',
        'valor_compra' => 'decimal:2',
        'pontos_calculados' => 'integer',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'aprovado_em' => 'datetime',
        'rejeitado_em' => 'datetime',
        'bonus_applied' => 'boolean'
    ];

    /**
     * Relacionamento com usuário
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relacionamento com empresa
     */
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * Relacionamento com QR code
     */
    public function qrCode()
    {
        return $this->belongsTo(QRCode::class);
    }

    /**
     * Relacionamento com pontos
     */
    public function pontos()
    {
        return $this->hasMany(Ponto::class);
    }

    /**
     * Relacionamento com cupons
     */
    public function cupons()
    {
        return $this->hasMany(Coupon::class);
    }

    /**
     * Scope para check-ins pendentes
     */
    public function scopePendentes($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope para check-ins aprovados
     */
    public function scopeAprovados($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope para check-ins rejeitados
     */
    public function scopeRejeitados($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Verificar se check-in pode ser aprovado
     */
    public function podeSerAprovado(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Verificar se check-in está aprovado
     */
    public function estaAprovado(): bool
    {
        return $this->status === 'approved';
    }
}
