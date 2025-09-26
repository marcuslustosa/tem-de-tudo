<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class QRCode extends Model
{
    use HasFactory;

    protected $table = 'qr_codes';

    protected $fillable = [
        'empresa_id',
        'code',
        'name',
        'location',
        'active_offers',
        'usage_count',
        'active',
        'last_used_at'
    ];

    protected $casts = [
        'active_offers' => 'array',
        'active' => 'boolean',
        'last_used_at' => 'datetime'
    ];

    /**
     * Relacionamento com empresa
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * Gerar código único para QR Code
     */
    public static function generateUniqueCode(): string
    {
        do {
            $code = Str::random(32);
        } while (self::where('code', $code)->exists());

        return $code;
    }

    /**
     * Registrar uso do QR Code
     */
    public function recordUsage(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Verificar se QR Code está ativo
     */
    public function isActive(): bool
    {
        return $this->active && $this->empresa->active;
    }

    /**
     * Obter ofertas ativas no momento
     */
    public function getActiveOffers(): array
    {
        if (!$this->active_offers) {
            return [];
        }

        // Filtrar ofertas por horário/data se necessário
        $currentHour = now()->hour;
        $currentDay = now()->dayOfWeek;

        return array_filter($this->active_offers, function ($offer) use ($currentHour, $currentDay) {
            // Verificar se a oferta tem restrições de horário
            if (isset($offer['schedule'])) {
                if (isset($offer['schedule']['hours'])) {
                    $hours = $offer['schedule']['hours'];
                    if ($currentHour < $hours['start'] || $currentHour > $hours['end']) {
                        return false;
                    }
                }

                if (isset($offer['schedule']['days'])) {
                    if (!in_array($currentDay, $offer['schedule']['days'])) {
                        return false;
                    }
                }
            }

            // Verificar se a oferta não expirou
            if (isset($offer['expires_at'])) {
                if (now()->gt($offer['expires_at'])) {
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * Adicionar oferta ao QR Code
     */
    public function addOffer(array $offer): bool
    {
        $offers = $this->active_offers ?? [];
        $offers[] = array_merge($offer, [
            'id' => Str::uuid(),
            'created_at' => now()->toISOString()
        ]);

        return $this->update(['active_offers' => $offers]);
    }

    /**
     * Remover oferta do QR Code
     */
    public function removeOffer(string $offerId): bool
    {
        $offers = $this->active_offers ?? [];
        $offers = array_filter($offers, fn($offer) => $offer['id'] !== $offerId);

        return $this->update(['active_offers' => array_values($offers)]);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeByEmpresa($query, $empresaId)
    {
        return $query->where('empresa_id', $empresaId);
    }

    /**
     * URL completa do QR Code
     */
    public function getUrlAttribute(): string
    {
        return config('app.url') . "/qr/{$this->code}";
    }

    /**
     * Dados para gerar o QR Code visual
     */
    public function getQrDataAttribute(): array
    {
        return [
            'url' => $this->url,
            'empresa_id' => $this->empresa_id,
            'code' => $this->code,
            'name' => $this->name,
            'location' => $this->location,
            'offers' => $this->getActiveOffers()
        ];
    }

    /**
     * Boot model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($qrCode) {
            if (!$qrCode->code) {
                $qrCode->code = self::generateUniqueCode();
            }
        });
    }
}