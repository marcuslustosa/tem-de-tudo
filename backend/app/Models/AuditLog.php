<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

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

    private static function tableReady(): bool
    {
        try {
            return Schema::hasTable('audit_logs');
        } catch (\Throwable $e) {
            return false;
        }
    }

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

    /**
     * Compatibilidade com chamadas legadas no projeto.
     */
    public static function logEvent(string $action, ?int $userId = null, ?Request $request = null, $details = null): ?self
    {
        if (!self::tableReady()) {
            Log::warning('audit_logs table not available, skipping audit event', [
                'action' => $action,
                'user_id' => $userId,
            ]);
            return null;
        }

        try {
            return self::create([
                'user_id' => $userId,
                'admin_id' => null,
                'action' => $action,
                'ip_address' => $request?->ip() ?? request()->ip(),
                'user_agent' => $request?->userAgent() ?? request()->userAgent(),
                'details' => is_array($details) ? $details : ['message' => (string) $details],
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to persist audit log', [
                'action' => $action,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
