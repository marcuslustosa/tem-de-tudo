<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'perfil',
        'telefone',
        'status'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'pontos' => 'integer',
            'pontos_pendentes' => 'integer',
            'ultimo_login' => 'datetime',
            'email_notifications' => 'boolean',
            'points_notifications' => 'boolean',
            'security_notifications' => 'boolean',
            'promotional_notifications' => 'boolean',
        ];
    }

    /**
     * Relacionamento com empresas (se for empresa)
     */
    public function empresa()
    {
        return $this->hasOne(Empresa::class, 'owner_id');
    }

    /**
     * Relacionamento com audit logs
     */
    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Relacionamento com push notifications
     */
    public function pushNotifications()
    {
        return $this->hasMany(PushNotification::class);
    }

    /**
     * Relacionamento com check-ins
     */
    public function checkIns()
    {
        return $this->hasMany(CheckIn::class);
    }

    /**
     * Relacionamento com pontos
     */
    public function pontos_historico()
    {
        return $this->hasMany(Ponto::class);
    }

    /**
     * Relacionamento com cupons
     */
    public function cupons()
    {
        return $this->hasMany(Coupon::class);
    }

    /**
     * Calcular nível do usuário baseado em pontos
     */
    public function calcularNivel(): array
    {
        $pontos = $this->pontos ?? 0;

        if ($pontos >= 10000) {
            return ['nome' => 'Diamante', 'cor' => '#b9f2ff', 'min' => 10000, 'proximo' => null, 'multiplicador' => 3.0];
        } elseif ($pontos >= 5000) {
            return ['nome' => 'Ouro', 'cor' => '#ffd700', 'min' => 5000, 'proximo' => 10000, 'multiplicador' => 2.0];
        } elseif ($pontos >= 1000) {
            return ['nome' => 'Prata', 'cor' => '#c0c0c0', 'min' => 1000, 'proximo' => 5000, 'multiplicador' => 1.5];
        }

        return ['nome' => 'Bronze', 'cor' => '#cd7f32', 'min' => 0, 'proximo' => 1000, 'multiplicador' => 1.0];
    }

    /**
     * Verificar se é admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     */
    public function getJWTCustomClaims()
    {
        return [];
    }


}
