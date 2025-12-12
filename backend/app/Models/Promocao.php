<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promocao extends Model
{
    use HasFactory;

    protected $table = 'promocoes';

    protected $fillable = [
        'empresa_id',
        'titulo',
        'descricao',
        'imagem',
        'ativo',
        'data_envio',
        'total_envios'
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'data_envio' => 'datetime',
        'total_envios' => 'integer',
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
    const MAX_TITULO = 100;
    const MAX_DESCRICAO = 500;

    /**
     * Verificar se foi enviada
     */
    public function foiEnviada()
    {
        return $this->data_envio !== null;
    }
}
