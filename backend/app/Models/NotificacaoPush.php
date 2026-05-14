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
        'promocao_id',
        'bonus_aniversario_id',
        'lembrete_id',
        'tipo',
        'titulo',
        'mensagem',
        'imagem',
        'status',
        'erro',
        'enviado',
        'data_envio',
    ];

    protected $casts = [
        'enviado' => 'boolean',
        'data_envio' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function promocao()
    {
        return $this->belongsTo(Promocao::class, 'promocao_id');
    }

    public function bonusAniversario()
    {
        return $this->belongsTo(BonusAniversario::class, 'bonus_aniversario_id');
    }

    public function lembrete()
    {
        return $this->belongsTo(LembreteAusencia::class, 'lembrete_id');
    }

    public function marcarComoEnviada()
    {
        $this->enviado = true;
        $this->data_envio = now();
        $this->save();
    }

    public function scopePendentes($query)
    {
        return $query->where('enviado', false);
    }

    public function scopeEnviadas($query)
    {
        return $query->where('enviado', true);
    }
}
