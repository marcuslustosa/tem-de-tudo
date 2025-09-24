<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AuditLog extends Model
{
    use HasFactory;

    protected $table = 'audit_logs';

    protected $fillable = [
        'user_id',
        'action',
        'ip_address',
        'user_agent',
        'details',
        'created_at'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Relacionamentos
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'user_id');
    }

    /**
     * Scopes
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeSecurityEvents($query)
    {
        return $query->whereIn('action', [
            'login_failed',
            'login_rate_limited', 
            'unauthorized_access',
            'password_changed',
            'admin_created',
            'permission_denied'
        ]);
    }

    /**
     * Criar log de evento
     */
    public static function logEvent($action, $userId = null, $request = null, $details = null)
    {
        return self::create([
            'user_id' => $userId,
            'action' => $action,
            'ip_address' => $request ? $request->ip() : request()->ip(),
            'user_agent' => $request ? $request->userAgent() : request()->userAgent(),
            'details' => $details,
            'created_at' => Carbon::now()
        ]);
    }

    /**
     * Obter eventos de segurança recentes
     */
    public static function getSecurityEvents($days = 7)
    {
        return self::securityEvents()
            ->recent($days)
            ->orderBy('created_at', 'desc')
            ->with(['user', 'admin'])
            ->get();
    }

    /**
     * Obter estatísticas de login
     */
    public static function getLoginStats($days = 30)
    {
        $period = Carbon::now()->subDays($days);
        
        return [
            'total_logins' => self::where('action', 'login_success')
                ->where('created_at', '>=', $period)->count(),
            'failed_attempts' => self::where('action', 'login_failed')
                ->where('created_at', '>=', $period)->count(),
            'rate_limited' => self::where('action', 'login_rate_limited')
                ->where('created_at', '>=', $period)->count(),
            'unique_users' => self::where('action', 'login_success')
                ->where('created_at', '>=', $period)
                ->distinct('user_id')->count('user_id')
        ];
    }

    /**
     * Cleanup de logs antigos
     */
    public static function cleanup($keepDays = 90)
    {
        $cutoff = Carbon::now()->subDays($keepDays);
        return self::where('created_at', '<', $cutoff)->delete();
    }
}