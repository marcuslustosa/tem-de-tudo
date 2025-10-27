<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'cnpj',
        'address',
        'phone',
        'email',
        'photos',
        'services',
        'user_id',
        'category',
        'descricao',
        'ativo'
    ];

    protected $casts = [
        'photos' => 'array',
        'services' => 'array',
        'ativo' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relacionamento com check-ins
     */
    public function checkIns()
    {
        return $this->hasMany(CheckIn::class);
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
     * Obter multiplicador de pontos baseado no valor da compra
     */
    public function getPointsMultiplier(float $valorCompra = 0): float
    {
        // Lógica simples: R$ 1,00 = 1 ponto
        return 1;
    }

    /**
     * Verificar se empresa está ativa
     */
    public function isAtiva(): bool
    {
        return $this->ativo ?? true;
    }
}
