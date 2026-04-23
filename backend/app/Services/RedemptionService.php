<?php

namespace App\Services;

use App\Models\RedemptionIntent;
use App\Models\User;
use App\Services\LedgerService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RedemptionService
{
    protected $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    /**
     * Solicita resgate no PDV (cria intenção e reserva pontos)
     * 
     * @param int $userId
     * @param int $companyId
     * @param int $points
     * @param array $options ['type', 'metadata', 'pdv_operator_id', 'pdv_terminal_id', 'expires_minutes']
     * @return RedemptionIntent
     */
    public function requestRedemption(int $userId, int $companyId, int $points, array $options = []): RedemptionIntent
    {
        return DB::transaction(function () use ($userId, $companyId, $points, $options) {
            // 1. Verifica saldo disponível
            $availableBalance = $this->ledgerService->getBalance($userId);
            $reservedBalance = $this->ledgerService->getReservedBalance($userId);
            $actualAvailable = $availableBalance - $reservedBalance;

            if ($actualAvailable < $points) {
                throw new \Exception(
                    "Saldo insuficiente. Disponível: {$actualAvailable} pontos (saldo total: {$availableBalance}, reservado: {$reservedBalance})"
                );
            }

            // 2. Cria intenção de resgate
            $expiresMinutes = $options['expires_minutes'] ?? 15; // 15 min default
            $metadata = $options['metadata'] ?? [];

            if (!is_array($metadata)) {
                $metadata = ['raw' => $metadata];
            }

            foreach (['pdv_operator_id', 'pdv_terminal_id', 'notes'] as $key) {
                if (isset($options[$key])) {
                    $metadata[$key] = $options[$key];
                }
            }

            $intent = RedemptionIntent::create([
                'user_id' => $userId,
                'company_id' => $companyId,
                'points_requested' => $points,
                'status' => RedemptionIntent::STATUS_PENDING,
                'redemption_type' => $options['type'] ?? RedemptionIntent::TYPE_PRODUCT,
                'metadata' => !empty($metadata) ? $metadata : null,
                'pdv_operator_id' => $options['pdv_operator_id'] ?? null,
                'pdv_terminal_id' => $options['pdv_terminal_id'] ?? null,
                'requested_at' => now(),
                'expires_at' => now()->addMinutes($expiresMinutes),
            ]);

            // 3. Reserva pontos no ledger
            $reservedLedger = $this->ledgerService->reserve(
                userId: $userId,
                points: $points,
                description: "Reserva para resgate - Intent #{$intent->intent_id}",
                options: [
                    'idempotency_key' => "redemption:reserve:{$intent->intent_id}",
                    'company_id' => $companyId,
                    'metadata' => [
                        'redemption_intent_id' => $intent->id,
                        'intent_uuid' => $intent->intent_id,
                        'type' => $intent->redemption_type,
                        'expires_at' => $intent->expires_at->toIso8601String(),
                    ],
                ]
            );

            // 4. Atualiza intent com ledger_id e status
            $intent->update([
                'reserved_ledger_id' => $reservedLedger->id,
                'status' => RedemptionIntent::STATUS_RESERVED,
                'reserved_at' => now(),
            ]);

            return $intent->fresh();
        });
    }

    /**
     * Confirma resgate (debita pontos definitivamente)
     * 
     * @param string $intentId
     * @param int|null $finalPoints - Se null, usa points_requested
     * @return RedemptionIntent
     */
    public function confirmRedemption(string $intentId, ?int $finalPoints = null): RedemptionIntent
    {
        return DB::transaction(function () use ($intentId, $finalPoints) {
            $intent = RedemptionIntent::where('intent_id', $intentId)->firstOrFail();

            if (!$intent->canBeConfirmed()) {
                throw new \Exception(
                    "Resgate não pode ser confirmado. Status: {$intent->status}, Expirado: " . 
                    ($intent->isExpired() ? 'Sim' : 'Não')
                );
            }

            $pointsToConfirm = $finalPoints ?? $intent->points_requested;

            // 1. Libera a reserva
            $this->ledgerService->release(
                ledgerId: $intent->reserved_ledger_id,
                reason: "Reserva liberada para confirmação - Intent #{$intent->intent_id}"
            );

            // 2. Debita pontos definitivamente
            $confirmedLedger = $this->ledgerService->debit(
                userId: $intent->user_id,
                points: $pointsToConfirm,
                description: "Resgate confirmado - Intent #{$intent->intent_id}",
                options: [
                    'idempotency_key' => "redemption:confirm:{$intent->intent_id}",
                    'company_id' => $intent->company_id,
                    'metadata' => [
                        'redemption_intent_id' => $intent->id,
                        'intent_uuid' => $intent->intent_id,
                        'type' => $intent->redemption_type,
                        'original_reservation' => $intent->reserved_ledger_id,
                        'pdv_operator_id' => $intent->pdv_operator_id,
                        'pdv_terminal_id' => $intent->pdv_terminal_id,
                    ],
                ]
            );

            // 3. Atualiza intent
            $intent->update([
                'confirmed_ledger_id' => $confirmedLedger->id,
                'points_confirmed' => $pointsToConfirm,
                'status' => RedemptionIntent::STATUS_CONFIRMED,
                'confirmed_at' => now(),
            ]);

            return $intent->fresh();
        });
    }

    /**
     * Cancela resgate (libera reserva)
     * 
     * @param string $intentId
     * @param string $reason
     * @return RedemptionIntent
     */
    public function cancelRedemption(string $intentId, string $reason = 'Cancelado pelo operador'): RedemptionIntent
    {
        return DB::transaction(function () use ($intentId, $reason) {
            $intent = RedemptionIntent::where('intent_id', $intentId)->firstOrFail();

            if (!$intent->canBeCanceled()) {
                throw new \Exception("Resgate não pode ser cancelado. Status atual: {$intent->status}");
            }

            // Libera reserva se existir
            if ($intent->reserved_ledger_id) {
                $this->ledgerService->release(
                    ledgerId: $intent->reserved_ledger_id,
                    reason: "Resgate cancelado - Intent #{$intent->intent_id}: {$reason}"
                );
            }

            // Atualiza intent
            $intent->update([
                'status' => RedemptionIntent::STATUS_CANCELED,
                'canceled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            return $intent->fresh();
        });
    }

    /**
     * Estorna resgate confirmado (devolve pontos)
     * 
     * @param string $intentId
     * @param string $reason
     * @param int $reversedBy - User ID do admin que autorizou
     * @return RedemptionIntent
     */
    public function reverseRedemption(string $intentId, string $reason, int $reversedBy): RedemptionIntent
    {
        return DB::transaction(function () use ($intentId, $reason, $reversedBy) {
            $intent = RedemptionIntent::where('intent_id', $intentId)->firstOrFail();

            if (!$intent->canBeReversed()) {
                throw new \Exception("Resgate não pode ser estornado. Status atual: {$intent->status}");
            }

            if (!$intent->confirmed_ledger_id) {
                throw new \Exception("Resgate não possui ledger confirmado");
            }

            // Reverte transação no ledger
            $reversalLedger = $this->ledgerService->reverse(
                ledgerId: $intent->confirmed_ledger_id,
                reason: "Estorno de resgate - Intent #{$intent->intent_id}: {$reason}",
                createdBy: $reversedBy
            );

            // Atualiza intent
            $intent->update([
                'reversal_ledger_id' => $reversalLedger->id,
                'status' => RedemptionIntent::STATUS_REVERSED,
                'reversed_at' => now(),
                'reversal_reason' => $reason,
                'reversed_by' => $reversedBy,
            ]);

            return $intent->fresh();
        });
    }

    /**
     * Processa reservas expiradas (libera pontos automaticamente)
     * 
     * Deve rodar via cron a cada 5-10 minutos
     */
    public function processExpiredReservations(): array
    {
        $expired = RedemptionIntent::expired()->get();
        $processed = 0;

        foreach ($expired as $intent) {
            try {
                DB::transaction(function () use ($intent) {
                    if ($intent->reserved_ledger_id) {
                        $this->ledgerService->release(
                            ledgerId: $intent->reserved_ledger_id,
                            reason: "Reserva expirada automaticamente - Intent #{$intent->intent_id}"
                        );
                    }

                    $intent->update([
                        'status' => RedemptionIntent::STATUS_EXPIRED,
                        'canceled_at' => now(),
                        'cancellation_reason' => 'Reserva expirou automaticamente',
                    ]);
                });
                $processed++;
            } catch (\Exception $e) {
                // Log erro mas continua processando
                \Log::error("Erro ao processar reserva expirada #{$intent->intent_id}: " . $e->getMessage());
            }
        }

        return [
            'total_expired' => $expired->count(),
            'processed' => $processed,
        ];
    }

    /**
     * Busca intent por ID
     */
    public function getIntent(string $intentId): ?RedemptionIntent
    {
        return RedemptionIntent::where('intent_id', $intentId)
            ->with(['user', 'company', 'pdvOperator', 'reservedLedger', 'confirmedLedger'])
            ->first();
    }

    /**
     * Histórico de resgates do usuário
     */
    public function getUserRedemptions(
        int $userId,
        int $limit = 20,
        ?int $companyId = null
    ): \Illuminate\Database\Eloquent\Collection
    {
        return RedemptionIntent::where('user_id', $userId)
            ->when($companyId !== null, fn ($query) => $query->where('company_id', $companyId))
            ->with(['company', 'confirmedLedger'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Resgates pendentes/reservados da empresa (PDV)
     */
    public function getCompanyPendingRedemptions(int $companyId): \Illuminate\Database\Eloquent\Collection
    {
        return RedemptionIntent::whereIn('status', [
                RedemptionIntent::STATUS_PENDING, 
                RedemptionIntent::STATUS_RESERVED
            ])
            ->where('company_id', $companyId)
            ->where('expires_at', '>', now())
            ->with(['user', 'pdvOperator'])
            ->latest()
            ->get();
    }
}

