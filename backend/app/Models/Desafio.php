<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Desafio extends Model
{
    protected $fillable = [
        'empresa_id', 'nome', 'descricao', 'tipo', 'meta',
        'recompensa_pontos', 'recompensa_descricao',
        'data_inicio', 'data_fim', 'ativo',
    ];

    protected function casts(): array
    {
        return [
            'data_inicio' => 'datetime',
            'data_fim'    => 'datetime',
            'ativo'       => 'boolean',
            'meta'        => 'integer',
            'recompensa_pontos' => 'integer',
        ];
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function progressos()
    {
        return $this->hasMany(DesafioProgresso::class);
    }

    public function scopeAtivos($query)
    {
        return $query->where('ativo', true)
                     ->where('data_inicio', '<=', now())
                     ->where('data_fim', '>=', now());
    }
}
