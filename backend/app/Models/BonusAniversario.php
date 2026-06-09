<?php

namespace App\Models;

use App\Models\Concerns\PgSafeBooleans;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BonusAniversario extends Model
{
    use HasFactory, PgSafeBooleans;

    protected $table = 'bonus_aniversario';

    protected $fillable = [
        'user_id',
        'empresa_id',
        'pontos',
        'data_resgate',
        'ano',
        'titulo',
        'descricao',
        'presente',
        'imagem',
        'dias_validade',
        'notification_title',
        'notification_body',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'data_resgate' => 'datetime',
        'ano' => 'integer',
        'pontos' => 'integer',
        'dias_validade' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function resgates()
    {
        return $this->hasMany(BonusAniversarioResgate::class, 'bonus_aniversario_id');
    }

    public function isOperationallyAvailable(): bool
    {
        return (bool) $this->ativo;
    }

    public function daysValidity(): ?int
    {
        $value = $this->dias_validade;

        return $value && $value > 0 ? (int) $value : null;
    }

    public function notificationTitle(): string
    {
        return Str::limit(trim((string) ($this->notification_title ?: $this->titulo)), 80, '');
    }

    public function notificationBody(): string
    {
        $value = trim((string) ($this->notification_body ?: $this->descricao ?: 'Parabens! Ha um beneficio especial esperando por voce.'));

        return Str::limit($value, 120, '');
    }

    public function imageUrl(): ?string
    {
        $path = trim((string) ($this->imagem ?? ''));
        if ($path === '') {
            return null;
        }

        if (
            str_starts_with($path, 'http://')
            || str_starts_with($path, 'https://')
            || str_starts_with($path, '/')
        ) {
            return $path;
        }

        return Storage::url($path);
    }
}
