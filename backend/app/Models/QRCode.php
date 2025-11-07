<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QRCode extends Model
{
    use HasFactory;

    protected $table = 'qr_codes';

    protected $fillable = [
        'empresa_id',
        'name',
        'code',
        'location',
        'active',
        'active_offers',
        'usage_count',
        'last_used_at'
    ];

    protected $casts = [
        'active' => 'boolean',
        'active_offers' => 'array',
        'usage_count' => 'integer',
        'last_used_at' => 'datetime'
    ];

    /**
     * Relacionamento com empresa
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
        return $this->hasMany(CheckIn::class);
    }

    /**
     * Scope para QR codes ativos
     */
    public function scopeAtivos($query)
    {
        return $query->where('active', true);
    }

    /**
     * Verificar se QR code está ativo
     */
    public function estaAtivo(): bool
    {
        return $this->active;
    }

    /**
     * Incrementar contador de uso
     */
    public function incrementarUso(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Gerar código único para QR
     */
    public static function gerarCodigoUnico(): string
    {
        do {
            $codigo = 'QR' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
        } while (self::where('code', $codigo)->exists());

        return $codigo;
    }
}
