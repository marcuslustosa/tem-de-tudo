<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $table = 'audit_logs';

    protected $fillable = [
        'user_id',
        'admin_id',
        'action',
        'ip_address',
        'user_agent',
        'details'
    ];

    protected $casts = [
        'details' => 'array',
        'created_at' => 'datetime'
    ];

    /**
     * Relacionamento com usuário
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relacionamento com admin
     */
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Scope por ação
     */
    public function scopePorAcao($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope por período
     */
    public function scopePorPeriodo($query, $inicio, $fim)
    {
        return $query->whereBetween('created_at', [$inicio, $fim]);
    }

    /**
     * Registrar log de auditoria
     */
    public static function registrar(string $action, ?int $userId = null, ?int $adminId = null, ?array $details = null): self
    {
        return self::create([
            'user_id' => $userId,
            'admin_id' => $adminId,
            'action' => $action,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'details' => $details
        ]);
    }
}
