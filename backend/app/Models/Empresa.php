<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    use HasFactory;

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
        'owner_id',
        'ramo',
        'whatsapp',
        'instagram',
        'facebook',
        'avaliacao_media',
        'total_avaliacoes'
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'points_multiplier' => 'decimal:2',
        'avaliacao_media' => 'decimal:2',
        'total_avaliacoes' => 'integer'
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
        return $this->hasMany(Produto::class)->where('ativo', true);
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
     * Obter multiplicador de pontos baseado no valor da compra
     */
    public function getPointsMultiplier(float $valorCompra = 0): float
    {
        // Usar multiplicador configurado ou padrão de 1.0
        return $this->points_multiplier ?? 1.0;
    }

    /**
     * Verificar se empresa está ativa
     */
    public function isAtiva(): bool
    {
        return $this->ativo ?? true;
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
