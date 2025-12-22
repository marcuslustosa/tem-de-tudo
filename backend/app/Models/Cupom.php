<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cupom extends Model
{
    use HasFactory;

    protected $table = 'cupons';

    protected $fillable = [
        'user_id',
        'empresa_id',
        'codigo',
        'titulo',
        'descricao',
        'valor_desconto',
        'tipo_desconto',
        'valido_ate',
        'usado',
        'usado_em',
        'ativo'
    ];

    protected $casts = [
        'valido_ate' => 'datetime',
        'usado_em' => 'datetime',
        'usado' => 'boolean',
        'ativo' => 'boolean',
        'valor_desconto' => 'decimal:2'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function isValid()
    {
        return $this->ativo 
            && !$this->usado 
            && $this->valido_ate->isFuture();
    }

    public function marcarComoUsado()
    {
        $this->update([
            'usado' => true,
            'usado_em' => now()
        ]);
    }
}
