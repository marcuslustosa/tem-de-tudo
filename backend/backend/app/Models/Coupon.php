<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Coupon extends Model
{
    use HasFactory;

    protected $table = 'coupons';

    protected $fillable = [
        'user_id',
        'empresa_id',
        'checkin_id',
        'codigo',
        'tipo',
        'descricao',
        'custo_pontos',
        'valor_desconto',
        'porcentagem_desconto',
        'status',
        'expira_em',
        'usado_em',
        'dados_extra'
    ];

    protected $casts = [
        'valor_desconto' => 'decimal:2',
        'expira_em' => 'datetime',
        'usado_em' => 'datetime',
        'dados_extra' => 'array'
    ];

    /**
     * Relacionamento com User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relacionamento com Empresa (opcional)
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * Relacionamento com CheckIn (opcional)
     */
    public function checkin(): BelongsTo
    {
        return $this->belongsTo(CheckIn::class);
    }

    /**
     * Scopes
     */
    public function scopeAtivos($query)
    {
        return $query->where('status', 'active')
                    ->where(function($q) {
                        $q->whereNull('expira_em')
                          ->orWhere('expira_em', '>', now());
                    });
    }

    public function scopeUsados($query)
    {
        return $query->where('status', 'used');
    }

    public function scopeExpirados($query)
    {
        return $query->where('status', 'expired')
                    ->orWhere('expira_em', '<=', now());
    }

    public function scopeDoUsuario($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Acessors
     */
    public function getStatusLabelAttribute(): string
    {
        if ($this->status === 'active' && $this->expira_em && Carbon::now()->gt($this->expira_em)) {
            return 'Expirado';
        }

        return match($this->status) {
            'active' => 'Ativo',
            'used' => 'Usado',
            'expired' => 'Expirado',
            default => 'Desconhecido'
        };
    }

    public function getStatusColorAttribute(): string
    {
        if ($this->status === 'active' && $this->expira_em && Carbon::now()->gt($this->expira_em)) {
            return '#ef4444';
        }

        return match($this->status) {
            'active' => '#10b981',
            'used' => '#6b7280',
            'expired' => '#ef4444',
            default => '#6b7280'
        };
    }

    public function getTipoLabelAttribute(): string
    {
        return match($this->tipo) {
            'discount-10' => '10% OFF',
            'discount-20' => '20% OFF',
            'burger-free' => 'Burger Grátis',
            'haircut-50' => 'Corte 50% OFF',
            'dinner-2' => 'Jantar para 2',
            'tshirt-exclusive' => 'Camiseta Exclusiva',
            default => $this->descricao
        };
    }

    public function getDiasRestantesAttribute(): ?int
    {
        if (!$this->expira_em || $this->status !== 'active') {
            return null;
        }

        $dias = Carbon::now()->diffInDays($this->expira_em, false);
        return max(0, $dias);
    }

    /**
     * Verificar se o cupom está válido
     */
    public function isValido(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->expira_em && Carbon::now()->gt($this->expira_em)) {
            return false;
        }

        return true;
    }

    /**
     * Marcar cupom como usado
     */
    public function marcarComoUsado(): bool
    {
        if (!$this->isValido()) {
            return false;
        }

        return $this->update([
            'status' => 'used',
            'usado_em' => now()
        ]);
    }

    /**
     * Gerar código único para o cupom
     */
    public static function gerarCodigo(): string
    {
        do {
            $codigo = 'TDT' . strtoupper(\Illuminate\Support\Str::random(6));
        } while (self::where('codigo', $codigo)->exists());

        return $codigo;
    }

    /**
     * Boot model para gerar código automaticamente
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($coupon) {
            if (empty($coupon->codigo)) {
                $coupon->codigo = self::gerarCodigo();
            }
        });
    }
}