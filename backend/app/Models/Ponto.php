<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'pontos' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
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
     * Relacionamento com check-in
     */
    public function checkin()
    {
        return $this->belongsTo(CheckIn::class);
    }

    /**
     * Relacionamento com cupom
     */
    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Scope para pontos positivos (ganhos)
     */
    public function scopeGanhos($query)
    {
        return $query->where('pontos', '>', 0);
    }

    /**
     * Scope para pontos negativos (resgates)
     */
    public function scopeResgates($query)
    {
        return $query->where('pontos', '<', 0);
    }

    /**
     * Scope por tipo
     */
    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }
}
