<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PontoTransacao extends Model
{
    use HasFactory;

    protected $table = 'ponto_transacoes';

    protected $fillable = [
        'user_id',
        'pontos',
        'tipo',
        'descricao',
        'valor_compra',
        'estabelecimento_id',
    ];

    protected $casts = [
        'pontos' => 'integer',
        'valor_compra' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function estabelecimento()
    {
        return $this->belongsTo(User::class, 'estabelecimento_id');
    }
}
