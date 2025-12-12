<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BonusAdesao extends Model
{
    use HasFactory;

    protected $table = 'bonus_adesao';

    protected $fillable = [
        'empresa_id',
        'titulo',
        'descricao',
        'tipo_desconto',
        'valor_desconto',
        'imagem',
        'ativo'
    ];

    protected $casts = [
        'valor_desconto' => 'decimal:2',
        'ativo' => 'boolean',
    ];

    /**
     * Relacionamento com Empresa
     */
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * Formatar desconto para exibição
     */
    public function getDescontoFormatadoAttribute()
    {
        if ($this->tipo_desconto === 'porcentagem') {
            return $this->valor_desconto . '%';
        }
        return 'R$ ' . number_format($this->valor_desconto, 2, ',', '.');
    }
}
