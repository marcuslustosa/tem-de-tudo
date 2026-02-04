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
        'data_nascimento',
        'status',
        'pontos',
        'pontos_pendentes',
        'nivel',
        'fcm_token',
        'email_notifications',
        'points_notifications',
        'security_notifications',
        'promotional_notifications',
        'ultimo_login',
        'ip_ultimo_login',
        'pontos_lifetime',
        'valor_gasto_total',
        'dias_consecutivos',
        'ultimo_checkin',
        'empresas_visitadas',
        'multiplicador_pontos',
        'posicao_ranking'
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
            'data_nascimento' => 'date',
            'pontos' => 'integer',
            'pontos_pendentes' => 'integer',
            'ultimo_login' => 'datetime',
            'email_notifications' => 'boolean',
            'points_notifications' => 'boolean',
            'security_notifications' => 'boolean',
            'promotional_notifications' => 'boolean',
            'pontos_lifetime' => 'integer',
            'valor_gasto_total' => 'integer',
            'dias_consecutivos' => 'integer',
            'ultimo_checkin' => 'date',
            'empresas_visitadas' => 'integer',
            'multiplicador_pontos' => 'decimal:2',
            'posicao_ranking' => 'integer'
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
     * Relacionamento com pagamentos
     */
    public function pagamentos()
    {
        return $this->hasMany(Pagamento::class);
    }

    /**
     * Relacionamento com check-ins (alias)
     */
    public function checkins()
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
     * Relacionamento com badges conquistados
     */
    public function badges()
    {
        return $this->belongsToMany(Badge::class, 'user_badges')
                    ->withTimestamps()
                    ->withPivot('conquistado_em');
    }

    /**
     * Relacionamento com transações (para cálculo de valor gasto)
     */
    public function transacoes()
    {
        return $this->hasMany(Ponto::class)->where('tipo', 'like', '%compra%');
    }

    /**
     * Calcular nível do usuário baseado em pontos lifetime
     */
    public function calcularNivel(): array
    {
        $pontos = $this->pontos_lifetime ?? $this->pontos ?? 0;

        if ($pontos >= 15000) {
            return [
                'id' => 4,
                'nome' => 'Diamante', 
                'cor' => '#b9f2ff', 
                'icone' => '💎',
                'min' => 15000, 
                'proximo' => null, 
                'multiplicador' => 3.0,
                'beneficios' => ['Triplo de pontos', 'Descontos exclusivos', 'Atendimento VIP']
            ];
        } elseif ($pontos >= 5000) {
            return [
                'id' => 3,
                'nome' => 'Ouro', 
                'cor' => '#ffd700', 
                'icone' => '🥇',
                'min' => 5000, 
                'proximo' => 15000, 
                'multiplicador' => 2.0,
                'beneficios' => ['Dobro de pontos', 'Descontos especiais', 'Ofertas exclusivas']
            ];
        } elseif ($pontos >= 1500) {
            return [
                'id' => 2,
                'nome' => 'Prata', 
                'cor' => '#c0c0c0', 
                'icone' => '🥈',
                'min' => 1500, 
                'proximo' => 5000, 
                'multiplicador' => 1.5,
                'beneficios' => ['50% mais pontos', 'Descontos especiais']
            ];
        }

        return [
            'id' => 1,
            'nome' => 'Bronze', 
            'cor' => '#cd7f32', 
            'icone' => '🥉',
            'min' => 0, 
            'proximo' => 1500, 
            'multiplicador' => 1.0,
            'beneficios' => ['Pontos básicos', 'Programa de fidelidade']
        ];
    }

    /**
     * Atualizar nível baseado em pontos lifetime
     */
    public function atualizarNivel()
    {
        $nivel_info = $this->calcularNivel();
        $this->nivel = $nivel_info['id'];
        $this->multiplicador_pontos = $nivel_info['multiplicador'];
        $this->save();
        
        return $nivel_info;
    }

    /**
     * Verificar e conquistar novos badges
     */
    public function verificarBadges()
    {
        $badges_conquistados = [];
        $badges_disponiveis = Badge::where('ativo', true)->get();
        
        foreach ($badges_disponiveis as $badge) {
            // Verifica se já tem o badge
            if ($this->badges()->where('badge_id', $badge->id)->exists()) {
                continue;
            }
            
            // Verifica se conquistou o badge
            if ($badge->verificarConquista($this)) {
                $this->badges()->attach($badge->id, [
                    'conquistado_em' => now()
                ]);
                $badges_conquistados[] = $badge;
            }
        }
        
        return $badges_conquistados;
    }

    /**
     * Processar check-in e atualizar estatísticas
     */
    public function processarCheckin(CheckIn $checkin)
    {
        // Atualizar pontos lifetime
        $this->pontos_lifetime += $checkin->pontos_ganhos;
        
        // Atualizar dias consecutivos
        $hoje = now()->format('Y-m-d');
        $ontem = now()->subDay()->format('Y-m-d');
        
        if ($this->ultimo_checkin?->format('Y-m-d') == $ontem) {
            $this->dias_consecutivos += 1;
        } elseif ($this->ultimo_checkin?->format('Y-m-d') != $hoje) {
            $this->dias_consecutivos = 1;
        }
        
        $this->ultimo_checkin = $hoje;
        
        // Atualizar contador de empresas visitadas
        $empresas_unicas = $this->checkIns()->distinct('empresa_id')->count();
        $this->empresas_visitadas = $empresas_unicas;
        
        $this->save();
        
        // Atualizar nível e verificar badges
        $this->atualizarNivel();
        return $this->verificarBadges();
    }

    /**
     * Processar compra e atualizar valor gasto
     */
    public function processarCompra($valor_centavos)
    {
        $this->valor_gasto_total += $valor_centavos;
        $this->save();
        
        // Atualizar nível e verificar badges
        $this->atualizarNivel();
        return $this->verificarBadges();
    }

    /**
     * Verificar se é admin
     */
    public function isAdmin(): bool
    {
        return $this->perfil === 'admin';
    }

    /**
     * Relacionamento com inscrições em empresas
     */
    public function inscricoes()
    {
        return $this->hasMany(InscricaoEmpresa::class);
    }

    /**
     * Relacionamento com empresas inscritas (através de inscricoes)
     */
    public function empresasInscritas()
    {
        return $this->belongsToMany(Empresa::class, 'inscricoes_empresa')
            ->withPivot('data_inscricao', 'ultima_visita', 'bonus_adesao_resgatado')
            ->withTimestamps();
    }

    /**
     * Relacionamento com QR Code do cliente
     */
    public function qrCode()
    {
        return $this->hasOne(QRCode::class);
    }

    /**
     * Relacionamento com progressos de cartões fidelidade
     */
    public function cartoesFidelidadeProgresso()
    {
        return $this->hasMany(CartaoFidelidadeProgresso::class);
    }

    /**
     * Relacionamento com avaliações feitas
     */
    public function avaliacoes()
    {
        return $this->hasMany(Avaliacao::class);
    }

    /**
     * Relacionamento com notificações push recebidas
     */
    public function notificacoesPush()
    {
        return $this->hasMany(NotificacaoPush::class);
    }

    /**
     * Verificar se hoje é aniversário
     */
    public function ehAniversarioHoje(): bool
    {
        if (!$this->data_nascimento) {
            return false;
        }
        
        $hoje = now();
        return $this->data_nascimento->month == $hoje->month 
            && $this->data_nascimento->day == $hoje->day;
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
