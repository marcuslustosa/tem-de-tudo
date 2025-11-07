<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PushNotification extends Model
{
    use HasFactory;

    protected $table = 'push_notifications';

    protected $fillable = [
        'user_id',
        'admin_id',
        'user_type',
        'title',
        'body',
        'data',
        'type',
        'fcm_token',
        'is_sent',
        'sent_at',
        'read_at',
        'error_message'
    ];

    protected $casts = [
        'data' => 'array',
        'is_sent' => 'boolean',
        'sent_at' => 'datetime',
        'read_at' => 'datetime'
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
     * Scope para notificações enviadas
     */
    public function scopeEnviadas($query)
    {
        return $query->where('is_sent', true);
    }

    /**
     * Scope para notificações pendentes
     */
    public function scopePendentes($query)
    {
        return $query->where('is_sent', false);
    }

    /**
     * Scope por tipo de usuário
     */
    public function scopePorTipoUsuario($query, $userType)
    {
        return $query->where('user_type', $userType);
    }

    /**
     * Scope por tipo de notificação
     */
    public function scopePorTipo($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Marcar como enviada
     */
    public function marcarComoEnviada(): void
    {
        $this->update([
            'is_sent' => true,
            'sent_at' => now()
        ]);
    }

    /**
     * Marcar como lida
     */
    public function marcarComoLida(): void
    {
        $this->update(['read_at' => now()]);
    }

    /**
     * Registrar erro de envio
     */
    public function registrarErro(string $errorMessage): void
    {
        $this->update(['error_message' => $errorMessage]);
    }
}
