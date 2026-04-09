<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookSaida extends Model
{
    protected $table = 'webhooks_saida';

    protected $fillable = [
        'empresa_id', 'url', 'segredo', 'eventos', 'ativo',
    ];

    protected function casts(): array
    {
        return [
            'eventos' => 'array',
            'ativo'   => 'boolean',
        ];
    }

    protected $hidden = ['segredo'];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function logs()
    {
        return $this->hasMany(WebhookLog::class, 'webhook_id');
    }
}
