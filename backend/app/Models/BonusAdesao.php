<?php

namespace App\Models;

use App\Models\Concerns\PgSafeBooleans;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class BonusAdesao extends Model
{
    use HasFactory, PgSafeBooleans;

    public const TYPE_ADHESION_BONUS = 'adhesion_bonus';

    protected $table = 'bonus_adesao';

    protected $fillable = [
        'empresa_id',
        'titulo',
        'descricao',
        'tipo_desconto',
        'valor_desconto',
        'imagem',
        'ativo',
        'data_expiracao',
        'limite_por_cliente',
        'tipo',
        'ordem',
        'termos',
    ];

    protected $casts = [
        'valor_desconto' => 'decimal:2',
        'ativo' => 'boolean',
        'data_expiracao' => 'datetime',
        'limite_por_cliente' => 'integer',
        'ordem' => 'integer',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function resgates()
    {
        return $this->hasMany(BonusAdesaoResgate::class, 'bonus_id');
    }

    public function getDescontoFormatadoAttribute()
    {
        if ($this->tipo_desconto === 'porcentagem') {
            return $this->valor_desconto . '%';
        }

        return 'R$ ' . number_format((float) $this->valor_desconto, 2, ',', '.');
    }

    public function isExpired(): bool
    {
        return $this->data_expiracao instanceof Carbon
            ? $this->data_expiracao->isPast()
            : false;
    }

    public function isOperationallyAvailable(): bool
    {
        return (bool) $this->ativo && !$this->isExpired();
    }
}
