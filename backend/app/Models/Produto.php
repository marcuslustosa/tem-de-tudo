<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produto extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id',
        'nome',
        'descricao',
        'preco',
        'categoria',
        'imagem',
        'ativo',
        'estoque',
        'pontos_gerados'
    ];

    protected $casts = [
        'preco' => 'decimal:2',
        'ativo' => 'boolean',
        'estoque' => 'integer',
        'pontos_gerados' => 'integer'
    ];

    /**
     * Relacionamento com empresa
     */
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * Produtos ativos
     */
    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }

    /**
     * Produtos por categoria
     */
    public function scopeCategoria($query, $categoria)
    {
        return $query->where('categoria', $categoria);
    }

    /**
     * Calcular pontos gerados baseado no preço
     */
    public function calcularPontos($multiplicador = 1)
    {
        if ($this->pontos_gerados) {
            return $this->pontos_gerados * $multiplicador;
        }
        
        // Padrão: 1 ponto para cada R$ 10 gastos
        return floor($this->preco / 10) * $multiplicador;
    }

    /**
     * URL da imagem com fallback
     */
    public function getImagemUrlAttribute()
    {
        if ($this->imagem && filter_var($this->imagem, FILTER_VALIDATE_URL)) {
            return $this->imagem;
        }
        
        // Imagem padrão baseada na categoria
        $defaultImages = [
            'alimentacao' => 'https://images.unsplash.com/photo-1546833999-b9f581a1996d?w=400',
            'bebidas' => 'https://images.unsplash.com/photo-1544145945-f90425340c7e?w=400',
            'doces' => 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=400',
            'beleza' => 'https://images.unsplash.com/photo-1596462502278-27bfdc403348?w=400',
            'servicos' => 'https://images.unsplash.com/photo-1559599101-f09722fb4948?w=400',
            'saude' => 'https://images.unsplash.com/photo-1576602976047-174e57a47881?w=400',
        ];
        
        return $defaultImages[$this->categoria] ?? 'https://images.unsplash.com/photo-1560472354-b33ff0c44a43?w=400';
    }
}