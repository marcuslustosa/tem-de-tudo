<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
// use Tymon\JWTAuth\Contracts\JWTSubject;

class Admin extends Authenticatable // implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $table = 'admins';

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
        'permissoes' => 'array',
        'senha_temporaria' => 'boolean',
        'ultimo_login' => 'datetime'
    ];

    /**
     * JWT Methods - Commented out until JWT is properly configured
     */
    /*
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'tipo' => 'admin',
            'nivel' => $this->nivel,
            'permissoes' => $this->permissoes
        ];
    }
    */

    /**
     * Relacionamentos
     */
    public function criadoPor()
    {
        return $this->belongsTo(Admin::class, 'criado_por');
    }

    public function adminsCriados()
    {
        return $this->hasMany(Admin::class, 'criado_por');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class, 'user_id');
    }

    /**
     * Scopes
     */
    public function scopeAtivos($query)
    {
        return $query->where('status', 'ativo');
    }

    public function scopeMasters($query)
    {
        return $query->where('nivel', 'Master');
    }

    /**
     * Verificar se tem permissão específica
     */
    public function hasPermission($permission)
    {
        return in_array($permission, $this->permissoes ?? []);
    }

    /**
     * Verificar se pode criar administradores
     */
    public function canCreateAdmins()
    {
        return $this->hasPermission('criar_administradores');
    }

    /**
     * Verificar se é master admin
     */
    public function isMaster()
    {
        return $this->nivel === 'Master';
    }

    /**
     * Obter nome para display
     */
    public function getDisplayNameAttribute()
    {
        return $this->nome ?? $this->name ?? 'Administrador';
    }
}