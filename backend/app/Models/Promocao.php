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
        'desconto',
        'pontos_necessarios',
        'data_inicio',
        'data_fim',
        'validade',
        'imagem',
        'status',
        'visualizacoes',
        'resgates',
        'usos',
        'ativo',
        'data_envio',
        'total_envios',
        'percentual_desconto',
        'valor_desconto',
        'tipo_recompensa'
    ];

    protected $casts = [
        'desconto' => 'decimal:2',
        'pontos_necessarios' => 'integer',
        'data_inicio' => 'date',
        'data_fim' => 'date',
        'validade' => 'date',
        'visualizacoes' => 'integer',
        'resgates' => 'integer',
        'usos' => 'integer',
        'ativo' => 'boolean',
        'data_envio' => 'datetime',
        'total_envios' => 'integer',
        'percentual_desconto' => 'decimal:2',
        'valor_desconto' => 'decimal:2',
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
