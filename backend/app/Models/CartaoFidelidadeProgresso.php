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
        'ultimo_ponto'
    ];

    protected $casts = [
        'pontos_atuais' => 'integer',
        'vezes_resgatado' => 'integer',
        'ultimo_ponto' => 'datetime',
    ];

    /**
     * Relacionamento com User (Cliente)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relacionamento com Cartão Fidelidade
     */
    public function cartao()
    {
        return $this->belongsTo(CartaoFidelidade::class, 'cartao_fidelidade_id');
    }

    /**
     * Adicionar ponto ao cartão
     */
    public function adicionarPonto()
    {
        $this->pontos_atuais++;
        $this->ultimo_ponto = now();
        
        // Se completou o cartão
        if ($this->pontos_atuais >= $this->cartao->meta_pontos) {
            $this->vezes_resgatado++;
            $this->pontos_atuais = 0; // Resetar
        }
        
        $this->save();
        return $this;
    }

    /**
     * Calcular porcentagem de progresso
     */
    public function getPorcentagemProgressoAttribute()
    {
        $meta = $this->cartao->meta_pontos;
        return ($this->pontos_atuais / $meta) * 100;
    }
}
