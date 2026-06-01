<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QRCode extends Model
{
    use HasFactory;

    public const COMPANY_CODE_PREFIX = 'COMPANY_V1_';
    public const LEGACY_CLIENT_CODE_PREFIX = 'CLIENT_LEGACY_';

    protected $table = 'qr_codes';

    protected $fillable = [
        'empresa_id',
        'name',
        'code',
        'location',
        'active',
        'active_offers',
        'usage_count',
        'last_used_at',
        'qr_path'
    ];

    protected $casts = [
        'active' => 'boolean',
        'active_offers' => 'array',
        'usage_count' => 'integer',
        'last_used_at' => 'datetime'
    ];

    /**
     * Relacionamento com Empresa
     */
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * Relacionamento com check-ins
     */
    public function checkIns()
    {
        return $this->hasMany(CheckIn::class, 'qr_code_id');
    }

    /**
     * Scope para QR codes ativos
     */
    public function scopeAtivos($query)
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            return $query->whereRaw($this->qualifyColumn('active') . ' = true');
        }

        return $query->where('active', true);
    }

    /**
     * Incrementar contador de uso
     */
    public function incrementarUso()
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Gerar código único para QR Code
     */
    public static function gerarCodigoUnico($typeOrEmpresaId = null, ?int $ownerId = null): string
    {
        $type = 'empresa';

        if (is_string($typeOrEmpresaId) && !is_numeric($typeOrEmpresaId)) {
            $type = strtolower($typeOrEmpresaId);
        }

        $prefix = $type === 'cliente'
            ? self::LEGACY_CLIENT_CODE_PREFIX
            : self::COMPANY_CODE_PREFIX;

        do {
            $code = $prefix . Str::upper(Str::random(40));
        } while (self::query()->where('code', $code)->exists());

        return $code;
    }
}
