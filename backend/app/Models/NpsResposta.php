<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NpsResposta extends Model
{
    protected $table = 'nps_respostas';

    protected $fillable = [
        'user_id', 'empresa_id', 'promocao_id', 'nota', 'comentario', 'contexto',
    ];

    protected function casts(): array
    {
        return [
            'nota' => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    /** Classificação NPS padrão */
    public function getClassificacaoAttribute(): string
    {
        if ($this->nota >= 9) return 'promotor';
        if ($this->nota >= 7) return 'neutro';
        return 'detrator';
    }
}
