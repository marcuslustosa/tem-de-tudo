<?php

namespace App\Services;

use App\Models\CompanyLoyaltyConfig;
use App\Models\Empresa;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class LoyaltyProgramService
{
    private const SETTINGS_FILE = 'admin-settings.json';
    private const DEFAULT_REDEEM_POINTS_PER_CURRENCY = 10;
    private const DEFAULT_MIN_REDEEM_POINTS = 50;
    private const DEFAULT_WELCOME_BONUS_POINTS = 0;

    /**
     * Tipos que representam consumo/resgate de pontos.
     */
    private const REDEEM_TYPES = ['resgate', 'redeem'];

    /**
     * Tipos de acumulo para cenarios legados e novos.
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

    public function pointsBasePerReal(?Empresa $empresa = null): float
    {
        $policy = $this->effectivePolicy($empresa);

        return max(0.01, (float) ($policy['points_per_real'] ?? 1.0));
    }

    public function scanBasePoints(?Empresa $empresa = null): int
    {
        $policy = $this->effectivePolicy($empresa);

        return max(1, (int) ($policy['scan_base_points'] ?? 100));
    }

    public function redeemPointsPerCurrency(?Empresa $empresa = null): int
    {
        $policy = $this->effectivePolicy($empresa);

        return max(1, (int) ($policy['redeem_points_per_currency'] ?? self::DEFAULT_REDEEM_POINTS_PER_CURRENCY));
    }

    public function minRedeemPoints(?Empresa $empresa = null): int
    {
        $policy = $this->effectivePolicy($empresa);

        return max(1, (int) ($policy['min_redeem_points'] ?? self::DEFAULT_MIN_REDEEM_POINTS));
    }

    public function welcomeBonusPoints(?Empresa $empresa = null): int
    {
        $policy = $this->effectivePolicy($empresa);

        return max(0, (int) ($policy['welcome_bonus_points'] ?? self::DEFAULT_WELCOME_BONUS_POINTS));
    }

    public function calculatePurchasePoints(float $valorCompra, ?Empresa $empresa = null): int
    {
        $policy = $this->effectivePolicy($empresa);
        $valor = max(0, $valorCompra);
        $base = floor($valor * max(0.01, (float) ($policy['points_per_real'] ?? 1.0)));
        $multiplier = $empresa ? max(0.1, (float) $empresa->getPointsMultiplier($valorCompra)) : 1.0;
        $final = (int) floor($base * $multiplier);

        return max(1, $final);
    }

    public function calculateScanPoints(?Empresa $empresa = null): int
    {
        $policy = $this->effectivePolicy($empresa);
        $base = max(1, (int) ($policy['scan_base_points'] ?? 100));
        $multiplier = $empresa ? max(0.1, (float) $empresa->getPointsMultiplier()) : 1.0;
        $final = (int) round($base * $multiplier);

        return max(1, $final);
    }

    /**
     * Regra de custo para resgate:
     * - Usa pontos_necessarios/custo_pontos quando disponivel;
     * - fallback em desconto * pontos_por_moeda.
     */
    public function promotionCost(object|array $promotion, ?Empresa $empresa = null): int
    {
        $policy = $this->effectivePolicy($empresa);
        $currencyRate = max(1, (int) ($policy['redeem_points_per_currency'] ?? self::DEFAULT_REDEEM_POINTS_PER_CURRENCY));
        $minimum = max(1, (int) ($policy['min_redeem_points'] ?? self::DEFAULT_MIN_REDEEM_POINTS));

        $value = is_array($promotion) ? $promotion : (array) $promotion;
        $directCost = $value['pontos_necessarios'] ?? $value['custo_pontos'] ?? null;
        if ($directCost !== null && is_numeric($directCost)) {
            return max($minimum, (int) round((float) $directCost));
        }

        $discount = $value['desconto'] ?? $value['desconto_percentual'] ?? null;
        if ($discount !== null && is_numeric($discount)) {
            return max($minimum, (int) round(((float) $discount) * $currencyRate));
        }

        return max($minimum, 100);
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
        $policy = $this->effectivePolicy($empresa);
        $pointsBase = max(0.01, (float) ($policy['points_per_real'] ?? 1.0));
        $scanBase = max(1, (int) ($policy['scan_base_points'] ?? 100));
        $redeemRate = max(1, (int) ($policy['redeem_points_per_currency'] ?? self::DEFAULT_REDEEM_POINTS_PER_CURRENCY));
        $minRedeem = max(1, (int) ($policy['min_redeem_points'] ?? self::DEFAULT_MIN_REDEEM_POINTS));
        $multiplier = $empresa ? max(0.1, (float) $empresa->getPointsMultiplier()) : 1.0;
        $onboarding = $empresa ? $this->onboardingStatus($empresa) : null;

        return [
            'modelo' => 'fidelidade-pontos',
            'config_origem' => $policy['source'] ?? 'platform_default',
            'acumulo' => [
                'pontos_por_real' => $pointsBase,
                'pontos_base_scan' => $scanBase,
                'bonus_boas_vindas' => max(0, (int) ($policy['welcome_bonus_points'] ?? self::DEFAULT_WELCOME_BONUS_POINTS)),
                'multiplicador_empresa' => $multiplier,
                'formula_compra' => 'floor(valor_compra * pontos_por_real * multiplicador_empresa)',
                'formula_scan' => 'round(pontos_base_scan * multiplicador_empresa)',
            ],
            'resgate' => [
                'regra_custo' => 'pontos_necessarios > custo_pontos > desconto*redeem_points_per_currency > 100',
                'pontos_por_moeda' => $redeemRate,
                'minimo_pontos_resgate' => $minRedeem,
                'tipos_debito' => self::REDEEM_TYPES,
            ],
            'onboarding_empresa' => [
                'fluxo' => 'cadastro de empresa via admin',
                'beneficios' => [
                    'definir campanhas',
                    'definir multiplicador de pontos',
                    'acesso a dashboard e relatorio',
                    'resgates e notificacoes em tempo real',
                ],
                'status' => $onboarding,
            ],
        ];
    }

    public function effectivePolicy(?Empresa $empresa = null): array
    {
        $settings = $this->settings();
        $policy = [
            'source' => 'platform_default',
            'is_active' => true,
            'points_per_real' => max(0.01, (float) ($settings['points_base_per_real'] ?? 1.0)),
            'scan_base_points' => max(1, (int) ($settings['scan_base_points'] ?? 100)),
            'redeem_points_per_currency' => self::DEFAULT_REDEEM_POINTS_PER_CURRENCY,
            'min_redeem_points' => self::DEFAULT_MIN_REDEEM_POINTS,
            'welcome_bonus_points' => self::DEFAULT_WELCOME_BONUS_POINTS,
            'metadata' => [],
            'company_id' => $empresa?->id,
        ];

        $config = $this->companyConfig($empresa);
        if (!$config) {
            return $policy;
        }

        if (!$config->is_active) {
            $policy['source'] = 'company_override_inactive';
            $policy['is_active'] = false;

            return $policy;
        }

        $policy['source'] = 'company_override_active';
        $policy['points_per_real'] = $config->points_per_real !== null
            ? max(0.01, (float) $config->points_per_real)
            : $policy['points_per_real'];
        $policy['scan_base_points'] = $config->scan_base_points !== null
            ? max(1, (int) $config->scan_base_points)
            : $policy['scan_base_points'];
        $policy['redeem_points_per_currency'] = $config->redeem_points_per_currency !== null
            ? max(1, (int) $config->redeem_points_per_currency)
            : $policy['redeem_points_per_currency'];
        $policy['min_redeem_points'] = $config->min_redeem_points !== null
            ? max(1, (int) $config->min_redeem_points)
            : $policy['min_redeem_points'];
        $policy['welcome_bonus_points'] = $config->welcome_bonus_points !== null
            ? max(0, (int) $config->welcome_bonus_points)
            : $policy['welcome_bonus_points'];
        $policy['metadata'] = is_array($config->metadata) ? $config->metadata : [];

        return $policy;
    }

    public function upsertCompanyConfig(Empresa $empresa, array $payload, ?int $actorId = null): CompanyLoyaltyConfig
    {
        $config = CompanyLoyaltyConfig::query()->firstOrNew([
            'company_id' => $empresa->id,
        ]);

        if (!$config->exists) {
            $config->created_by = $actorId;
        }

        if (array_key_exists('points_per_real', $payload)) {
            $config->points_per_real = $payload['points_per_real'] !== null
                ? max(0.01, (float) $payload['points_per_real'])
                : null;
        }

        if (array_key_exists('scan_base_points', $payload)) {
            $config->scan_base_points = $payload['scan_base_points'] !== null
                ? max(1, (int) $payload['scan_base_points'])
                : null;
        }

        if (array_key_exists('redeem_points_per_currency', $payload)) {
            $config->redeem_points_per_currency = max(1, (int) $payload['redeem_points_per_currency']);
        }

        if (array_key_exists('min_redeem_points', $payload)) {
            $config->min_redeem_points = max(1, (int) $payload['min_redeem_points']);
        }

        if (array_key_exists('welcome_bonus_points', $payload)) {
            $config->welcome_bonus_points = max(0, (int) $payload['welcome_bonus_points']);
        }

        if (array_key_exists('is_active', $payload)) {
            $config->is_active = (bool) $payload['is_active'];
        }

        if (array_key_exists('metadata', $payload)) {
            $config->metadata = is_array($payload['metadata']) ? $payload['metadata'] : [];
        }

        $config->updated_by = $actorId;
        $config->save();

        return $config->fresh();
    }

    public function onboardingStatus(Empresa $empresa): array
    {
        $owner = $empresa->owner()->first();
        $policy = $this->effectivePolicy($empresa);
        $profileComplete = $this->hasFilledString($empresa->nome)
            && $this->hasFilledString($empresa->cnpj)
            && $this->hasFilledString($empresa->telefone)
            && $this->hasFilledString($empresa->endereco);
        $hasOwner = $owner !== null && $owner->status !== 'bloqueado';
        $hasActiveQr = $this->hasActiveQrCode($empresa->id);
        $hasActivePromotion = $this->hasActivePromotion($empresa->id);
        $hasFirstTransaction = $this->hasFirstTransaction($empresa->id);
        $subscription = $this->subscriptionSnapshot($empresa->id);
        $policyConfigured = ($policy['source'] ?? 'platform_default') === 'company_override_active';

        $checks = [
            [
                'key' => 'perfil_empresa',
                'label' => 'Perfil da empresa completo',
                'ok' => $profileComplete,
                'required' => true,
            ],
            [
                'key' => 'usuario_responsavel',
                'label' => 'Usuario responsavel ativo',
                'ok' => $hasOwner,
                'required' => true,
            ],
            [
                'key' => 'politica_fidelidade',
                'label' => 'Politica de fidelidade configurada',
                'ok' => $policyConfigured,
                'required' => true,
            ],
            [
                'key' => 'qrcode_operacional',
                'label' => 'QR Code ativo para check-in',
                'ok' => $hasActiveQr,
                'required' => true,
            ],
            [
                'key' => 'campanha_ativa',
                'label' => 'Ao menos uma promocao ativa',
                'ok' => $hasActivePromotion,
                'required' => true,
            ],
            [
                'key' => 'primeira_transacao',
                'label' => 'Primeira transacao registrada',
                'ok' => $hasFirstTransaction,
                'required' => false,
            ],
        ];

        if ($subscription['available']) {
            $checks[] = [
                'key' => 'assinatura',
                'label' => 'Assinatura apta para operacao',
                'ok' => !$subscription['blocked'],
                'required' => true,
            ];
        }

        $requiredChecks = array_values(array_filter($checks, static fn (array $check): bool => $check['required'] === true));
        $completedChecks = array_values(array_filter($checks, static fn (array $check): bool => $check['ok'] === true));
        $completedRequiredChecks = array_values(array_filter($requiredChecks, static fn (array $check): bool => $check['ok'] === true));
        $pendingRequiredChecks = array_values(array_filter($requiredChecks, static fn (array $check): bool => $check['ok'] === false));
        $nextActions = array_map(
            static fn (array $check): string => $check['label'],
            $pendingRequiredChecks
        );

        $totalChecks = count($checks);
        $totalCompletedChecks = count($completedChecks);
        $progress = $totalChecks > 0
            ? (int) round(($totalCompletedChecks / $totalChecks) * 100)
            : 0;
        $isReady = count($requiredChecks) === count($completedRequiredChecks);

        return [
            'company_id' => $empresa->id,
            'company_name' => $empresa->nome,
            'is_ready' => $isReady,
            'progress_percent' => $progress,
            'checks_total' => $totalChecks,
            'checks_completed' => $totalCompletedChecks,
            'checks' => $checks,
            'next_actions' => $nextActions,
            'subscription' => $subscription,
            'effective_policy' => $policy,
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

    private function companyConfig(?Empresa $empresa): ?CompanyLoyaltyConfig
    {
        if (!$empresa) {
            return null;
        }

        if ($empresa->relationLoaded('loyaltyConfig')) {
            return $empresa->getRelation('loyaltyConfig');
        }

        return $empresa->loyaltyConfig()->first();
    }

    private function hasFilledString(mixed $value): bool
    {
        return is_string($value) && trim($value) !== '';
    }

    private function hasActiveQrCode(int $companyId): bool
    {
        if (!Schema::hasTable('qr_codes')) {
            return false;
        }

        $query = DB::table('qr_codes')->where('empresa_id', $companyId);

        if (Schema::hasColumn('qr_codes', 'active')) {
            $query->where('active', true);
        } elseif (Schema::hasColumn('qr_codes', 'ativo')) {
            $query->where('ativo', true);
        }

        return $query->exists();
    }

    private function hasActivePromotion(int $companyId): bool
    {
        if (!Schema::hasTable('promocoes')) {
            return false;
        }

        $query = DB::table('promocoes')->where('empresa_id', $companyId);

        if (Schema::hasColumn('promocoes', 'ativo')) {
            $query->where('ativo', true);
        }

        if (Schema::hasColumn('promocoes', 'status')) {
            $query->whereIn('status', ['ativa', 'active']);
        }

        return $query->exists();
    }

    private function hasFirstTransaction(int $companyId): bool
    {
        if (Schema::hasTable('ledger') && Schema::hasColumn('ledger', 'company_id')) {
            return DB::table('ledger')->where('company_id', $companyId)->exists();
        }

        if (Schema::hasTable('pontos') && Schema::hasColumn('pontos', 'empresa_id')) {
            return DB::table('pontos')->where('empresa_id', $companyId)->exists();
        }

        return false;
    }

    private function subscriptionSnapshot(int $companyId): array
    {
        if (!Schema::hasTable('subscriptions')) {
            return [
                'available' => false,
                'status' => null,
                'blocked' => false,
            ];
        }

        $subscription = DB::table('subscriptions')
            ->where('company_id', $companyId)
            ->orderByDesc('id')
            ->first();

        $status = $subscription?->status ? strtolower((string) $subscription->status) : null;
        $blocked = in_array($status, ['suspended', 'canceled'], true);

        return [
            'available' => true,
            'status' => $status,
            'blocked' => $blocked,
        ];
    }
}
