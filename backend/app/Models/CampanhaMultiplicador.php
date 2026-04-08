<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampanhaMultiplicador extends Model
{
    protected $fillable = [
        'empresa_id',
        'nome',
        'descricao',
        'multiplicador',
        'data_inicio',
        'data_fim',
        'ativo',
    ];

    protected $casts = [
        'data_inicio'   => 'datetime',
        'data_fim'      => 'datetime',
        'ativo'         => 'boolean',
        'multiplicador' => 'float',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function scopeAtiva($query)
    {
        return $query
            ->where('ativo', true)
            ->where('data_inicio', '<=', now())
            ->where('data_fim', '>=', now());
    }
}
