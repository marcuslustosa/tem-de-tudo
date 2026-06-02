<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Promocao extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'ativa';
    public const STATUS_PAUSED = 'pausada';
    public const STATUS_EXPIRED = 'expirada';

    protected $table = 'promocoes';

    protected $fillable = [
        'empresa_id',
        'titulo',
        'descricao',
        'imagem',
        'notification_title',
        'notification_body',
        'desconto',
        'pontos_necessarios',
        'data_inicio',
        'data_fim',
        'validade',
        'status',
        'visualizacoes',
        'resgates',
        'usos',
        'ativo',
        'data_envio',
        'total_envios',
        'percentual_desconto',
        'valor_desconto',
        'tipo_recompensa',
        'desconto_percentual',
        'desconto_valor',
        'quantidade_disponivel',
        'qtd_disponivel',
        'qtd_resgatada',
        'limite_por_usuario',
        'termos_condicoes',
    ];

    protected $casts = [
        'desconto' => 'decimal:2',
        'pontos_necessarios' => 'integer',
        'data_inicio' => 'date',
        'data_fim' => 'date',
        'validade' => 'date',
        'visualizacoes' => 'integer',
        'resgates' => 'integer',
        'usos' => 'integer',
        'ativo' => 'boolean',
        'data_envio' => 'datetime',
        'total_envios' => 'integer',
        'percentual_desconto' => 'decimal:2',
        'valor_desconto' => 'decimal:2',
        'desconto_percentual' => 'decimal:2',
        'desconto_valor' => 'decimal:2',
        'quantidade_disponivel' => 'integer',
        'qtd_disponivel' => 'integer',
        'qtd_resgatada' => 'integer',
        'limite_por_usuario' => 'integer',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function resgatesClientes()
    {
        return $this->hasMany(PromocaoResgate::class, 'promocao_id');
    }

    public function setAtivoAttribute($value): void
    {
        $this->attributes['ativo'] = $this->databaseBooleanValue((bool) $value);
    }

    public function foiEnviada(): bool
    {
        return $this->data_envio !== null;
    }

    public function expirationDate(): ?Carbon
    {
        return $this->validade ?: $this->data_fim;
    }

    public function isExpired(): bool
    {
        $expirationDate = $this->expirationDate();

        return $expirationDate ? $expirationDate->isPast() : false;
    }

    public function hasAvailableStock(): bool
    {
        $available = $this->qtd_disponivel ?? $this->quantidade_disponivel;
        if ($available === null) {
            return true;
        }

        return (int) ($this->qtd_resgatada ?? 0) < (int) $available;
    }

    public function isOperationallyAvailable(): bool
    {
        if (!$this->ativo || $this->isExpired() || !$this->hasAvailableStock()) {
            return false;
        }

        $status = Str::lower(trim((string) ($this->status ?? self::STATUS_ACTIVE)));

        return !in_array($status, [self::STATUS_PAUSED, self::STATUS_EXPIRED, 'inactive', 'inativa'], true);
    }

    public function notificationTitle(): string
    {
        return trim((string) ($this->notification_title ?: $this->titulo));
    }

    public function notificationBody(): string
    {
        $value = trim((string) ($this->notification_body ?: $this->descricao ?: 'Confira a promocao disponivel no aplicativo.'));

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

    private function databaseBooleanValue(bool $value): bool|string
    {
        return $this->getConnection()->getDriverName() === 'pgsql'
            ? ($value ? 'true' : 'false')
            : $value;
    }
}
