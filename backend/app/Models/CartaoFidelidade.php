<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartaoFidelidade extends Model
{
    use HasFactory;

    protected $table = 'cartoes_fidelidade';

    protected $fillable = [
        'empresa_id',
        'titulo',
        'descricao',
        'meta_pontos',
        'recompensa',
        'ativo'
    ];

    protected $casts = [
        'meta_pontos' => 'integer',
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
     * Relacionamento com Progressos dos Clientes
     */
    public function progressos()
    {
        return $this->hasMany(CartaoFidelidadeProgresso::class);
    }

    /**
     * Obter progresso de um cliente específico
     */
    public function progressoDoCliente($userId)
    {
        return $this->progressos()->where('user_id', $userId)->first();
    }
}
