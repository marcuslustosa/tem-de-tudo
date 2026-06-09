<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Empresa extends Model
{
    use \App\Models\Concerns\PgSafeBooleans;
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'nome',
        'endereco',
        'telefone',
        'cnpj',
        'logo',
        'descricao',
        'categoria',
        'points_multiplier',
        'ativo',
        'status',
        'owner_id',
        'ramo',
        'whatsapp',
        'instagram',
        'facebook',
        'avaliacao_media',
        'total_avaliacoes',
        'latitude',
        'longitude'
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'points_multiplier' => 'float',
        'avaliacao_media' => 'float',
        'total_avaliacoes' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float'
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Relacionamento com check-ins
     */
    public function checkIns()
    {
        return $this->hasMany(CheckIn::class);
    }

    /**
     * Relacionamento com pagamentos
     */
    public function pagamentos()
    {
        return $this->hasMany(Pagamento::class);
    }

    /**
     * Relacionamento com produtos
     */
    public function produtos()
    {
        return $this->hasMany(Produto::class);
    }

    /**
     * Produtos ativos da empresa
     */
    public function produtosAtivos()
    {
        return $this->hasMany(Produto::class)->whereTrue('ativo');
    }

    /**
     * Relacionamento com pontos
     */
    public function pontos()
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
     * Obter multiplicador de pontos, priorizando campanhas temporárias ativas.
     */
    public function getPointsMultiplier(float $valorCompra = 0): float
    {
        $base = (float) ($this->points_multiplier ?? 1.0);

        $campanha = \App\Models\CampanhaMultiplicador::where('empresa_id', $this->id)
            ->whereTrue('ativo')
            ->where('data_inicio', '<=', now())
            ->where('data_fim', '>=', now())
            ->orderByDesc('multiplicador')
            ->first();

        return $campanha ? max($base, $campanha->multiplicador) : $base;
    }

    /**
     * Verificar se empresa está ativa
     */
    public function isAtiva(): bool
    {
        return $this->isPubliclyVisible();
    }

    public function isPubliclyVisible(): bool
    {
        $isEnabled = (bool) ($this->ativo ?? true);

        return $isEnabled && $this->operationalStatus() === self::STATUS_ACTIVE;
    }

    public function operationalStatus(): string
    {
        return self::normalizeOperationalStatus(
            $this->attributes['status'] ?? null,
            $this->attributes['ativo'] ?? null
        );
    }

    public function setStatusAttribute($value): void
    {
        $this->attributes['status'] = self::normalizeOperationalStatus(
            $value,
            $this->attributes['ativo'] ?? null
        );
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        if (Schema::hasColumn($this->getTable(), 'ativo')) {
            try {
                $type = strtolower((string) Schema::getColumnType($this->getTable(), 'ativo'));
            } catch (\Throwable) {
                $type = 'boolean';
            }

            if (in_array($type, ['bool', 'boolean'], true)) {
                if (DB::connection()->getDriverName() === 'pgsql') {
                    $query->whereRaw($this->qualifyColumn('ativo') . ' = true');
                } else {
                    $query->whereTrue('ativo');
                }
            } else {
                $query->whereIn('ativo', [1, '1', true, 'true', 'ativo', 'ativa', 'active']);
            }
        }

        self::applyOperationalStatusFilter($query, self::STATUS_ACTIVE, $this->qualifyColumn('status'));

        return $query;
    }

    public static function applyOperationalStatusFilter(Builder $query, string $status, string $column = 'status'): Builder
    {
        if (!Schema::hasColumn((new static())->getTable(), 'status')) {
            return $query;
        }

        try {
            $type = strtolower((string) Schema::getColumnType((new static())->getTable(), 'status'));
        } catch (\Throwable) {
            return $query;
        }

        if (!in_array($type, ['string', 'text', 'varchar', 'char'], true)) {
            return $query;
        }

        return $query->whereIn($column, self::normalizedStatusVariants($status));
    }

    public static function normalizeOperationalStatus($status, $ativo = null): string
    {
        $normalized = strtolower(trim((string) ($status ?? '')));

        return match ($normalized) {
            self::STATUS_PENDING, 'pendente' => self::STATUS_PENDING,
            self::STATUS_ACTIVE, 'ativo', 'ativa', 'approved' => self::STATUS_ACTIVE,
            self::STATUS_SUSPENDED, 'suspenso', 'suspensa', 'inativo', 'inativa', 'inactive', 'blocked', 'bloqueado' => self::STATUS_SUSPENDED,
            self::STATUS_REJECTED, 'rejected', 'rejeitado', 'rejeitada' => self::STATUS_REJECTED,
            default => (bool) ($ativo ?? true) ? self::STATUS_ACTIVE : self::STATUS_SUSPENDED,
        };
    }

    public static function normalizedStatusAliases(string $status): array
    {
        return match (self::normalizeOperationalStatus($status)) {
            self::STATUS_PENDING => [self::STATUS_PENDING, 'pendente'],
            self::STATUS_ACTIVE => [self::STATUS_ACTIVE, 'ativo', 'ativa', 'approved'],
            self::STATUS_SUSPENDED => [self::STATUS_SUSPENDED, 'suspenso', 'suspensa', 'inativo', 'inativa', 'inactive', 'bloqueado'],
            self::STATUS_REJECTED => [self::STATUS_REJECTED, 'rejeitado', 'rejeitada'],
            default => [self::STATUS_ACTIVE],
        };
    }

    public static function normalizedStatusVariants(string $status): array
    {
        return collect(self::normalizedStatusAliases($status))
            ->flatMap(function (string $value) {
                return array_values(array_unique([
                    $value,
                    strtolower($value),
                    strtoupper($value),
                    ucfirst(strtolower($value)),
                ]));
            })
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Relacionamento com QR codes
     */
    public function qrCodes()
    {
        return $this->hasMany(QRCode::class);
    }

    /**
     * Relacionamento com discount levels
     */
    public function discountLevels()
    {
        return $this->hasMany(DiscountLevel::class);
    }

    /**
     * Relacionamento com campanhas de multiplicador temporário
     */
    public function campanhasMultiplicador()
    {
        return $this->hasMany(CampanhaMultiplicador::class);
    }

    /**
     * Configuração de política de fidelidade por empresa.
     */
    public function loyaltyConfig()
    {
        return $this->hasOne(CompanyLoyaltyConfig::class, 'company_id');
    }

    /**
     * Relacionamento com inscrições de clientes
     */
    public function inscricoes()
    {
        return $this->hasMany(InscricaoEmpresa::class);
    }

    /**
     * Relacionamento com bônus de adesão
     */
    public function bonusAdesao()
    {
        return $this->hasOne(BonusAdesao::class);
    }

    /**
     * Relacionamento com cartões fidelidade
     */
    public function cartoesFidelidade()
    {
        return $this->hasMany(CartaoFidelidade::class);
    }

    /**
     * Relacionamento com promoções
     */
    public function promocoes()
    {
        return $this->hasMany(Promocao::class);
    }

    /**
     * Relacionamento com bônus aniversário
     */
    public function bonusAniversario()
    {
        return $this->hasOne(BonusAniversario::class);
    }

    /**
     * Relacionamento com lembretes de ausência
     */
    public function lembretesAusencia()
    {
        return $this->hasMany(LembreteAusencia::class);
    }

    /**
     * Relacionamento com avaliações
     */
    public function avaliacoes()
    {
        return $this->hasMany(Avaliacao::class);
    }

    /**
     * Atualizar média de avaliação
     */
    public function atualizarAvaliacaoMedia()
    {
        $this->total_avaliacoes = $this->avaliacoes()->count();
        $this->avaliacao_media = $this->total_avaliacoes > 0 
            ? $this->avaliacoes()->avg('estrelas') 
            : 0;
        $this->save();
    }
}
