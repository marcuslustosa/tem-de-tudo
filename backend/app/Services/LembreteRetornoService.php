<?php

namespace App\Services;

use App\Models\BonusAdesaoResgate;
use App\Models\BonusAniversarioResgate;
use App\Models\CartaoFidelidadeMovimento;
use App\Models\Empresa;
use App\Models\InscricaoEmpresa;
use App\Models\LembreteAusencia;
use App\Models\LembreteEnvio;
use App\Models\NotificacaoPush;
use App\Models\PromocaoResgate;
use App\Models\PushSubscription;
use App\Models\User;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class LembreteRetornoService
{
    public function __construct(
        private readonly WebPushDeliveryService $pushDeliveryService
    ) {
    }

    public function companyReminders(Empresa $empresa)
    {
        return LembreteAusencia::query()
            ->where('empresa_id', $empresa->id)
            ->orderByDesc('ativo')
            ->orderByDesc('created_at');
    }

    public function latestCompanyReminder(Empresa $empresa): ?LembreteAusencia
    {
        return $this->companyReminders($empresa)->first();
    }

    public function activeCompanyReminder(Empresa $empresa): ?LembreteAusencia
    {
        return $this->companyReminders($empresa)
            ->get()
            ->first(fn (LembreteAusencia $reminder) => $reminder->isOperationallyAvailable());
    }

    public function saveReminder(Empresa $empresa, array $payload, ?LembreteAusencia $reminder = null): LembreteAusencia
    {
        $diasSemVisita = array_key_exists('dias_sem_visita', $payload)
            ? max(1, (int) $payload['dias_sem_visita'])
            : max(1, (int) ($reminder?->daysWithoutVisit() ?? 30));

        $titulo = array_key_exists('titulo', $payload)
            ? trim((string) $payload['titulo'])
            : trim((string) ($reminder?->titulo ?? ''));

        $mensagem = array_key_exists('mensagem', $payload)
            ? $this->normalizeNullableString($payload['mensagem'])
            : $reminder?->mensagem;

        $ativo = array_key_exists('ativo', $payload)
            ? (bool) $payload['ativo']
            : (bool) ($reminder?->ativo ?? true);

        $data = [
            'empresa_id' => $empresa->id,
            'dias_ausencia' => $diasSemVisita,
            'dias_sem_visita' => $diasSemVisita,
            'titulo' => $titulo,
            'mensagem' => $mensagem,
            'ativo' => $ativo,
        ];

        return DB::transaction(function () use ($empresa, $reminder, $data) {
            if ($data['ativo']) {
                LembreteAusencia::query()
                    ->where('empresa_id', $empresa->id)
                    ->when($reminder?->id, fn ($query) => $query->where('id', '!=', $reminder->id))
                    ->update(['ativo' => false]);
            }

            if ($reminder) {
                $reminder->update($data);

                return $reminder->refresh();
            }

            return LembreteAusencia::query()->create($data);
        });
    }

    public function sendEligibleReminders(Empresa $empresa, LembreteAusencia $reminder, User $actor): array
    {
        $this->guardReminderOwnership($empresa, $reminder);

        if (!$reminder->isOperationallyAvailable()) {
            throw new DomainException('Lembrete de retorno inativo.');
        }

        $targets = $this->eligibleTargets($empresa, $reminder);
        if ($targets->isEmpty()) {
            throw new DomainException('Nenhum cliente vinculado esta elegivel ao lembrete de retorno agora.');
        }

        $auth = $this->pushDeliveryService->auth();
        if ($auth === null) {
            throw new DomainException('Push web nao esta configurado neste ambiente.');
        }

        $subscriptionsByUser = PushSubscription::query()
            ->whereIn('user_id', $targets->pluck('customer.id')->unique()->all())
            ->get()
            ->groupBy('user_id');

        $sentAt = now();
        $stats = [
            'total_targeted' => $targets->count(),
            'total_with_subscription' => 0,
            'total_sent' => 0,
            'total_failed' => 0,
            'total_without_subscription' => 0,
        ];

        foreach ($targets as $target) {
            /** @var User $customer */
            $customer = $target['customer'];
            /** @var Carbon $referenceLastVisit */
            $referenceLastVisit = $target['reference_last_visit_at'];

            try {
                $envio = DB::transaction(function () use ($reminder, $empresa, $customer, $referenceLastVisit, $sentAt) {
                    $duplicate = LembreteEnvio::query()
                        ->where('lembrete_id', $reminder->id)
                        ->where('user_id', $customer->id)
                        ->where('reference_last_visit_at', $referenceLastVisit)
                        ->lockForUpdate()
                        ->first();

                    if ($duplicate) {
                        return null;
                    }

                    return LembreteEnvio::query()->create([
                        'lembrete_id' => $reminder->id,
                        'empresa_id' => $empresa->id,
                        'user_id' => $customer->id,
                        'sent_at' => $sentAt,
                        'reference_last_visit_at' => $referenceLastVisit,
                        'status' => LembreteEnvio::STATUS_PENDING,
                    ]);
                });
            } catch (QueryException $e) {
                if ($this->isDuplicateReminderConstraint($e)) {
                    continue;
                }

                throw $e;
            }

            if (!$envio) {
                continue;
            }

            $log = NotificacaoPush::query()->create([
                'user_id' => $customer->id,
                'empresa_id' => $empresa->id,
                'lembrete_id' => $reminder->id,
                'tipo' => 'lembrete',
                'titulo' => trim((string) $reminder->titulo),
                'mensagem' => trim((string) $reminder->mensagem),
                'imagem' => null,
                'status' => LembreteEnvio::STATUS_PENDING,
                'erro' => null,
                'enviado' => false,
                'data_envio' => null,
            ]);

            $subscriptions = $subscriptionsByUser->get($customer->id, collect());
            if ($subscriptions->isEmpty()) {
                $stats['total_without_subscription']++;
                $envio->update([
                    'status' => LembreteEnvio::STATUS_NO_SUBSCRIPTION,
                    'erro' => 'Cliente sem subscription push ativa.',
                ]);
                $log->update([
                    'status' => LembreteEnvio::STATUS_NO_SUBSCRIPTION,
                    'erro' => 'Cliente sem subscription push ativa.',
                    'data_envio' => $sentAt,
                ]);
                continue;
            }

            $stats['total_with_subscription']++;
            $result = $this->pushDeliveryService->deliverToSubscriptions(
                $subscriptions,
                trim((string) $reminder->titulo),
                trim((string) $reminder->mensagem),
                [
                    'type' => 'lembrete',
                    'empresa_id' => $empresa->id,
                    'lembrete_id' => $reminder->id,
                    'url' => '/detalhe_do_parceiro.html?id=' . $empresa->id,
                ],
                $auth
            );

            if ($result['sent']) {
                $stats['total_sent']++;
                $envio->update([
                    'status' => LembreteEnvio::STATUS_SENT,
                    'erro' => $result['error'] ?: null,
                ]);
                $log->update([
                    'status' => LembreteEnvio::STATUS_SENT,
                    'erro' => $result['error'] ?: null,
                    'enviado' => true,
                    'data_envio' => $sentAt,
                ]);
            } else {
                $stats['total_failed']++;
                $envio->update([
                    'status' => LembreteEnvio::STATUS_FAILED,
                    'erro' => $result['error'] ?: 'Falha ao entregar push para este cliente.',
                ]);
                $log->update([
                    'status' => LembreteEnvio::STATUS_FAILED,
                    'erro' => $result['error'] ?: 'Falha ao entregar push para este cliente.',
                    'data_envio' => $sentAt,
                ]);
            }
        }

        return [
            'lembrete' => $this->serializeReminder($reminder->fresh()),
            'delivery' => $stats,
        ];
    }

    public function serializeReminder(LembreteAusencia $reminder, array $extra = []): array
    {
        return array_merge([
            'id' => $reminder->id,
            'empresa_id' => $reminder->empresa_id,
            'dias_sem_visita' => $reminder->daysWithoutVisit(),
            'dias_ausencia' => $reminder->daysWithoutVisit(),
            'titulo' => $reminder->titulo,
            'mensagem' => $reminder->mensagem,
            'ativo' => (bool) $reminder->ativo,
            'status' => $reminder->isOperationallyAvailable()
                ? LembreteAusencia::STATUS_AVAILABLE
                : LembreteAusencia::STATUS_INACTIVE,
        ], $extra);
    }

    private function eligibleTargets(Empresa $empresa, LembreteAusencia $reminder)
    {
        $requiredDays = $reminder->daysWithoutVisit();

        return InscricaoEmpresa::query()
            ->with('user')
            ->where('empresa_id', $empresa->id)
            ->get()
            ->map(function (InscricaoEmpresa $inscricao) use ($empresa) {
                $customer = $inscricao->user;
                if (!$customer || !$this->isCustomer($customer)) {
                    return null;
                }

                $reference = $this->resolveLastVisitReference($empresa, $customer, $inscricao);
                if (!$reference) {
                    return null;
                }

                return [
                    'customer' => $customer,
                    'inscricao' => $inscricao,
                    'reference_last_visit_at' => $reference['timestamp'],
                    'days_since_last_visit' => $reference['days_since_last_visit'],
                    'last_visit_source' => $reference['source'],
                ];
            })
            ->filter(function (?array $item) use ($reminder, $requiredDays) {
                if (!$item) {
                    return false;
                }

                if (($item['days_since_last_visit'] ?? 0) < $requiredDays) {
                    return false;
                }

                return !$this->alreadySentForReference(
                    $reminder,
                    $item['customer'],
                    $item['reference_last_visit_at']
                );
            })
            ->values();
    }

    private function resolveLastVisitReference(Empresa $empresa, User $customer, ?InscricaoEmpresa $inscricao = null): ?array
    {
        $candidates = [];

        if ($inscricao?->ultima_visita) {
            $candidates[] = [
                'source' => 'inscricao_ultima_visita',
                'timestamp' => $inscricao->ultima_visita instanceof Carbon
                    ? $inscricao->ultima_visita->copy()
                    : Carbon::parse((string) $inscricao->ultima_visita),
            ];
        }

        if ($movement = $this->maxTableTimestamp('cartoes_fidelidade_movimentos', [
            ['empresa_id', '=', $empresa->id],
            ['user_id', '=', $customer->id],
        ], 'created_at')) {
            $candidates[] = [
                'source' => 'cartao_fidelidade_movimentos',
                'timestamp' => $movement,
            ];
        }

        if ($movement = $this->maxTableTimestamp('bonus_adesao_resgates', [
            ['empresa_id', '=', $empresa->id],
            ['user_id', '=', $customer->id],
        ], 'redeemed_at', 'data_resgate')) {
            $candidates[] = [
                'source' => 'bonus_adesao_resgates',
                'timestamp' => $movement,
            ];
        }

        if ($movement = $this->maxTableTimestamp('promocao_resgates', [
            ['empresa_id', '=', $empresa->id],
            ['user_id', '=', $customer->id],
        ], 'redeemed_at', 'created_at')) {
            $candidates[] = [
                'source' => 'promocao_resgates',
                'timestamp' => $movement,
            ];
        }

        if ($movement = $this->maxTableTimestamp('bonus_aniversario_resgates', [
            ['empresa_id', '=', $empresa->id],
            ['user_id', '=', $customer->id],
        ], 'redeemed_at', 'created_at')) {
            $candidates[] = [
                'source' => 'bonus_aniversario_resgates',
                'timestamp' => $movement,
            ];
        }

        if ($movement = $this->maxTableTimestamp('pontos', [
            ['empresa_id', '=', $empresa->id],
            ['user_id', '=', $customer->id],
        ], 'created_at')) {
            $candidates[] = [
                'source' => 'pontos',
                'timestamp' => $movement,
            ];
        }

        if ($candidates === []) {
            return null;
        }

        usort($candidates, fn (array $a, array $b) => $b['timestamp']->getTimestamp() <=> $a['timestamp']->getTimestamp());
        $selected = $candidates[0];
        $days = $selected['timestamp']->copy()->startOfDay()->diffInDays(now()->startOfDay());

        return [
            'source' => $selected['source'],
            'timestamp' => $selected['timestamp'],
            'days_since_last_visit' => $days,
        ];
    }

    private function maxTableTimestamp(string $table, array $conditions, string ...$columns): ?Carbon
    {
        if (!$this->hasTable($table)) {
            return null;
        }

        foreach ($columns as $column) {
            if (!$this->hasColumn($table, $column)) {
                continue;
            }

            $query = DB::table($table);
            foreach ($conditions as [$field, $operator, $value]) {
                $query->where($field, $operator, $value);
            }

            $value = $query->max($column);
            if ($value) {
                return $value instanceof Carbon ? $value : Carbon::parse((string) $value);
            }
        }

        return null;
    }

    private function alreadySentForReference(LembreteAusencia $reminder, User $customer, Carbon $referenceLastVisitAt): bool
    {
        return LembreteEnvio::query()
            ->where('lembrete_id', $reminder->id)
            ->where('user_id', $customer->id)
            ->where('reference_last_visit_at', $referenceLastVisitAt)
            ->exists();
    }

    private function guardReminderOwnership(Empresa $empresa, LembreteAusencia $reminder): void
    {
        if ($reminder->empresa_id !== $empresa->id) {
            throw new DomainException('Empresa nao pode operar lembrete de outra empresa.');
        }
    }

    private function isDuplicateReminderConstraint(QueryException $e): bool
    {
        $message = Str::lower($e->getMessage());

        return str_contains($message, 'lembrete_envios_lembrete_user_visit_unique')
            || str_contains($message, 'unique constraint failed: lembrete_envios.lembrete_id, lembrete_envios.user_id, lembrete_envios.reference_last_visit_at');
    }

    private function isCustomer(User $user): bool
    {
        $perfil = Str::lower(trim((string) ($user->perfil ?? $user->role ?? $user->tipo ?? '')));

        return in_array($perfil, ['cliente', 'customer'], true);
    }

    private function hasTable(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }

    private function hasColumn(string $table, string $column): bool
    {
        try {
            return Schema::hasTable($table) && Schema::hasColumn($table, $column);
        } catch (\Throwable) {
            return false;
        }
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
