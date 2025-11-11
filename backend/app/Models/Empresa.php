<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome',
        'endereco',
        'telefone',
        'cnpj',
        'logo',
        'descricao',
        'points_multiplier',
        'ativo',
        'owner_id'
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'points_multiplier' => 'decimal:2'
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
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
        // Usar multiplicador configurado ou padrão de 1.0
        return $this->points_multiplier ?? 1.0;
    }

    /**
     * Verificar se empresa está ativa
     */
    public function isAtiva(): bool
    {
        return $this->ativo ?? true;
    }

    /**
     * Relacionamento com QR codes
     */
    public function qrCodes()
    {
        return $this->hasMany(QRCode::class);
    }

    /**
     * Relacionamento com discount levels
     */
    public function discountLevels()
    {
        return $this->hasMany(DiscountLevel::class);
    }
}
