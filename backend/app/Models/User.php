<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
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
        'role',
        'pontos',
        'pontos_pendentes',
        'telefone',
        'nivel',
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
        ];
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
        } elseif ($pontos >= 2500) {
            return ['nome' => 'Prata', 'cor' => '#c0c0c0', 'min' => 2500, 'proximo' => 5000, 'multiplicador' => 1.5];
        } elseif ($pontos >= 1000) {
            return ['nome' => 'Bronze', 'cor' => '#cd7f32', 'min' => 1000, 'proximo' => 2500, 'multiplicador' => 1.2];
        }
        
        return ['nome' => 'Iniciante', 'cor' => '#8b8b8b', 'min' => 0, 'proximo' => 1000, 'multiplicador' => 1.0];
    }

    /**
     * Verificar se é admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Inicializar valores padrão
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (is_null($user->pontos)) {
                $user->pontos = 0;
            }
            if (is_null($user->pontos_pendentes)) {
                $user->pontos_pendentes = 0;
            }
            if (is_null($user->role)) {
                $user->role = 'user';
            }
        });
    }
}
