<?php

namespace App\Services;

use App\Models\BonusAniversario;
use App\Models\BonusAniversarioResgate;
use App\Models\Empresa;
use App\Models\InscricaoEmpresa;
use App\Models\NotificacaoPush;
use App\Models\PushSubscription;
use App\Models\User;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BonusAniversarioService
{
    public const LOG_STATUS_PENDING = 'pending';
    public const LOG_STATUS_SENT = 'sent';
    public const LOG_STATUS_FAILED = 'failed';
    public const LOG_STATUS_NO_SUBSCRIPTION = 'no_subscription';

    public function __construct(
        private readonly WebPushDeliveryService $pushDeliveryService
    ) {
    }

    public function companyBonuses(Empresa $empresa)
    {
        return BonusAniversario::query()
            ->where('empresa_id', $empresa->id)
            ->orderByDesc('ativo')
            ->orderByDesc('created_at');
    }

    public function latestCompanyBonus(Empresa $empresa): ?BonusAniversario
    {
        return $this->companyBonuses($empresa)->first();
    }

    public function activeCompanyBonus(Empresa $empresa): ?BonusAniversario
    {
        return $this->companyBonuses($empresa)
            ->get()
            ->first(fn (BonusAniversario $bonus) => $bonus->isOperationallyAvailable());
    }

    public function saveBonus(Empresa $empresa, array $payload, ?BonusAniversario $bonus = null): BonusAniversario
    {
        $titulo = array_key_exists('titulo', $payload)
            ? trim((string) $payload['titulo'])
            : trim((string) ($bonus?->titulo ?? ''));

        $descricao = array_key_exists('descricao', $payload)
            ? $this->normalizeNullableString($payload['descricao'])
            : $bonus?->descricao;

        $imagem = array_key_exists('imagem', $payload)
            ? $this->normalizeNullableString($payload['imagem'])
            : $bonus?->imagem;

        $diasValidade = array_key_exists('dias_validade', $payload)
            ? max(1, (int) $payload['dias_validade'])
            : $bonus?->daysValidity();

        if (array_key_exists('dias_validade', $payload) && (int) $payload['dias_validade'] <= 0) {
            $diasValidade = null;
        }

        $ativo = array_key_exists('ativo', $payload)
            ? (bool) $payload['ativo']
            : (bool) ($bonus?->ativo ?? true);

        $notificationTitle = $this->normalizeNullableString($payload['notification_title'] ?? $bonus?->notification_title ?? null)
            ?: $titulo;

        $notificationBody = $this->normalizeNullableString($payload['notification_body'] ?? $bonus?->notification_body ?? null)
            ?: Str::limit((string) $descricao, 120, '');

        $data = [
            'empresa_id' => $empresa->id,
            'titulo' => $titulo,
            'descricao' => $descricao,
            'presente' => $payload['presente'] ?? ($bonus?->presente ?? $titulo),
            'imagem' => $imagem,
            'dias_validade' => $diasValidade,
            'notification_title' => Str::limit($notificationTitle, 80, ''),
            'notification_body' => Str::limit($notificationBody, 120, ''),
            'ativo' => $ativo,
        ];

        return DB::transaction(function () use ($empresa, $bonus, $data) {
            if ($data['ativo']) {
                BonusAniversario::query()
                    ->where('empresa_id', $empresa->id)
                    ->when($bonus?->id, fn ($query) => $query->where('id', '!=', $bonus->id))
                    ->update(['ativo' => false]);
            }

            if ($bonus) {
                $bonus->update($data);

                return $bonus->refresh();
            }

            return BonusAniversario::query()->create($data);
        });
    }

    public function customerBirthdaySnapshot(Empresa $empresa, User $customer, ?Carbon $reference = null): array
    {
        $reference ??= now();
        $inscricao = $this->findInscricao($empresa, $customer);
        $latestBonus = $this->latestCompanyBonus($empresa);

        if (!$inscricao) {
            return [
                'status' => BonusAniversarioResgate::STATUS_NOT_LINKED,
                'message' => 'Cliente nao esta vinculado a esta empresa.',
                'bonus' => $latestBonus ? $this->serializeBonus($latestBonus) : null,
                'can_present_qr' => false,
                'can_validate' => false,
                'redeemed_at' => null,
                'valid_from' => null,
                'valid_until' => null,
                'eligibility_year' => (int) $reference->year,
            ];
        }

        $activeBonus = $this->activeCompanyBonus($empresa);
        if (!$activeBonus) {
            return [
                'status' => BonusAniversarioResgate::STATUS_INACTIVE,
                'message' => 'Nenhum bônus aniversário ativo no momento.',
                'bonus' => $latestBonus ? $this->serializeBonus($latestBonus) : null,
                'can_present_qr' => false,
                'can_validate' => false,
                'redeemed_at' => null,
                'valid_from' => null,
                'valid_until' => null,
                'eligibility_year' => (int) $reference->year,
            ];
        }

        $window = $this->resolveEligibilityWindow($customer, $activeBonus, $reference);
        $serialized = $this->serializeBonus($activeBonus, [
            'valid_from' => optional($window['starts_at'] ?? null)->toIso8601String(),
            'valid_until' => optional($window['ends_at'] ?? null)->toIso8601String(),
            'eligibility_year' => $window['year'] ?? (int) $reference->year,
        ]);

        if (($window['status'] ?? null) === BonusAniversarioResgate::STATUS_MISSING_BIRTH_DATE) {
            return [
                'status' => BonusAniversarioResgate::STATUS_MISSING_BIRTH_DATE,
                'message' => 'Cliente precisa informar a data de nascimento para liberar o bônus aniversário.',
                'bonus' => $serialized,
                'can_present_qr' => false,
                'can_validate' => false,
                'redeemed_at' => null,
                'valid_from' => null,
                'valid_until' => null,
                'eligibility_year' => $window['year'] ?? (int) $reference->year,
            ];
        }

        if (!($window['eligible'] ?? false)) {
            return [
                'status' => BonusAniversarioResgate::STATUS_OUT_OF_WINDOW,
                'message' => $window['message'] ?? 'Cliente fora do período elegível do bônus aniversário.',
                'bonus' => $serialized,
                'can_present_qr' => false,
                'can_validate' => false,
                'redeemed_at' => null,
                'valid_from' => optional($window['starts_at'] ?? null)->toIso8601String(),
                'valid_until' => optional($window['ends_at'] ?? null)->toIso8601String(),
                'eligibility_year' => $window['year'] ?? (int) $reference->year,
            ];
        }

        $redemption = $this->findYearlyRedemption($empresa, $customer, (int) $window['year']);
        if ($redemption) {
            return [
                'status' => BonusAniversarioResgate::STATUS_REDEEMED,
                'message' => 'Bônus aniversário já utilizado neste ano para esta empresa.',
                'bonus' => $serialized,
                'can_present_qr' => false,
                'can_validate' => false,
                'redeemed_at' => optional($redemption->redeemed_at)->toIso8601String(),
                'validated_by' => $redemption->validated_by,
                'valid_from' => optional($window['starts_at'] ?? null)->toIso8601String(),
                'valid_until' => optional($window['ends_at'] ?? null)->toIso8601String(),
                'eligibility_year' => $window['year'],
            ];
        }

        return [
            'status' => BonusAniversarioResgate::STATUS_AVAILABLE,
            'message' => 'Cliente elegível ao bônus aniversário. A validação deve acontecer no estabelecimento.',
            'bonus' => $serialized,
            'can_present_qr' => true,
            'can_validate' => true,
            'redeemed_at' => null,
            'valid_from' => optional($window['starts_at'] ?? null)->toIso8601String(),
            'valid_until' => optional($window['ends_at'] ?? null)->toIso8601String(),
            'eligibility_year' => $window['year'],
        ];
    }

    public function listBonusesForCustomer(User $customer, ?int $empresaId = null): array
    {
        if ($empresaId) {
            $empresa = Empresa::query()->publiclyVisible()->find($empresaId);
            if (!$empresa) {
                return [];
            }

            return [
                $this->withCompanyPayload($empresa, $this->customerBirthdaySnapshot($empresa, $customer)),
            ];
        }

        $linkedCompanies = InscricaoEmpresa::query()
            ->with('empresa')
            ->where('user_id', $customer->id)
            ->get()
            ->filter(fn (InscricaoEmpresa $inscricao) => $inscricao->empresa instanceof Empresa && $inscricao->empresa->isPubliclyVisible())
            ->map(fn (InscricaoEmpresa $inscricao) => $inscricao->empresa)
            ->unique('id')
            ->values();

        return $linkedCompanies
            ->map(function (Empresa $empresa) use ($customer) {
                return $this->withCompanyPayload($empresa, $this->customerBirthdaySnapshot($empresa, $customer));
            })
            ->filter(fn (array $item) => ($item['status'] ?? null) === BonusAniversarioResgate::STATUS_AVAILABLE)
            ->values()
            ->all();
    }

    public function validateBonus(Empresa $empresa, BonusAniversario $bonus, User $customer, User $validator): array
    {
        $this->guardBonusOwnership($empresa, $bonus);

        if (!$bonus->isOperationallyAvailable()) {
            throw new DomainException('Bônus aniversário inativo.');
        }

        $inscricao = $this->findInscricao($empresa, $customer);
        if (!$inscricao) {
            throw new DomainException('Cliente nao esta vinculado a esta empresa.');
        }

        $window = $this->resolveEligibilityWindow($customer, $bonus, now());
        if (($window['status'] ?? null) === BonusAniversarioResgate::STATUS_MISSING_BIRTH_DATE) {
            throw new DomainException('Cliente nao possui data de nascimento cadastrada.');
        }

        if (!($window['eligible'] ?? false)) {
            throw new DomainException('Cliente não está no período elegível do bônus aniversário.');
        }

        $year = (int) ($window['year'] ?? now()->year);
        if ($this->findYearlyRedemption($empresa, $customer, $year)) {
            throw new DomainException('Bônus aniversário já utilizado neste ano para esta empresa.');
        }

        $timestamp = now();

        try {
            DB::transaction(function () use ($bonus, $empresa, $customer, $validator, $inscricao, $timestamp, $year): void {
                $duplicate = BonusAniversarioResgate::query()
                    ->where('empresa_id', $empresa->id)
                    ->where('user_id', $customer->id)
                    ->where('ano', $year)
                    ->lockForUpdate()
                    ->exists();

                if ($duplicate) {
                    throw new DomainException('Bônus aniversário já utilizado neste ano para esta empresa.');
                }

                BonusAniversarioResgate::query()->create([
                    'bonus_aniversario_id' => $bonus->id,
                    'empresa_id' => $empresa->id,
                    'user_id' => $customer->id,
                    'ano' => $year,
                    'status' => BonusAniversarioResgate::STATUS_REDEEMED,
                    'redeemed_at' => $timestamp,
                    'validated_by' => $validator->id,
                ]);

                $inscricao->update([
                    'ultima_visita' => $timestamp,
                ]);
            });
        } catch (QueryException $e) {
            if ($this->isDuplicateRedemptionConstraint($e)) {
                throw new DomainException('Bônus aniversário já utilizado neste ano para esta empresa.', 0, $e);
            }

            throw $e;
        }

        return $this->customerBirthdaySnapshot($empresa, $customer);
    }

    public function sendToEligibleCustomers(Empresa $empresa, BonusAniversario $bonus, User $actor): array
    {
        $this->guardBonusOwnership($empresa, $bonus);

        if (!$bonus->isOperationallyAvailable()) {
            throw new DomainException('Bônus aniversário inativo.');
        }

        $eligibleTargets = InscricaoEmpresa::query()
            ->with('user')
            ->where('empresa_id', $empresa->id)
            ->get()
            ->filter(function (InscricaoEmpresa $inscricao) use ($empresa) {
                return $inscricao->user
                    && $this->isCustomer($inscricao->user)
                    && $this->customerBirthdaySnapshot($empresa, $inscricao->user)['status'] === BonusAniversarioResgate::STATUS_AVAILABLE;
            })
            ->values();

        if ($eligibleTargets->isEmpty()) {
            throw new DomainException('Nenhum cliente vinculado e elegível para receber o bônus aniversário agora.');
        }

        $auth = $this->pushDeliveryService->auth();

        $subscriptionsByUser = PushSubscription::query()
            ->active()
            ->whereIn('user_id', $eligibleTargets->pluck('user_id')->unique()->all())
            ->get()
            ->groupBy('user_id');

        $sentAt = now();
        $title = $bonus->notificationTitle();
        $body = $bonus->notificationBody();
        $payload = [
            'type' => 'aniversario',
            'empresa_id' => $empresa->id,
            'bonus_aniversario_id' => $bonus->id,
            'url' => '/detalhe_do_parceiro.html?id=' . $empresa->id,
        ];

        $stats = [
            'status' => $auth === null ? 'config_missing' : 'pending',
            'config_missing' => $auth === null,
            'message' => $auth === null ? 'Configuração de push pendente no servidor.' : null,
            'total_elegiveis' => $eligibleTargets->count(),
            'total_com_subscription' => 0,
            'enviados' => 0,
            'falhas' => 0,
            'ignorados_sem_subscription' => 0,
            'ignorados_sem_vinculo' => 0,
            'total_targeted' => $eligibleTargets->count(),
            'total_with_subscription' => 0,
            'total_sent' => 0,
            'total_failed' => 0,
            'total_without_subscription' => 0,
        ];

        foreach ($eligibleTargets as $inscricao) {
            $customer = $inscricao->user;
            if (!$customer) {
                continue;
            }

            $log = NotificacaoPush::query()->create([
                'user_id' => $customer->id,
                'empresa_id' => $empresa->id,
                'bonus_aniversario_id' => $bonus->id,
                'tipo' => 'aniversario',
                'titulo' => $title,
                'mensagem' => $body,
                'imagem' => $bonus->imagem,
                'status' => self::LOG_STATUS_PENDING,
                'erro' => null,
                'enviado' => false,
                'data_envio' => null,
            ]);

            $subscriptions = $subscriptionsByUser->get($customer->id, collect());
            if ($subscriptions->isEmpty()) {
                $stats['ignorados_sem_subscription']++;
                $stats['total_without_subscription']++;
                $log->update([
                    'status' => self::LOG_STATUS_NO_SUBSCRIPTION,
                    'erro' => 'Cliente sem subscription push ativa.',
                    'data_envio' => $sentAt,
                ]);
                continue;
            }

            $stats['total_com_subscription']++;
            $stats['total_with_subscription']++;
            $result = $auth === null
                ? [
                    'sent' => false,
                    'error' => 'Configuração de push pendente no servidor.',
                    'status' => 'config_missing',
                    'config_missing' => true,
                ]
                : $this->pushDeliveryService->deliverToSubscriptions($subscriptions, $title, $body, $payload, $auth);

            if ($result['sent']) {
                $stats['enviados']++;
                $stats['total_sent']++;
                $log->update([
                    'status' => self::LOG_STATUS_SENT,
                    'erro' => $result['error'] ?: null,
                    'enviado' => true,
                    'data_envio' => $sentAt,
                ]);
            } else {
                $stats['falhas']++;
                $stats['total_failed']++;
                $log->update([
                    'status' => self::LOG_STATUS_FAILED,
                    'erro' => $result['error'] ?: 'Falha ao entregar push para este cliente.',
                    'data_envio' => $sentAt,
                ]);
            }
        }

        if (($stats['enviados'] ?? 0) > 0) {
            $stats['status'] = 'sent';
            $stats['config_missing'] = false;
        } elseif (($stats['config_missing'] ?? false) === true) {
            $stats['status'] = 'config_missing';
        } elseif (($stats['total_com_subscription'] ?? 0) === 0) {
            $stats['status'] = 'no_subscription';
            $stats['message'] = 'O bonus aniversario esta ativo, mas nenhum aniversariante elegivel ativou notificacoes neste dispositivo ainda.';
        } else {
            $stats['status'] = 'failed';
            $stats['message'] = 'Nao foi possivel entregar o bonus aniversario para os aniversariantes com notificacoes ativas.';
        }

        return [
            'bonus' => $this->serializeBonus($bonus->fresh()),
            'delivery' => $stats,
        ];
    }

    public function serializeBonus(BonusAniversario $bonus, array $extra = []): array
    {
        return array_merge([
            'id' => $bonus->id,
            'empresa_id' => $bonus->empresa_id,
            'titulo' => $bonus->titulo,
            'descricao' => $bonus->descricao,
            'imagem' => $bonus->imagem,
            'imagem_url' => $bonus->imageUrl(),
            'dias_validade' => $bonus->daysValidity(),
            'validade_tipo' => $bonus->daysValidity() ? 'days_after_birthday' : 'birthday_month',
            'validade_descricao' => $bonus->daysValidity()
                ? sprintf('Válido por %d dia(s) a partir da data de aniversário.', $bonus->daysValidity())
                : 'Válido durante todo o mês do aniversário.',
            'notification_title' => $bonus->notificationTitle(),
            'notification_body' => $bonus->notificationBody(),
            'ativo' => (bool) $bonus->ativo,
            'status' => $bonus->isOperationallyAvailable()
                ? BonusAniversarioResgate::STATUS_AVAILABLE
                : BonusAniversarioResgate::STATUS_INACTIVE,
        ], $extra);
    }

    private function withCompanyPayload(Empresa $empresa, array $snapshot): array
    {
        return array_merge($snapshot, [
            'empresa' => [
                'id' => $empresa->id,
                'nome' => $empresa->nome,
                'logo' => $empresa->logo,
                'categoria' => $empresa->categoria ?? $empresa->ramo ?? null,
                'public_page_url' => '/detalhe_do_parceiro.html?id=' . $empresa->id,
            ],
        ]);
    }

    private function resolveEligibilityWindow(User $customer, BonusAniversario $bonus, Carbon $reference): array
    {
        if (!$customer->data_nascimento) {
            return [
                'status' => BonusAniversarioResgate::STATUS_MISSING_BIRTH_DATE,
                'eligible' => false,
                'message' => 'Cliente sem data de nascimento cadastrada.',
                'year' => (int) $reference->year,
            ];
        }

        $birthDate = $customer->data_nascimento instanceof Carbon
            ? $customer->data_nascimento->copy()
            : Carbon::parse((string) $customer->data_nascimento);

        $year = (int) $reference->year;
        $monthDays = Carbon::create($year, (int) $birthDate->month, 1)->daysInMonth;
        $birthday = Carbon::create($year, (int) $birthDate->month, min((int) $birthDate->day, $monthDays), 0, 0, 0, $reference->timezone);

        $daysValidity = $bonus->daysValidity();
        if ($daysValidity !== null) {
            $startsAt = $birthday->copy()->startOfDay();
            $endsAt = $birthday->copy()->addDays($daysValidity - 1)->endOfDay();
            $eligible = $reference->betweenIncluded($startsAt, $endsAt);

            return [
                'status' => $eligible ? BonusAniversarioResgate::STATUS_AVAILABLE : BonusAniversarioResgate::STATUS_OUT_OF_WINDOW,
                'eligible' => $eligible,
                'message' => $eligible
                    ? 'Cliente está dentro da janela configurada para o bônus aniversário.'
                    : sprintf('Disponível apenas por %d dia(s) a partir da data de aniversário.', $daysValidity),
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'year' => $year,
            ];
        }

        $startsAt = $birthday->copy()->startOfMonth();
        $endsAt = $birthday->copy()->endOfMonth();
        $eligible = $reference->month === $birthday->month;

        return [
            'status' => $eligible ? BonusAniversarioResgate::STATUS_AVAILABLE : BonusAniversarioResgate::STATUS_OUT_OF_WINDOW,
            'eligible' => $eligible,
            'message' => $eligible
                ? 'Cliente está no mês do aniversário.'
                : 'Disponível apenas durante o mês do aniversário.',
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'year' => $year,
        ];
    }

    private function findInscricao(Empresa $empresa, User $customer): ?InscricaoEmpresa
    {
        return InscricaoEmpresa::query()
            ->where('empresa_id', $empresa->id)
            ->where('user_id', $customer->id)
            ->first();
    }

    private function findYearlyRedemption(Empresa $empresa, User $customer, int $year): ?BonusAniversarioResgate
    {
        return BonusAniversarioResgate::query()
            ->where('empresa_id', $empresa->id)
            ->where('user_id', $customer->id)
            ->where('ano', $year)
            ->where('status', BonusAniversarioResgate::STATUS_REDEEMED)
            ->latest('id')
            ->first();
    }

    private function guardBonusOwnership(Empresa $empresa, BonusAniversario $bonus): void
    {
        if ($bonus->empresa_id !== $empresa->id) {
            throw new DomainException('Empresa não pode operar bônus aniversário de outra empresa.');
        }
    }

    private function isCustomer(User $user): bool
    {
        $perfil = Str::lower(trim((string) ($user->perfil ?? $user->role ?? $user->tipo ?? '')));

        return in_array($perfil, ['cliente', 'customer'], true);
    }

    private function isDuplicateRedemptionConstraint(QueryException $e): bool
    {
        $message = Str::lower($e->getMessage());

        return str_contains($message, 'bonus_aniversario_resgates_bonus_user_ano_unique')
            || str_contains($message, 'bonus_aniversario_resgates_empresa_user_ano_unique')
            || str_contains($message, 'unique constraint failed: bonus_aniversario_resgates.bonus_aniversario_id, bonus_aniversario_resgates.user_id, bonus_aniversario_resgates.ano')
            || str_contains($message, 'unique constraint failed: bonus_aniversario_resgates.empresa_id, bonus_aniversario_resgates.user_id, bonus_aniversario_resgates.ano');
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
