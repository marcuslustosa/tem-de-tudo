<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Avaliacao extends Model
{
    use HasFactory;

    protected $table = 'avaliacoes';

    protected $fillable = [
        'user_id',
        'empresa_id',
        'estrelas',
        'comentario'
    ];

    protected $casts = [
        'estrelas' => 'integer',
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

    /**
     * Atualizar média de avaliação da empresa
     */
    public static function boot()
    {
        parent::boot();

        static::saved(function ($avaliacao) {
            $avaliacao->empresa->atualizarAvaliacaoMedia();
        });

        static::deleted(function ($avaliacao) {
            $avaliacao->empresa->atualizarAvaliacaoMedia();
        });
    }
}
