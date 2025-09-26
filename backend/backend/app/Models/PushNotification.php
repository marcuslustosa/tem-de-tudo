<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PushNotification extends Model
{
    use HasFactory;

    protected $table = 'push_notifications';

    protected $fillable = [
        'user_id',
        'user_type',
        'title',
        'body',
        'data',
        'type',
        'sent_at',
        'read_at',
        'fcm_token',
        'is_sent',
        'error_message'
    ];

    protected $casts = [
        'data' => 'array',
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
        'is_sent' => 'boolean'
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
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeForUser($query, $userId, $userType = 'client')
    {
        return $query->where('user_id', $userId)->where('user_type', $userType);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeSent($query)
    {
        return $query->where('is_sent', true);
    }

    public function scopePending($query)
    {
        return $query->where('is_sent', false)->whereNull('error_message');
    }

    /**
     * Marcar como lida
     */
    public function markAsRead()
    {
        $this->update(['read_at' => now()]);
    }

    /**
     * Tipos de notificação
     */
    public static function getTypes()
    {
        return [
            'welcome' => 'Boas-vindas',
            'points_gained' => 'Pontos Ganhos',
            'points_redeemed' => 'Pontos Resgatados',
            'level_up' => 'Mudança de Nível',
            'promotion' => 'Promoção',
            'security_alert' => 'Alerta de Segurança',
            'system_update' => 'Atualização do Sistema',
            'admin_alert' => 'Alerta Administrativo'
        ];
    }

    /**
     * Criar notificação para usuário
     */
    public static function createForUser($userId, $userType, $title, $body, $data = [], $type = 'general', $fcmToken = null)
    {
        return self::create([
            'user_id' => $userId,
            'user_type' => $userType,
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'type' => $type,
            'fcm_token' => $fcmToken,
            'is_sent' => false
        ]);
    }

    /**
     * Criar notificação em massa
     */
    public static function createBulk($users, $title, $body, $data = [], $type = 'general')
    {
        $notifications = [];
        
        foreach ($users as $user) {
            $notifications[] = [
                'user_id' => $user->id,
                'user_type' => $user instanceof Admin ? 'admin' : 'client',
                'title' => $title,
                'body' => $body,
                'data' => json_encode($data),
                'type' => $type,
                'fcm_token' => $user->fcm_token ?? null,
                'is_sent' => false,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        if (!empty($notifications)) {
            self::insert($notifications);
        }

        return count($notifications);
    }

    /**
     * Obter estatísticas
     */
    public static function getStats($days = 30)
    {
        $period = Carbon::now()->subDays($days);
        
        return [
            'total_sent' => self::where('is_sent', true)->where('created_at', '>=', $period)->count(),
            'total_pending' => self::pending()->count(),
            'total_errors' => self::whereNotNull('error_message')->where('created_at', '>=', $period)->count(),
            'by_type' => self::where('created_at', '>=', $period)
                            ->groupBy('type')
                            ->selectRaw('type, count(*) as count')
                            ->pluck('count', 'type'),
            'read_rate' => self::where('is_sent', true)
                             ->where('created_at', '>=', $period)
                             ->whereNotNull('read_at')
                             ->count() / max(1, self::where('is_sent', true)->where('created_at', '>=', $period)->count()) * 100
        ];
    }
}