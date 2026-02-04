<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pagamento extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'empresa_id',
        'produto_id',
        'mercadopago_payment_id',
        'status',
        'valor',
        'valor_desconto',
        'valor_final',
        'pontos_gerados',
        'metodo_pagamento',
        'detalhes_pagamento',
        'qr_code_data',
        'link_pagamento',
        'data_expiracao',
        'webhook_events'
    ];

    protected $casts = [
        'valor' => 'integer', // em centavos
        'valor_desconto' => 'integer',
        'valor_final' => 'integer',
        'pontos_gerados' => 'integer',
        'detalhes_pagamento' => 'json',
        'webhook_events' => 'json',
        'data_expiracao' => 'datetime'
    ];

    // Relacionamentos
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function produto()
    {
        return $this->belongsTo(Produto::class);
    }

    // Status do pagamento
    public function isPendente()
    {
        return $this->status === 'pending';
    }

    public function isAprovado()
    {
        return $this->status === 'approved';
    }

    public function isCancelado()
    {
        return in_array($this->status, ['cancelled', 'rejected']);
    }

    // Calcular pontos baseado no valor gasto
    public function calcularPontos()
    {
        $user = $this->user;
        $nivel_info = $user->calcularNivel();
        $multiplicador = $nivel_info['multiplicador'];
        
        // 1 ponto a cada R$ 1,00 gasto
        $pontos_base = intval($this->valor_final / 100);
        
        return intval($pontos_base * $multiplicador);
    }

    // Formatar valor para exibição
    public function getValorFormatadoAttribute()
    {
        return 'R$ ' . number_format($this->valor / 100, 2, ',', '.');
    }

    public function getValorFinalFormatadoAttribute()
    {
        return 'R$ ' . number_format($this->valor_final / 100, 2, ',', '.');
    }

    // Scopes
    public function scopeAprovados($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePendentes($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeDoUsuario($query, $user_id)
    {
        return $query->where('user_id', $user_id);
    }
}