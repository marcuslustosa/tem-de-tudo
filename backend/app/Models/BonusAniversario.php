<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BonusAniversario extends Model
{
    use HasFactory;

    protected $table = 'bonus_aniversario';

    protected $fillable = [
        'empresa_id',
        'titulo',
        'descricao',
        'presente',
        'imagem',
        'ativo'
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    /**
     * Relacionamento com Empresa
     */
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
}
