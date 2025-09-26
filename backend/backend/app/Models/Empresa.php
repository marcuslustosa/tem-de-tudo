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
        'plan_id',
        'subscription_id',
        'category'
    ];

    protected $casts = [
        'photos' => 'array',
        'services' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relacionamento com plano
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Relacionamento com assinatura
     */
    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Relacionamento com níveis de desconto
     */
    public function discountLevels()
    {
        return $this->hasMany(DiscountLevel::class, 'empresa_id');
    }

    /**
     * Relacionamento com QR Codes
     */
    public function qrCodes()
    {
        return $this->hasMany(QRCode::class, 'empresa_id');
    }

    /**
     * Criar níveis de desconto padrão quando empresa é criada
     */
    public static function boot()
    {
        parent::boot();
        
        static::created(function ($empresa) {
            // Criar níveis de desconto padrão
            \App\Models\DiscountLevel::createDefaultLevels($empresa->id);
        });
    }

    /**
     * Verificar se empresa tem plano ativo
     */
    public function hasActivePlan()
    {
        return $this->subscription && $this->subscription->isActive();
    }

    /**
     * Obter multiplicador de pontos baseado no plano
     */
    public function getPointsMultiplier()
    {
        if (!$this->plan) {
            return 1; // Multiplicador padrão
        }

        $multipliers = [
            'basico' => 1,
            'premium' => 1.5,
            'enterprise' => 2,
            'franquia' => 2
        ];

        return $multipliers[$this->plan->slug] ?? 1;
    }

    public function pontos()
    {
        return $this->hasMany(Ponto::class);
    }

    public function ofertas()
    {
        return $this->hasMany(Oferta::class);
    }
}
