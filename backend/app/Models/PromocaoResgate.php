<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromocaoResgate extends Model
{
    use HasFactory;

    public const STATUS_REDEEMED = 'redeemed';

    protected $table = 'promocao_resgates';

    protected $fillable = [
        'promocao_id',
        'empresa_id',
        'user_id',
        'status',
        'redeemed_at',
        'validated_by',
    ];

    protected $casts = [
        'redeemed_at' => 'datetime',
    ];

    public function promocao()
    {
        return $this->belongsTo(Promocao::class, 'promocao_id');
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
}
