<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Segmento extends Model
{
    protected $fillable = ['nome', 'descricao', 'criterios', 'ativo'];

    protected function casts(): array
    {
        return [
            'criterios' => 'array',
            'ativo'     => 'boolean',
        ];
    }

    public function usuarios()
    {
        return $this->belongsToMany(User::class, 'segmento_usuarios', 'segmento_id', 'user_id')
                    ->withPivot('adicionado_em');
    }
}
