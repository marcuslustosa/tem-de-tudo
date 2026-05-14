<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BonusAniversarioResgate extends Model
{
    use HasFactory;

    public const STATUS_AVAILABLE = 'available';
    public const STATUS_REDEEMED = 'redeemed';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_MISSING_BIRTH_DATE = 'missing_birth_date';
    public const STATUS_NOT_LINKED = 'not_linked';
    public const STATUS_OUT_OF_WINDOW = 'out_of_window';

    protected $table = 'bonus_aniversario_resgates';

    protected $fillable = [
        'bonus_aniversario_id',
        'empresa_id',
        'user_id',
        'ano',
        'status',
        'redeemed_at',
        'validated_by',
    ];

    protected $casts = [
        'ano' => 'integer',
        'redeemed_at' => 'datetime',
    ];

    public function bonus()
    {
        return $this->belongsTo(BonusAniversario::class, 'bonus_aniversario_id');
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
