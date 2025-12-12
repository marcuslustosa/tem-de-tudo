<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificacaoPush extends Model
{
    use HasFactory;

    protected $table = 'notificacoes_push';

    protected $fillable = [
        'user_id',
        'empresa_id',
        'tipo',
        'titulo',
        'mensagem',
        'imagem',
        'enviado',
        'data_envio'
    ];

    protected $casts = [
        'enviado' => 'boolean',
        'data_envio' => 'datetime',
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
     * Marcar como enviada
     */
    public function marcarComoEnviada()
    {
        $this->enviado = true;
        $this->data_envio = now();
        $this->save();
    }

    /**
     * Scope para notificações pendentes
     */
    public function scopePendentes($query)
    {
        return $query->where('enviado', false);
    }

    /**
     * Scope para notificações enviadas
     */
    public function scopeEnviadas($query)
    {
        return $query->where('enviado', true);
    }
}
