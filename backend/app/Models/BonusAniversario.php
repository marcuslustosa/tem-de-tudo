<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BonusAniversario extends Model
{
    use HasFactory;

    protected $table = 'bonus_aniversario';

    protected $fillable = [
        'user_id',
        'empresa_id',
        'pontos',
        'data_resgate',
        'ano',
        'titulo',
        'descricao',
        'presente',
        'imagem',
        'ativo'
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'data_resgate' => 'datetime',
        'ano' => 'integer',
        'pontos' => 'integer'
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
}
