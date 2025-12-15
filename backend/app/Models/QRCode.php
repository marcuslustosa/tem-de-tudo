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
    public static function gerarCodigoUnico($empresaId)
    {
        // Formato: EMP-ID-TIMESTAMP-RANDOM
        return 'QR-' . $empresaId . '-' . time() . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
    }
}
