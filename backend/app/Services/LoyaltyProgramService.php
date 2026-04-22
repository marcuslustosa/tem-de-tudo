<?php

namespace App\Services;

use App\Models\Empresa;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

class LoyaltyProgramService
{
    private const SETTINGS_FILE = 'admin-settings.json';

    /**
     * Tipos que representam consumo/resgate de pontos.
     */
    private const REDEEM_TYPES = ['resgate', 'redeem'];

    /**
     * Tipos de acúmulo para cenários legados e novos.
     */
    private const EARN_TYPES = [
        'ganho',
        'earn',
        'checkin',
        'checkin_manual',
        'bonus',
        'bonus_adesao',
        'bonus_indicacao',
        'bonus_indicado',
        'bonus_desafio',
        'adicao',
        'adjustment',
        'ajuste',
        'ajuste_manual',
    ];

    public function __construct(
        private readonly ?Filesystem $disk = null
    ) {
    }

    public function settings(): array
    {
        $defaults = $this->defaultSettings();
        $disk = $this->disk();

        if (!$disk->exists(self::SETTINGS_FILE)) {
            return $defaults;
        }

        try {
            $decoded = json_decode((string) $disk->get(self::SETTINGS_FILE), true);
            if (!is_array($decoded)) {
                return $defaults;
            }

            return array_merge($defaults, $decoded);
        } catch (\Throwable) {
            return $defaults;
        }
    }

    public function isClienteRegistrationAllowed(): bool
    {
        return (bool) ($this->settings()['allow_register_cliente'] ?? true);
    }

    public function isEmpresaRegistrationAllowed(): bool
    {
        return (bool) ($this->settings()['allow_register_empresa'] ?? true);
    }

    public function isMaintenanceMode(): bool
    {
        return (bool) ($this->settings()['maintenance_mode'] ?? false);
    }

    public function pointsBasePerReal(): float
    {
        return max(0.01, (float) ($this->settings()['points_base_per_real'] ?? 1.0));
    }

    public function scanBasePoints(): int
    {
        return max(1, (int) ($this->settings()['scan_base_points'] ?? 100));
    }

    public function calculatePurchasePoints(float $valorCompra, ?Empresa $empresa = null): int
    {
        $valor = max(0, $valorCompra);
        $base = floor($valor * $this->pointsBasePerReal());
        $multiplier = $empresa ? max(0.1, (float) $empresa->getPointsMultiplier($valorCompra)) : 1.0;
        $final = (int) floor($base * $multiplier);

        return max(1, $final);
    }

    public function calculateScanPoints(?Empresa $empresa = null): int
    {
        $base = $this->scanBasePoints();
        $multiplier = $empresa ? max(0.1, (float) $empresa->getPointsMultiplier()) : 1.0;
        $final = (int) round($base * $multiplier);

        return max(1, $final);
    }

    /**
     * Regra de custo para resgate:
     * - Usa pontos_necessarios/custo_pontos quando disponível;
     * - fallback em desconto * 10 para compatibilidade.
     */
    public function promotionCost(object|array $promotion): int
    {
        $value = is_array($promotion) ? $promotion : (array) $promotion;
        $directCost = $value['pontos_necessarios'] ?? $value['custo_pontos'] ?? null;
        if ($directCost !== null && is_numeric($directCost)) {
            return max(1, (int) round((float) $directCost));
        }

        $discount = $value['desconto'] ?? $value['desconto_percentual'] ?? null;
        if ($discount !== null && is_numeric($discount)) {
            return max(1, (int) round(((float) $discount) * 10));
        }

        return 100;
    }

    public function redeemTypes(): array
    {
        return self::REDEEM_TYPES;
    }

    public function earnTypes(): array
    {
        return self::EARN_TYPES;
    }

    public function isRedeemType(?string $type): bool
    {
        return in_array(strtolower((string) $type), self::REDEEM_TYPES, true);
    }

    public function isEarnType(?string $type): bool
    {
        return in_array(strtolower((string) $type), self::EARN_TYPES, true);
    }

    public function summary(?Empresa $empresa = null): array
    {
        $pointsBase = $this->pointsBasePerReal();
        $scanBase = $this->scanBasePoints();
        $multiplier = $empresa ? max(0.1, (float) $empresa->getPointsMultiplier()) : 1.0;

        return [
            'modelo' => 'fidelidade-pontos',
            'acumulo' => [
                'pontos_por_real' => $pointsBase,
                'pontos_base_scan' => $scanBase,
                'multiplicador_empresa' => $multiplier,
                'formula_compra' => 'floor(valor_compra * pontos_por_real * multiplicador_empresa)',
                'formula_scan' => 'round(pontos_base_scan * multiplicador_empresa)',
            ],
            'resgate' => [
                'regra_custo' => 'pontos_necessarios > custo_pontos > desconto*10 > 100',
                'tipos_debito' => self::REDEEM_TYPES,
            ],
            'onboarding_empresa' => [
                'fluxo' => 'cadastro de empresa via admin',
                'beneficios' => [
                    'definir campanhas',
                    'definir multiplicador de pontos',
                    'acesso a dashboard e relatório',
                    'resgates e notificações em tempo real',
                ],
            ],
        ];
    }

    private function defaultSettings(): array
    {
        return [
            'platform_name' => 'Tem de Tudo',
            'support_email' => 'contato@temdetudo.com',
            'support_whatsapp' => '(11) 99999-0000',
            'points_base_per_real' => 1.0,
            'points_expiration_days' => 365,
            'scan_base_points' => 100,
            'allow_register_cliente' => true,
            'allow_register_empresa' => true,
            'push_enabled' => true,
            'maintenance_mode' => false,
        ];
    }

    private function disk(): Filesystem
    {
        return $this->disk ?? Storage::disk('local');
    }
}

