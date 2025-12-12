<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LembreteAusencia extends Model
{
    use HasFactory;

    protected $table = 'lembretes_ausencia';

    protected $fillable = [
        'empresa_id',
        'dias_ausencia',
        'titulo',
        'mensagem',
        'ativo'
    ];

    protected $casts = [
        'dias_ausencia' => 'integer',
        'ativo' => 'boolean',
    ];

    /**
     * Relacionamento com Empresa
     */
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * Validação de tamanho de texto
     */
    const MAX_MENSAGEM = 300;
}
