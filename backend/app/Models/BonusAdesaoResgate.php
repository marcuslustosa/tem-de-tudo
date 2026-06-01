<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class BonusAdesaoResgate extends Model
{
    use HasFactory;

    public const STATUS_AVAILABLE = 'available';
    public const STATUS_REDEEMED = 'redeemed';
    public const STATUS_EXPIRED = 'expired';
    public const TABLE_CANONICAL = 'bonus_adesao_resgates';
    public const TABLE_LEGACY = 'bonus_adesaos';

    protected static ?string $resolvedTable = null;

    protected $fillable = [
        'bonus_id',
        'empresa_id',
        'user_id',
        'status',
        'validated_by',
        'redeemed_at',
        'resgatado',
        'data_resgate',
        'pontos',
    ];

    protected $casts = [
        'redeemed_at' => 'datetime',
        'data_resgate' => 'datetime',
        'resgatado' => 'boolean',
        'pontos' => 'integer',
    ];

    public function setResgatadoAttribute($value): void
    {
        $this->attributes['resgatado'] = $this->databaseBooleanValue((bool) $value);
    }

    public function getTable()
    {
        if (static::$resolvedTable !== null) {
            return static::$resolvedTable;
        }

        if (Schema::hasTable(self::TABLE_CANONICAL)) {
            return static::$resolvedTable = self::TABLE_CANONICAL;
        }

        if (Schema::hasTable(self::TABLE_LEGACY)) {
            return static::$resolvedTable = self::TABLE_LEGACY;
        }

        return self::TABLE_CANONICAL;
    }

    public function bonus()
    {
        return $this->belongsTo(BonusAdesao::class, 'bonus_id');
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    private function databaseBooleanValue(bool $value): bool|string
    {
        return $this->getConnection()->getDriverName() === 'pgsql'
            ? ($value ? 'true' : 'false')
            : $value;
    }
}
