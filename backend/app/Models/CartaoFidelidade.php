<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartaoFidelidade extends Model
{
    use HasFactory;

    protected $table = 'cartoes_fidelidade';

    protected $fillable = [
        'user_id',
        'empresa_id',
        'titulo',
        'descricao',
        'categoria',
        'carimbos_atual',
        'carimbos_necessarios',
        'meta_pontos',
        'recompensa',
        'validade',
        'ativo'
    ];

    protected $casts = [
        'carimbos_atual' => 'integer',
        'carimbos_necessarios' => 'integer',
        'meta_pontos' => 'integer',
        'ativo' => 'boolean',
        'validade' => 'date'
    ];

    /**
     * Relacionamento com User (Cliente)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relacionamento com Empresa
     */
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
}
