<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    public $timestamps = false;

    protected $table = 'webhook_logs';

    protected $fillable = [
        'webhook_id', 'evento', 'payload',
        'status_http', 'resposta', 'sucesso',
        'tentativas', 'enviado_em',
    ];

    protected function casts(): array
    {
        return [
            'payload'    => 'array',
            'sucesso'    => 'boolean',
            'enviado_em' => 'datetime',
        ];
    }

    public function webhook()
    {
        return $this->belongsTo(WebhookSaida::class, 'webhook_id');
    }
}
