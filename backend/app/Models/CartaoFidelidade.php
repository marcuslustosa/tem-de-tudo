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
        'regra_ganho',
        'pontos_por_visita',
        'pontos_necessarios',
        'recompensa_descricao',
        'categoria',
        'carimbos_atual',
        'carimbos_necessarios',
        'meta_pontos',
        'recompensa',
        'validade',
        'data_expiracao',
        'ativo',
    ];

    protected $casts = [
        'carimbos_atual' => 'integer',
        'carimbos_necessarios' => 'integer',
        'pontos_por_visita' => 'integer',
        'pontos_necessarios' => 'integer',
        'meta_pontos' => 'integer',
        'ativo' => 'boolean',
        'validade' => 'date',
        'data_expiracao' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function progressos()
    {
        return $this->hasMany(CartaoFidelidadeProgresso::class, 'cartao_fidelidade_id');
    }

    public function movimentos()
    {
        return $this->hasMany(CartaoFidelidadeMovimento::class, 'cartao_fidelidade_id');
    }

    public function isExpired(): bool
    {
        return $this->data_expiracao !== null && $this->data_expiracao->isPast();
    }

    public function isOperationallyAvailable(): bool
    {
        return (bool) $this->ativo && !$this->isExpired();
    }
}
