<?php

namespace App\Services;

use App\Models\BonusAdesao;
use App\Models\BonusAdesaoResgate;
use App\Models\Empresa;
use App\Models\InscricaoEmpresa;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class BonusAdesaoService
{
    public function companyBonuses(Empresa $empresa)
    {
        $query = BonusAdesao::query()
            ->where('empresa_id', $empresa->id);

        if (Schema::hasColumn('bonus_adesao', 'ativo')) {
            $query->orderByDesc('ativo');
        }

        if (Schema::hasColumn('bonus_adesao', 'ordem')) {
            $query->orderBy('ordem');
        }

        return $query->orderByDesc('created_at');
    }

    public function latestCompanyBonus(Empresa $empresa): ?BonusAdesao
    {
        return $this->companyBonuses($empresa)->first();
    }

    public function activeCompanyBonus(Empresa $empresa): ?BonusAdesao
    {
        return $this->companyBonuses($empresa)
            ->get()
            ->first(fn (BonusAdesao $bonus) => $bonus->isOperationallyAvailable());
    }

    public function saveBonus(Empresa $empresa, array $payload, ?BonusAdesao $bonus = null): BonusAdesao
    {
        $titulo = array_key_exists('titulo', $payload)
            ? trim((string) $payload['titulo'])
            : ($bonus?->titulo ?? '');

        $descricao = array_key_exists('descricao', $payload)
            ? $this->normalizeNullableString($payload['descricao'])
            : $bonus?->descricao;

        $imagem = array_key_exists('imagem', $payload)
            ? $this->normalizeNullableString($payload['imagem'])
            : $bonus?->imagem;

        $ativo = array_key_exists('ativo', $payload)
            ? (bool) $payload['ativo']
            : (bool) ($bonus?->ativo ?? true);

        $dataExpiracao = array_key_exists('data_expiracao', $payload)
            ? $this->normalizeNullableString($payload['data_expiracao'])
            : $bonus?->data_expiracao;

        $ordem = array_key_exists('ordem', $payload)
            ? max(1, (int) $payload['ordem'])
            : (int) ($bonus?->ordem ?? 1);

        $termos = array_key_exists('termos', $payload)
            ? $this->normalizeNullableString($payload['termos'])
            : $bonus?->termos;

        $data = [
            'empresa_id' => $empresa->id,
            'titulo' => $titulo,
            'descricao' => $descricao,
            'imagem' => $imagem,
            'ativo' => $ativo,
            'data_expiracao' => $dataExpiracao,
            'limite_por_cliente' => 1,
            'tipo' => BonusAdesao::TYPE_ADHESION_BONUS,
            'ordem' => $ordem,
            'termos' => $termos,
            'tipo_desconto' => $payload['tipo_desconto'] ?? ($bonus?->tipo_desconto ?? 'valor_fixo'),
            'valor_desconto' => $payload['valor_desconto'] ?? ($bonus?->valor_desconto ?? 0),
        ];

        if ($data['ativo']) {
            BonusAdesao::query()
                ->where('empresa_id', $empresa->id)
                ->when($bonus?->id, fn ($query) => $query->where('id', '!=', $bonus->id))
                ->update(['ativo' => \Illuminate\Support\Facades\DB::raw('false')]);
        }

        if ($bonus) {
            $bonus->update($data);

            return $bonus->refresh();
        }

        return BonusAdesao::query()->create($data);
    }

    public function evaluateCustomerBonus(Empresa $empresa, User $customer): array
    {
        $inscricao = $this->findInscricao($empresa, $customer);
        $latestBonus = $this->latestCompanyBonus($empresa);

        if (!$inscricao) {
            return [
                'status' => 'not_linked',
                'message' => 'Cliente nao esta vinculado a esta empresa.',
                'bonus' => $latestBonus ? $this->serializeBonus($latestBonus) : null,
                'can_present_qr' => false,
                'can_validate' => false,
                'redeemed_at' => null,
            ];
        }

        $activeBonus = $this->activeCompanyBonus($empresa);
        if (!$activeBonus) {
            $status = $latestBonus && $latestBonus->isExpired()
                ? BonusAdesaoResgate::STATUS_EXPIRED
                : 'unavailable';

            return [
                'status' => $status,
                'message' => $status === BonusAdesaoResgate::STATUS_EXPIRED
                    ? 'Bonus de adesao expirado.'
                    : 'Nenhum bonus de adesao ativo no momento.',
                'bonus' => $latestBonus ? $this->serializeBonus($latestBonus) : null,
                'can_present_qr' => false,
                'can_validate' => false,
                'redeemed_at' => null,
            ];
        }

        $resgate = $this->findResgate($activeBonus, $customer);
        $legacyFlagOnly = $this->shouldTreatLegacyFlagAsRedeemed($empresa, $customer, $inscricao);
        if ($resgate || $legacyFlagOnly) {
            return [
                'status' => BonusAdesaoResgate::STATUS_REDEEMED,
                'message' => 'Bonus de adesao ja utilizado por este cliente.',
                'bonus' => $this->serializeBonus($activeBonus),
                'can_present_qr' => false,
                'can_validate' => false,
                'redeemed_at' => optional($resgate?->redeemed_at ?? $resgate?->data_resgate)->toIso8601String(),
                'validated_by' => $resgate?->validated_by,
            ];
        }

        return [
            'status' => BonusAdesaoResgate::STATUS_AVAILABLE,
            'message' => 'Bonus de adesao disponivel para validacao no estabelecimento.',
            'bonus' => $this->serializeBonus($activeBonus),
            'can_present_qr' => true,
            'can_validate' => true,
            'redeemed_at' => null,
        ];
    }

    public function lookupCustomerByQr(Empresa $empresa, string $qrCode): array
    {
        $decoded = app(ClienteQrCodeService::class)->decodificar($qrCode);
        if (!$decoded) {
            throw new \DomainException('QR Code do cliente invalido.');
        }

        $customer = User::query()->find((int) ($decoded['user_id'] ?? 0));
        if (!$customer || !$this->isCustomer($customer)) {
            throw new \DomainException('Cliente nao encontrado para este QR Code.');
        }

        return $this->lookupCustomer($empresa, $customer, $decoded);
    }

    public function lookupCustomer(Empresa $empresa, User $customer, array $qrPayload = []): array
    {
        $inscricao = $this->findInscricao($empresa, $customer);
        $bonusStatus = $this->evaluateCustomerBonus($empresa, $customer);

        return [
            'cliente' => [
                'id' => $customer->id,
                'nome' => $customer->name,
                'email' => $customer->email,
                'telefone' => $customer->telefone ?? null,
            ],
            'empresa' => [
                'id' => $empresa->id,
                'nome' => $empresa->nome,
            ],
            'vinculo' => [
                'existe' => (bool) $inscricao,
                'data_inscricao' => optional($inscricao?->data_inscricao)->toIso8601String(),
                'ultima_visita' => optional($inscricao?->ultima_visita)->toIso8601String(),
            ],
            'bonus_adesao' => $bonusStatus,
            'qrcode' => [
                'version' => $qrPayload['version'] ?? null,
                'expires_at' => $qrPayload['expires_at'] ?? null,
            ],
        ];
    }

    public function validateBonus(Empresa $empresa, BonusAdesao $bonus, User $customer, User $validator): array
    {
        if ($bonus->empresa_id !== $empresa->id) {
            throw new \DomainException('Empresa nao pode validar bonus de outra empresa.');
        }

        if (!$bonus->isOperationallyAvailable()) {
            throw new \DomainException($bonus->isExpired()
                ? 'Bonus de adesao expirado.'
                : 'Bonus de adesao inativo.');
        }

        $inscricao = $this->findInscricao($empresa, $customer);
        if (!$inscricao) {
            throw new \DomainException('Cliente nao esta vinculado a esta empresa.');
        }

        $existing = $this->findResgate($bonus, $customer);
        $legacyFlagOnly = $this->shouldTreatLegacyFlagAsRedeemed($empresa, $customer, $inscricao);
        if ($existing || $legacyFlagOnly) {
            throw new \DomainException('Bonus de adesao ja foi validado para este cliente.');
        }

        $timestamp = now();

        try {
            DB::transaction(function () use ($bonus, $empresa, $customer, $validator, $inscricao, $timestamp): void {
                $duplicate = BonusAdesaoResgate::query()
                    ->where('bonus_id', $bonus->id)
                    ->where('empresa_id', $bonus->empresa_id)
                    ->where('user_id', $customer->id)
                    ->lockForUpdate()
                    ->exists();

                if ($duplicate) {
                    throw new \DomainException('Bonus de adesao ja foi validado para este cliente.');
                }

                BonusAdesaoResgate::query()->create([
                    'bonus_id' => $bonus->id,
                    'empresa_id' => $empresa->id,
                    'user_id' => $customer->id,
                    'status' => BonusAdesaoResgate::STATUS_REDEEMED,
                    'validated_by' => $validator->id,
                    'redeemed_at' => $timestamp,
                    'resgatado' => true,
                    'data_resgate' => $timestamp,
                    'pontos' => 0,
                ]);

                $inscricao->update([
                    'bonus_adesao_resgatado' => true,
                    'ultima_visita' => $timestamp,
                ]);
            });
        } catch (QueryException $e) {
            if ($this->isDuplicateResgateConstraint($e)) {
                throw new \DomainException('Bonus de adesao ja foi validado para este cliente.', 0, $e);
            }

            throw $e;
        }

        return $this->lookupCustomer($empresa, $customer);
    }

    public function serializeBonus(BonusAdesao $bonus, array $extra = []): array
    {
        return array_merge([
            'id' => $bonus->id,
            'empresa_id' => $bonus->empresa_id,
            'titulo' => $bonus->titulo,
            'descricao' => $bonus->descricao,
            'imagem' => $bonus->imagem,
            'imagem_url' => $this->resolveImageUrl($bonus->imagem),
            'data_expiracao' => optional($bonus->data_expiracao)->toIso8601String(),
            'ativo' => (bool) $bonus->ativo,
            'tipo' => $bonus->tipo ?: BonusAdesao::TYPE_ADHESION_BONUS,
            'limite_por_cliente' => (int) ($bonus->limite_por_cliente ?? 1),
            'ordem' => (int) ($bonus->ordem ?? 1),
            'termos' => $bonus->termos,
            'status' => $bonus->isExpired()
                ? BonusAdesaoResgate::STATUS_EXPIRED
                : ((bool) $bonus->ativo ? BonusAdesaoResgate::STATUS_AVAILABLE : 'inactive'),
        ], $extra);
    }

    public function resolveImageUrl(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }

        if (
            str_starts_with($path, 'http://')
            || str_starts_with($path, 'https://')
            || str_starts_with($path, '/')
        ) {
            return $path;
        }

        return Storage::url($path);
    }

    private function isCustomer(User $user): bool
    {
        $perfil = strtolower(trim((string) ($user->perfil ?? $user->role ?? $user->tipo ?? '')));

        return in_array($perfil, ['cliente', 'customer'], true);
    }

    private function findInscricao(Empresa $empresa, User $customer): ?InscricaoEmpresa
    {
        return InscricaoEmpresa::query()
            ->where('empresa_id', $empresa->id)
            ->where('user_id', $customer->id)
            ->first();
    }

    private function findResgate(BonusAdesao $bonus, User $customer): ?BonusAdesaoResgate
    {
        return BonusAdesaoResgate::query()
            ->where('bonus_id', $bonus->id)
            ->where('empresa_id', $bonus->empresa_id)
            ->where('user_id', $customer->id)
            ->where('status', BonusAdesaoResgate::STATUS_REDEEMED)
            ->latest('id')
            ->first();
    }

    private function shouldTreatLegacyFlagAsRedeemed(
        Empresa $empresa,
        User $customer,
        ?InscricaoEmpresa $inscricao
    ): bool {
        if (!$inscricao || !(bool) $inscricao->bonus_adesao_resgatado) {
            return false;
        }

        $hasCanonicalResgate = BonusAdesaoResgate::query()
            ->where('empresa_id', $empresa->id)
            ->where('user_id', $customer->id)
            ->exists();

        if ($hasCanonicalResgate) {
            return false;
        }

        $configuredBonusesCount = BonusAdesao::query()
            ->where('empresa_id', $empresa->id)
            ->count();

        return $configuredBonusesCount <= 1;
    }

    private function isDuplicateResgateConstraint(QueryException $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'bonus_adesao_resgates_bonus_id_user_id_unique')
            || str_contains($message, 'unique constraint failed: bonus_adesao_resgates.bonus_id, bonus_adesao_resgates.user_id');
    }

    private function normalizeNullableString($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }
}
