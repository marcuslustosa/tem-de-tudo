<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QRCode extends Model
{
    use HasFactory;

    protected $table = 'qr_codes';

    protected $fillable = [
        'code',
        'type',
        'empresa_id',
        'user_id',
        'qr_image',
        'ativo'
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    /**
     * Relacionamento com Empresa
     */
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * Relacionamento com User (Cliente)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Gerar código único para QR Code
     */
    public static function gerarCodigoUnico($type, $id)
    {
        // Formato: TIPO-ID-TIMESTAMP-RANDOM
        return strtoupper($type) . '-' . $id . '-' . time() . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
    }
}
