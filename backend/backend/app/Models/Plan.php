<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'price',
        'establishments_limit',
        'checkins_limit',
        'benefits_limit',
        'points_multiplier',
        'features',
        'active'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'points_multiplier' => 'decimal:2',
        'features' => 'array',
        'active' => 'boolean'
    ];

    /**
     * Relacionamento com assinaturas
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Verificar se o plano permite estabelecimentos ilimitados
     */
    public function hasUnlimitedEstablishments(): bool
    {
        return is_null($this->establishments_limit);
    }

    /**
     * Verificar se o plano permite check-ins ilimitados
     */
    public function hasUnlimitedCheckins(): bool
    {
        return is_null($this->checkins_limit);
    }

    /**
     * Verificar se o plano possui uma feature específica
     */
    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? []);
    }

    /**
     * Obter planos ativos
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Planos predefinidos do sistema
     */
    public static function getDefaultPlans(): array
    {
        return [
            [
                'name' => 'Básico',
                'slug' => 'basico',
                'price' => 49.90,
                'establishments_limit' => 1,
                'checkins_limit' => 500,
                'benefits_limit' => 3,
                'points_multiplier' => 1.0,
                'features' => [
                    'qr_code_static',
                    'basic_reports',
                    'email_support'
                ]
            ],
            [
                'name' => 'Premium',
                'slug' => 'premium',
                'price' => 149.90,
                'establishments_limit' => 3,
                'checkins_limit' => null,
                'benefits_limit' => null,
                'points_multiplier' => 1.5,
                'features' => [
                    'qr_code_dynamic',
                    'advanced_reports',
                    'push_notifications',
                    'social_integration',
                    'priority_support'
                ]
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'price' => 299.90,
                'establishments_limit' => null,
                'checkins_limit' => null,
                'benefits_limit' => null,
                'points_multiplier' => 2.0,
                'features' => [
                    'qr_code_dynamic',
                    'analytics_dashboard',
                    'api_integration',
                    'white_label',
                    'account_manager',
                    'ai_campaigns'
                ]
            ],
            [
                'name' => 'Franquia',
                'slug' => 'franquia',
                'price' => 599.90,
                'establishments_limit' => null,
                'checkins_limit' => null,
                'benefits_limit' => null,
                'points_multiplier' => 2.0,
                'features' => [
                    'multi_store',
                    'shared_points',
                    'consolidated_reports',
                    'centralized_management',
                    'training_included',
                    'support_24_7'
                ]
            ]
        ];
    }
}