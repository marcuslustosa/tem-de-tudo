<?php

namespace App\Models;

use App\Models\Concerns\PgSafeBooleans;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LembreteAusencia extends Model
{
    use HasFactory, PgSafeBooleans;

    public const STATUS_AVAILABLE = 'available';
    public const STATUS_INACTIVE = 'inactive';
    public const MAX_MENSAGEM = 300;

    protected $table = 'lembretes_ausencia';

    protected $fillable = [
        'empresa_id',
        'dias_ausencia',
        'dias_sem_visita',
        'titulo',
        'mensagem',
        'imagem_url',
        'notification_title',
        'notification_body',
        'ativo',
    ];

    protected $casts = [
        'dias_ausencia' => 'integer',
        'dias_sem_visita' => 'integer',
        'ativo' => 'boolean',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function envios()
    {
        return $this->hasMany(LembreteEnvio::class, 'lembrete_id');
    }

    public function daysWithoutVisit(): int
    {
        return max(1, (int) ($this->dias_sem_visita ?? $this->dias_ausencia ?? 0));
    }

    public function isOperationallyAvailable(): bool
    {
        return (bool) $this->ativo && $this->daysWithoutVisit() > 0;
    }
}
