<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscountLevel extends Model
{
    use HasFactory;

    protected $table = 'discount_levels';

    protected $fillable = [
        'empresa_id',
        'name',
        'min_points',
        'max_points',
        'discount_percentage',
        'discount_value',
        'active',
        'description'
    ];

    protected $casts = [
        'min_points' => 'integer',
        'max_points' => 'integer',
        'discount_percentage' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'active' => 'boolean'
    ];

    /**
     * Relacionamento com empresa
     */
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * Scope para níveis ativos
     */
    public function scopeAtivos($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope por empresa
     */
    public function scopePorEmpresa($query, $empresaId)
    {
        return $query->where('empresa_id', $empresaId);
    }

    /**
     * Verificar se nível está ativo
     */
    public function estaAtivo(): bool
    {
        return $this->active;
    }

    /**
     * Calcular desconto baseado no valor
     */
    public function calcularDesconto(float $valor): float
    {
        if ($this->discount_percentage > 0) {
            return $valor * ($this->discount_percentage / 100);
        }

        return min($this->discount_value, $valor);
    }

    /**
     * Verificar se pontos se encaixam no nível
     */
    public function pontosSeEncaixam(int $pontos): bool
    {
        return $pontos >= $this->min_points &&
               ($this->max_points === null || $pontos <= $this->max_points);
    }
}
