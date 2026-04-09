<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DesafioProgresso extends Model
{
    protected $table = 'desafio_progresso';

    protected $fillable = [
        'user_id', 'desafio_id', 'progresso_atual',
        'concluido', 'concluido_em', 'recompensa_dada',
    ];

    protected function casts(): array
    {
        return [
            'concluido'       => 'boolean',
            'recompensa_dada' => 'boolean',
            'concluido_em'    => 'datetime',
            'progresso_atual' => 'integer',
        ];
    }

    public function desafio()
    {
        return $this->belongsTo(Desafio::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
