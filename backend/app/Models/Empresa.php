<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome',
        'cnpj',
        'endereco',
        'telefone',
        'email',
        'photos',
        'services',
        'user_id',
        'qr_code',
        'plan',
        'settings',
        'active',
        'points_multiplier'
    ];

    protected $casts = [
        'photos' => 'array',
        'services' => 'array',
        'active' => 'boolean',
        'points_multiplier' => 'decimal:2',
        'settings' => 'array'
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
        // Usar multiplicador configurado ou padrão de 1.0
        return $this->points_multiplier ?? 1.0;
    }

    /**
     * Verificar se empresa está ativa
     */
    public function isAtiva(): bool
    {
        return $this->active ?? true;
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
