<?php

namespace App\Services;

use App\Models\CartaoFidelidade;
use App\Models\CartaoFidelidadeMovimento;
use App\Models\CartaoFidelidadeProgresso;
use App\Models\Empresa;
use App\Models\InscricaoEmpresa;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CartaoFidelidadeService
{
    private const DUPLICATE_GUARD_SECONDS = 15;

    public function companyCards(Empresa $empresa)
    {
        return CartaoFidelidade::query()
            ->where('empresa_id', $empresa->id)
            ->orderByDesc('ativo')
            ->orderByDesc('created_at');
    }

    public function latestCompanyCard(Empresa $empresa): ?CartaoFidelidade
    {
        return $this->companyCards($empresa)->first();
    }

    public function activeCompanyCard(Empresa $empresa): ?CartaoFidelidade
    {
        return $this->companyCards($empresa)
            ->get()
            ->first(fn (CartaoFidelidade $card) => $card->isOperationallyAvailable());
    }

    public function saveCard(Empresa $empresa, array $payload, ?CartaoFidelidade $card = null): CartaoFidelidade
    {
        $titulo = array_key_exists('titulo', $payload)
            ? trim((string) $payload['titulo'])
            : trim((string) ($card?->titulo ?? ''));

        $descricao = $this->normalizeNullableString($payload['descricao'] ?? $card?->descricao ?? null);
        $regraGanho = $this->normalizeNullableString($payload['regra_ganho'] ?? $card?->regra_ganho ?? null)
            ?: 'Ganhe 1 ponto a cada visita.';
        $pontosPorVisita = max(1, (int) ($payload['pontos_por_visita'] ?? $card?->pontos_por_visita ?? 1));
        $pontosNecessarios = max(1, (int) ($payload['pontos_necessarios'] ?? $card?->pontos_necessarios ?? $card?->meta_pontos ?? 1));
        $recompensaDescricao = $this->normalizeNullableString(
            $payload['recompensa_descricao'] ?? $card?->recompensa_descricao ?? $card?->recompensa ?? null
        );
        $ativo = array_key_exists('ativo', $payload)
            ? (bool) $payload['ativo']
            : (bool) ($card?->ativo ?? true);
        $dataExpiracao = $this->normalizeNullableString($payload['data_expiracao'] ?? $card?->data_expiracao ?? null);

        $data = [
            'empresa_id' => $empresa->id,
            'titulo' => $titulo,
            'descricao' => $descricao,
            'meta_pontos' => $pontosNecessarios,
            'recompensa' => $recompensaDescricao,
            'ativo' => $ativo,
        ];

        if (Schema::hasColumn('cartoes_fidelidade', 'regra_ganho')) {
            $data['regra_ganho'] = $regraGanho;
        }
        if (Schema::hasColumn('cartoes_fidelidade', 'pontos_por_visita')) {
            $data['pontos_por_visita'] = $pontosPorVisita;
        }
        if (Schema::hasColumn('cartoes_fidelidade', 'pontos_necessarios')) {
            $data['pontos_necessarios'] = $pontosNecessarios;
        }
        if (Schema::hasColumn('cartoes_fidelidade', 'recompensa_descricao')) {
            $data['recompensa_descricao'] = $recompensaDescricao;
        }
        if (Schema::hasColumn('cartoes_fidelidade', 'data_expiracao')) {
            $data['data_expiracao'] = $dataExpiracao;
        }
        if (Schema::hasColumn('cartoes_fidelidade', 'validade')) {
            $data['validade'] = $dataExpiracao;
        }

        return DB::transaction(function () use ($empresa, $card, $data) {
            if ($data['ativo']) {
                CartaoFidelidade::query()
                    ->where('empresa_id', $empresa->id)
                    ->when($card?->id, fn ($query) => $query->where('id', '!=', $card->id))
                    ->update(['ativo' => \Illuminate\Support\Facades\DB::raw('false')]);
            }

            if ($card) {
                $card->update($data);

                return $card->refresh();
            }

            return CartaoFidelidade::query()->create($data);
        });
    }

    public function customerCardSnapshot(Empresa $empresa, User $customer): array
    {
        $inscricao = $this->findInscricao($empresa, $customer);
        $latestCard = $this->latestCompanyCard($empresa);
        $activeCard = $this->activeCompanyCard($empresa);

        if (!$latestCard) {
            return [
                'status' => 'unavailable',
                'message' => 'Nenhum cartao fidelidade ativo no momento.',
                'card' => null,
                'progress' => null,
                'history_summary' => [],
                'can_add_point' => false,
                'can_redeem' => false,
            ];
        }

        $card = $activeCard ?: $latestCard;
        if (!$inscricao) {
            return [
                'status' => 'not_linked',
                'message' => 'Cliente nao esta vinculado a esta empresa.',
                'card' => $this->serializeCard($card),
                'progress' => null,
                'history_summary' => [],
                'can_add_point' => false,
                'can_redeem' => false,
            ];
        }

        if (!$activeCard || !$activeCard->isOperationallyAvailable()) {
            return [
                'status' => $card->isExpired() ? 'expired' : 'inactive',
                'message' => $card->isExpired()
                    ? 'Cartao fidelidade expirado.'
                    : 'Cartao fidelidade inativo no momento.',
                'card' => $this->serializeCard($card),
                'progress' => $this->serializeProgress($card, $customer, false),
                'history_summary' => $this->historySummary($card, $customer),
                'can_add_point' => false,
                'can_redeem' => false,
            ];
        }

        $progress = $this->serializeProgress($activeCard, $customer, true);

        return [
            'status' => $progress['reward_available'] ? 'reward_available' : 'available',
            'message' => $progress['reward_available']
                ? 'Cliente ja pode resgatar a recompensa do cartao fidelidade.'
                : 'Cartao fidelidade disponivel para pontuacao por visita.',
            'card' => $this->serializeCard($activeCard),
            'progress' => $progress,
            'history_summary' => $this->historySummary($activeCard, $customer),
            'can_add_point' => true,
            'can_redeem' => (bool) $progress['reward_available'],
        ];
    }

    public function addVisitPoint(Empresa $empresa, CartaoFidelidade $card, User $customer, User $actor): array
    {
        $this->guardCompanyCardOwnership($empresa, $card);
        $this->guardCardAvailable($card);

        $inscricao = $this->findInscricao($empresa, $customer);
        if (!$inscricao) {
            throw new DomainException('Cliente nao esta vinculado a esta empresa.');
        }

        $timestamp = now();
        $pointsPerVisit = max(1, (int) $card->pontos_por_visita);

        DB::transaction(function () use ($card, $empresa, $customer, $actor, $inscricao, $timestamp, $pointsPerVisit): void {
            $recentEarned = CartaoFidelidadeMovimento::query()
                ->where('cartao_fidelidade_id', $card->id)
                ->where('empresa_id', $empresa->id)
                ->where('user_id', $customer->id)
                ->where('tipo', CartaoFidelidadeMovimento::TYPE_EARNED)
                ->where('created_at', '>=', $timestamp->copy()->subSeconds(self::DUPLICATE_GUARD_SECONDS))
                ->exists();

            if ($recentEarned) {
                throw new DomainException('Este cliente acabou de receber um ponto. Aguarde alguns segundos antes de repetir a operacao.');
            }

            $progress = CartaoFidelidadeProgresso::query()
                ->where('user_id', $customer->id)
                ->where('cartao_fidelidade_id', $card->id)
                ->lockForUpdate()
                ->first();

            if (!$progress) {
                $progress = CartaoFidelidadeProgresso::query()->create([
                    'user_id' => $customer->id,
                    'cartao_fidelidade_id' => $card->id,
                    'pontos_atuais' => 0,
                    'vezes_resgatado' => 0,
                    'ultimo_ponto' => null,
                ]);
                $progress->refresh();
            }

            $progress->update([
                'pontos_atuais' => (int) $progress->pontos_atuais + $pointsPerVisit,
                'ultimo_ponto' => $timestamp,
            ]);

            CartaoFidelidadeMovimento::query()->create([
                'cartao_fidelidade_id' => $card->id,
                'empresa_id' => $empresa->id,
                'user_id' => $customer->id,
                'pontos' => $pointsPerVisit,
                'tipo' => CartaoFidelidadeMovimento::TYPE_EARNED,
                'descricao' => sprintf('Visita validada pela empresa: +%d ponto(s).', $pointsPerVisit),
                'created_by' => $actor->id,
            ]);

            $inscricao->update([
                'ultima_visita' => $timestamp,
            ]);
        });

        return $this->customerCardSnapshot($empresa, $customer);
    }

    public function redeemReward(Empresa $empresa, CartaoFidelidade $card, User $customer, User $actor): array
    {
        $this->guardCompanyCardOwnership($empresa, $card);
        $this->guardCardAvailable($card);

        $inscricao = $this->findInscricao($empresa, $customer);
        if (!$inscricao) {
            throw new DomainException('Cliente nao esta vinculado a esta empresa.');
        }

        $timestamp = now();
        $requiredPoints = max(1, (int) $card->pontos_necessarios);

        DB::transaction(function () use ($card, $empresa, $customer, $actor, $inscricao, $timestamp, $requiredPoints): void {
            $progress = CartaoFidelidadeProgresso::query()
                ->where('user_id', $customer->id)
                ->where('cartao_fidelidade_id', $card->id)
                ->lockForUpdate()
                ->first();

            if (!$progress || (int) $progress->pontos_atuais < $requiredPoints) {
                throw new DomainException('Cliente ainda nao possui pontos suficientes para resgatar esta recompensa.');
            }

            $progress->update([
                'pontos_atuais' => (int) $progress->pontos_atuais - $requiredPoints,
                'vezes_resgatado' => (int) $progress->vezes_resgatado + 1,
            ]);

            CartaoFidelidadeMovimento::query()->create([
                'cartao_fidelidade_id' => $card->id,
                'empresa_id' => $empresa->id,
                'user_id' => $customer->id,
                'pontos' => $requiredPoints,
                'tipo' => CartaoFidelidadeMovimento::TYPE_REDEEMED,
                'descricao' => sprintf('Recompensa resgatada no estabelecimento: -%d ponto(s).', $requiredPoints),
                'created_by' => $actor->id,
            ]);

            $inscricao->update([
                'ultima_visita' => $timestamp,
            ]);
        });

        return $this->customerCardSnapshot($empresa, $customer);
    }

    public function serializeCard(CartaoFidelidade $card, array $extra = []): array
    {
        $requiredPoints = max(1, (int) ($card->pontos_necessarios ?? $card->meta_pontos ?? 1));

        return array_merge([
            'id' => $card->id,
            'empresa_id' => $card->empresa_id,
            'titulo' => $card->titulo,
            'descricao' => $card->descricao,
            'regra_ganho' => $card->regra_ganho ?: 'Ganhe 1 ponto a cada visita.',
            'pontos_por_visita' => max(1, (int) ($card->pontos_por_visita ?? 1)),
            'pontos_necessarios' => $requiredPoints,
            'recompensa_descricao' => $card->recompensa_descricao ?: $card->recompensa,
            'data_expiracao' => optional($card->data_expiracao)->toDateString(),
            'ativo' => (bool) $card->ativo,
            'status' => $card->isExpired()
                ? 'expired'
                : ((bool) $card->ativo ? 'available' : 'inactive'),
        ], $extra);
    }

    private function serializeProgress(CartaoFidelidade $card, User $customer, bool $ensureProgress): array
    {
        $progress = CartaoFidelidadeProgresso::query()
            ->where('user_id', $customer->id)
            ->where('cartao_fidelidade_id', $card->id)
            ->first();

        if (!$progress && $ensureProgress) {
            $progress = new CartaoFidelidadeProgresso([
                'user_id' => $customer->id,
                'cartao_fidelidade_id' => $card->id,
                'pontos_atuais' => 0,
                'vezes_resgatado' => 0,
                'ultimo_ponto' => null,
            ]);
        }

        $requiredPoints = max(1, (int) ($card->pontos_necessarios ?? $card->meta_pontos ?? 1));
        $currentPoints = max(0, (int) ($progress?->pontos_atuais ?? 0));
        $percentage = (int) min(100, round(($currentPoints / $requiredPoints) * 100));

        return [
            'current_points' => $currentPoints,
            'required_points' => $requiredPoints,
            'points_per_visit' => max(1, (int) ($card->pontos_por_visita ?? 1)),
            'reward_available' => $currentPoints >= $requiredPoints,
            'times_redeemed' => (int) ($progress?->vezes_resgatado ?? 0),
            'last_point_at' => optional($progress?->ultimo_ponto)->toIso8601String(),
            'percentage' => $percentage,
            'progress_label' => sprintf('%d de %d pontos', $currentPoints, $requiredPoints),
        ];
    }

    private function historySummary(CartaoFidelidade $card, User $customer): array
    {
        return CartaoFidelidadeMovimento::query()
            ->where('cartao_fidelidade_id', $card->id)
            ->where('user_id', $customer->id)
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(function (CartaoFidelidadeMovimento $movement) {
                return [
                    'id' => $movement->id,
                    'tipo' => $movement->tipo,
                    'pontos' => (int) $movement->pontos,
                    'descricao' => $movement->descricao,
                    'created_at' => optional($movement->created_at)->toIso8601String(),
                ];
            })
            ->values()
            ->all();
    }

    private function findInscricao(Empresa $empresa, User $customer): ?InscricaoEmpresa
    {
        return InscricaoEmpresa::query()
            ->where('empresa_id', $empresa->id)
            ->where('user_id', $customer->id)
            ->first();
    }

    private function guardCompanyCardOwnership(Empresa $empresa, CartaoFidelidade $card): void
    {
        if ($card->empresa_id !== $empresa->id) {
            throw new DomainException('Empresa nao pode operar cartao fidelidade de outra empresa.');
        }
    }

    private function guardCardAvailable(CartaoFidelidade $card): void
    {
        if (!$card->isOperationallyAvailable()) {
            throw new DomainException($card->isExpired()
                ? 'Cartao fidelidade expirado.'
                : 'Cartao fidelidade inativo.');
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
