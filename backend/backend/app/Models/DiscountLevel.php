<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscountLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id',
        'points_required',
        'discount_percentage',
        'title',
        'description',
        'is_active',
        'applies_to_all_products',
        'applies_to_all_services',
        'specific_categories'
    ];

    protected $casts = [
        'points_required' => 'integer',
        'discount_percentage' => 'decimal:2',
        'is_active' => 'boolean',
        'applies_to_all_products' => 'boolean',
        'applies_to_all_services' => 'boolean',
        'specific_categories' => 'array'
    ];

    /**
     * Relacionamento com Empresa
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * Criar níveis padrão para uma empresa
     */
    public static function createDefaultLevels($empresaId)
    {
        $defaultLevels = [
            [
                'points_required' => 500,
                'discount_percentage' => 5.00,
                'title' => 'Bronze',
                'description' => '5% de desconto em todas as compras'
            ],
            [
                'points_required' => 1000,
                'discount_percentage' => 10.00,
                'title' => 'Prata',
                'description' => '10% de desconto em todas as compras'
            ],
            [
                'points_required' => 2000,
                'discount_percentage' => 15.00,
                'title' => 'Ouro',
                'description' => '15% de desconto em todas as compras'
            ],
            [
                'points_required' => 5000,
                'discount_percentage' => 20.00,
                'title' => 'Platina',
                'description' => '20% de desconto em todas as compras'
            ],
            [
                'points_required' => 10000,
                'discount_percentage' => 25.00,
                'title' => 'Diamante',
                'description' => '25% de desconto VIP'
            ]
        ];

        foreach ($defaultLevels as $level) {
            self::create([
                'empresa_id' => $empresaId,
                'points_required' => $level['points_required'],
                'discount_percentage' => $level['discount_percentage'],
                'title' => $level['title'],
                'description' => $level['description'],
                'is_active' => true,
                'applies_to_all_products' => true,
                'applies_to_all_services' => true
            ]);
        }
    }

    /**
     * Obter níveis de desconto ativos ordenados por pontos
     */
    public static function getActiveLevelsForCompany($empresaId)
    {
        return self::where('empresa_id', $empresaId)
                   ->where('is_active', true)
                   ->orderBy('points_required', 'asc')
                   ->get();
    }

    /**
     * Calcular melhor desconto disponível para pontos do usuário
     */
    public static function getBestDiscountForPoints($empresaId, $userPoints)
    {
        return self::where('empresa_id', $empresaId)
                   ->where('is_active', true)
                   ->where('points_required', '<=', $userPoints)
                   ->orderBy('discount_percentage', 'desc')
                   ->first();
    }

    /**
     * Próximo nível de desconto
     */
    public static function getNextLevel($empresaId, $userPoints)
    {
        return self::where('empresa_id', $empresaId)
                   ->where('is_active', true)
                   ->where('points_required', '>', $userPoints)
                   ->orderBy('points_required', 'asc')
                   ->first();
    }

    /**
     * Calcular desconto em uma compra
     */
    public function calculateDiscount($purchaseAmount)
    {
        return round($purchaseAmount * ($this->discount_percentage / 100), 2);
    }

    /**
     * Verificar se o desconto se aplica a categoria específica
     */
    public function appliesTo($category = null)
    {
        // Se aplica a tudo
        if ($this->applies_to_all_products && $this->applies_to_all_services) {
            return true;
        }

        // Se categoria específica foi fornecida
        if ($category) {
            return in_array($category, $this->specific_categories ?? []);
        }

        return false;
    }

    /**
     * Scope para níveis ativos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para empresa
     */
    public function scopeForCompany($query, $empresaId)
    {
        return $query->where('empresa_id', $empresaId);
    }

    /**
     * Accessor para formatação da porcentagem
     */
    public function getFormattedDiscountAttribute()
    {
        return $this->discount_percentage . '%';
    }

    /**
     * Accessor para formatação de pontos
     */
    public function getFormattedPointsAttribute()
    {
        return number_format($this->points_required, 0, ',', '.');
    }
}