php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Admin extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'nome',
        'email',
        'telefone',
        'password',
        'nivel',
        'empresa',
        'cnpj',
        'permissoes',
        'criado_por',
        'status',
        'senha_temporaria',
        'ultimo_login',
        'ip_ultimo_login'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'permissoes' => 'array',
        'ultimo_login' => 'datetime'
    ];

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

    /**
     * Verificar se admin tem permissão
     */
    public function hasPermission(string $permission): bool
    {
        if (!$this->permissoes) {
            return false;
        }

        return in_array($permission, $this->permissoes);
    }

    /**
     * Verificar se admin está ativo
     */
    public function isActive(): bool
    {
        return $this->status === 'ativo';
    }

    /**
     * Atualizar último login
     */
    public function updateLastLogin(): void
    {
        $this->update([
            'ultimo_login' => now(),
            'ip_ultimo_login' => request()->ip()
        ]);
    }

    /**
     * Relacionamento com admin criador
     */
    public function criadoPor()
    {
        return $this->belongsTo(Admin::class, 'criado_por');
    }

    /**
     * Relacionamento com admins criados por este admin
     */
    public function adminsCriados()
    {
        return $this->hasMany(Admin::class, 'criado_por');
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
        return $this->hasMany(PushNotification::class, 'admin_id');
    }
}
