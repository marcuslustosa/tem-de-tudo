<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartaoFidelidadeProgresso extends Model
{
    use HasFactory;

    protected $table = 'cartoes_fidelidade_progresso';

    protected $fillable = [
        'user_id',
        'cartao_fidelidade_id',
        'pontos_atuais',
        'vezes_resgatado',
        'ultimo_ponto',
    ];

    protected $casts = [
        'pontos_atuais' => 'integer',
        'vezes_resgatado' => 'integer',
        'ultimo_ponto' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cartao()
    {
        return $this->belongsTo(CartaoFidelidade::class, 'cartao_fidelidade_id');
    }

    public function cartaoFidelidade()
    {
        return $this->belongsTo(CartaoFidelidade::class, 'cartao_fidelidade_id');
    }

    public function getPorcentagemProgressoAttribute()
    {
        $meta = max(1, (int) ($this->cartao?->pontos_necessarios ?? $this->cartao?->meta_pontos ?? 1));

        return ($this->pontos_atuais / $meta) * 100;
    }
}
