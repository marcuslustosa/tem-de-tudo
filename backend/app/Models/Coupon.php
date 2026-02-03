<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'empresa_id',
        'promocao_id',
        'checkin_id',
        'codigo',
        'tipo',
        'descricao',
        'custo_pontos',
        'valor_desconto',
        'porcentagem_desconto',
        'percentual_desconto',
        'tipo_recompensa',
        'data_validade',
        'usado',
        'data_uso',
        'status',
        'expira_em',
        'usado_em',
        'dados_extra'
    ];

    protected $casts = [
        'custo_pontos' => 'integer',
        'valor_desconto' => 'decimal:2',
        'porcentagem_desconto' => 'decimal:2',
        'percentual_desconto' => 'decimal:2',
        'usado' => 'boolean',
        'data_validade' => 'datetime',
        'data_uso' => 'datetime',
        'expira_em' => 'datetime',
        'usado_em' => 'datetime',
        'dados_extra' => 'array'
    ];

    /**
     * Relacionamento com usuário
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relacionamento com empresa
     */
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * Relacionamento com promoção
     */
    public function promocao()
    {
        return $this->belongsTo(Promocao::class);
    }

    /**
     * Relacionamento com check-in
     */
    public function checkin()
    {
        return $this->belongsTo(CheckIn::class);
    }

    /**
     * Relacionamento com pontos
     */
    public function pontos()
    {
        return $this->hasMany(Ponto::class);
    }

    /**
     * Scope para cupons ativos
     */
    public function scopeAtivos($query)
    {
        return $query->where('status', 'active')
                    ->where(function($q) {
                        $q->whereNull('expira_em')
                          ->orWhere('expira_em', '>', now());
                    });
    }

    /**
     * Scope para cupons expirados
     */
    public function scopeExpirados($query)
    {
        return $query->where('status', 'expired')
                    ->orWhere('expira_em', '<=', now());
    }

    /**
     * Scope para cupons usados
     */
    public function scopeUsados($query)
    {
        return $query->where('status', 'used');
    }

    /**
     * Verificar se cupom está ativo
     */
    public function estaAtivo(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->expira_em && $this->expira_em->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Verificar se cupom expirou
     */
    public function expirou(): bool
    {
        return $this->expira_em && $this->expira_em->isPast();
    }

    /**
     * Usar cupom
     */
    public function usar(): bool
    {
        if (!$this->estaAtivo()) {
            return false;
        }

        $this->update([
            'status' => 'used',
            'usado_em' => now()
        ]);

        return true;
    }
}
