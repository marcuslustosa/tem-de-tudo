<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InscricaoEmpresa extends Model
{
    use HasFactory;

    protected $table = 'inscricoes_empresa';

    protected $fillable = [
        'user_id',
        'empresa_id',
        'data_inscricao',
        'ultima_visita',
        'bonus_adesao_resgatado'
    ];

    protected $casts = [
        'data_inscricao' => 'datetime',
        'ultima_visita' => 'datetime',
        'bonus_adesao_resgatado' => 'boolean',
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

    /**
     * Verificar se cliente já resgatou bônus de adesão
     */
    public function jaResgatouBonus()
    {
        return $this->bonus_adesao_resgatado;
    }

    /**
     * Calcular dias desde última visita
     */
    public function diasDesdeUltimaVisita()
    {
        if (!$this->ultima_visita) {
            return $this->data_inscricao->diffInDays(now());
        }
        return $this->ultima_visita->diffInDays(now());
    }
}
