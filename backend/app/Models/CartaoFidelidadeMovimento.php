<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartaoFidelidadeMovimento extends Model
{
    use HasFactory;

    public const TYPE_EARNED = 'earned';
    public const TYPE_REDEEMED = 'redeemed';
    public const TYPE_ADJUSTED = 'adjusted';

    protected $table = 'cartoes_fidelidade_movimentos';

    protected $fillable = [
        'cartao_fidelidade_id',
        'empresa_id',
        'user_id',
        'pontos',
        'tipo',
        'descricao',
        'created_by',
    ];

    protected $casts = [
        'pontos' => 'integer',
    ];

    public function cartao()
    {
        return $this->belongsTo(CartaoFidelidade::class, 'cartao_fidelidade_id');
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function cliente()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function autor()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
