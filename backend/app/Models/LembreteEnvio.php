<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LembreteEnvio extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_NO_SUBSCRIPTION = 'no_subscription';

    protected $table = 'lembrete_envios';

    protected $fillable = [
        'lembrete_id',
        'empresa_id',
        'user_id',
        'sent_at',
        'reference_last_visit_at',
        'status',
        'erro',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'reference_last_visit_at' => 'datetime',
    ];

    public function lembrete()
    {
        return $this->belongsTo(LembreteAusencia::class, 'lembrete_id');
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
