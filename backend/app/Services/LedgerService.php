<?php

namespace App\Services;

use App\Models\Ledger;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * LedgerService - Serviço centralizado para TODAS operações de pontos.
 * 
 * REGRAS CRÍTICAS:
 * - NUNCA modificar pontos fora deste service
 * - Sempre usar transações DB
 * - Idempotência obrigatória
 * - Auditoria completa
 */
class LedgerService
{
    /**
     * Adiciona pontos (crédito)
     * 
     * @param int $userId
     * @param int $points Valor positivo
     * @param string $description
     * @param array $options [
     *   'company_id' => int,
     *   'type' => string (earn|earn_bonus),
     *   'metadata' => array,
     *   'idempotency_key' => string (opcional, gera automático),
     *   'source' => string (system|admin|api),
     *   'created_by' => int (user_id do criador)
     * ]
     * @return Ledger
     * @throws \Exception
     */
    public function credit(int $userId, int $points, string $description, array $options = []): Ledger
    {
        if ($points <= 0) {
            throw new \InvalidArgumentException('Points must be positive for credit operation');
        }

        return $this->createTransaction(
            userId: $userId,
            points: $points,
            transactionType: $options['type'] ?? Ledger::TYPE_EARN,
            description: $description,
            options: $options
        );
    }

    /**
     * Debita pontos (débito)
     * 
     * @param int $userId
     * @param int $points Valor positivo (será convertido para negativo)
     * @param string $description
     * @param array $options
     * @return Ledger
     * @throws \Exception
     */
    public function debit(int $userId, int $points, string $description, array $options = []): Ledger
    {
        if ($points <= 0) {
            throw new \InvalidArgumentException('Points must be positive for debit operation');
        }

        // Verifica saldo
        $currentBalance = $this->getBalance($userId);
        if ($currentBalance < $points) {
            throw new \Exception("Insufficient balance. Current: {$currentBalance}, Required: {$points}");
        }

        return $this->createTransaction(
            userId: $userId,
            points: -$points,  // Negativo
            transactionType: $options['type'] ?? Ledger::TYPE_REDEEM,
            description: $description,
            options: $options
        );
    }

    /**
     * Reserva pontos (para PDV pendente)
     * 
     * @param int $userId
     * @param int $points
     * @param string $description
     * @param array $options
     * @return Ledger
     */
    public function reserve(int $userId, int $points, string $description, array $options = []): Ledger
    {
        if ($points <= 0) {
            throw new \InvalidArgumentException('Points must be positive for reserve operation');
        }

        $currentBalance = $this->getBalance($userId);
        $reserved = $this->getReservedBalance($userId);
        $available = $currentBalance - $reserved;

        if ($available < $points) {
            throw new \Exception("Insufficient available balance. Available: {$available}, Required: {$points}");
        }

        return $this->createTransaction(
            userId: $userId,
            points: -$points,
            transactionType: Ledger::TYPE_RESERVED,
            description: $description,
            options: $options
        );
    }

    /**
     * Libera reserva (cancela PDV)
     * 
     * @param int $ledgerId ID da transação de reserva
     * @param string $reason
     * @return Ledger
     */
    public function release(int $ledgerId, string $reason): Ledger
    {
        $reservation = Ledger::findOrFail($ledgerId);

        if ($reservation->transaction_type !== Ledger::TYPE_RESERVED) {
            throw new \Exception('Only reserved transactions can be released');
        }

        if ($reservation->reversals()->count() > 0) {
            throw new \Exception('Reservation already released');
        }

        return $this->createTransaction(
            userId: $reservation->user_id,
            points: abs($reservation->points),  // Positivo, devolve
            transactionType: Ledger::TYPE_RELEASED,
            description: "Liberação de reserva: {$reason}",
            options: [
                'related_ledger_id' => $ledgerId,
                'company_id' => $reservation->company_id,
                'source' => 'system',
            ]
        );
    }

    /**
     * Reverte transação (estorno)
     * 
     * @param int $ledgerId ID da transação a reverter
     * @param string $reason Motivo da reversão
     * @param int|null $createdBy User ID do admin que criou
     * @return Ledger
     */
    public function reverse(int $ledgerId, string $reason, ?int $createdBy = null): Ledger
    {
        $original = Ledger::findOrFail($ledgerId);

        if (!$original->canBeReversed()) {
            throw new \Exception('Transaction cannot be reversed');
        }

        return $this->createTransaction(
            userId: $original->user_id,
            points: -$original->points,  // Inverte sinal
            transactionType: Ledger::TYPE_REVERSAL,
            description: "Reversão: {$reason}",
            options: [
                'related_ledger_id' => $ledgerId,
                'company_id' => $original->company_id,
                'source' => 'admin',
                'created_by' => $createdBy,
                'metadata' => [
                    'original_transaction' => $original->id,
                    'original_type' => $original->transaction_type,
                    'reason' => $reason,
                ],
            ]
        );
    }

    /**
     * Ajuste manual (admin)
     * 
     * @param int $userId
     * @param int $points Pode ser positivo ou negativo
     * @param string $reason
     * @param int $adminId
     * @return Ledger
     */
    public function adjust(int $userId, int $points, string $reason, int $adminId): Ledger
    {
        if ($points == 0) {
            throw new \InvalidArgumentException('Adjustment points cannot be zero');
        }

        if ($points < 0) {
            $currentBalance = $this->getBalance($userId);
            if ($currentBalance < abs($points)) {
                throw new \Exception("Insufficient balance for adjustment");
            }
        }

        return $this->createTransaction(
            userId: $userId,
            points: $points,
            transactionType: Ledger::TYPE_ADJUSTMENT,
            description: "Ajuste manual: {$reason}",
            options: [
                'source' => 'admin',
                'created_by' => $adminId,
                'metadata' => ['reason' => $reason],
            ]
        );
    }

    /**
     * Cria transação no ledger (método interno)
     * 
     * @param int $userId
     * @param int $points Pode ser positivo ou negativo
     * @param string $transactionType
     * @param string $description
     * @param array $options
     * @return Ledger
     */
    protected function createTransaction(
        int $userId,
        int $points,
        string $transactionType,
        string $description,
        array $options = []
    ): Ledger {
        return DB::transaction(function () use ($userId, $points, $transactionType, $description, $options) {
            // Gera idempotency key se não fornecida
            $idempotencyKey = $options['idempotency_key'] ?? Str::uuid()->toString();

            // Verifica se já existe (idempotência)
            $existing = Ledger::where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $existing; // Retorna existente, não duplica
            }

            // Lock do usuário para garantir consistência
            $user = User::lockForUpdate()->findOrFail($userId);

            // Calcula saldos
            $balanceBefore = $this->getBalance($userId, lock: false); // Já está no lock
            $balanceAfter = $balanceBefore + $points;

            // Cria registro
            $ledger = Ledger::create([
                'idempotency_key' => $idempotencyKey,
                'user_id' => $userId,
                'company_id' => $options['company_id'] ?? null,
                'transaction_type' => $transactionType,
                'points' => $points,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => $description,
                'metadata' => $options['metadata'] ?? null,
                'related_ledger_id' => $options['related_ledger_id'] ?? null,
                'source' => $options['source'] ?? 'system',
                'created_by' => $options['created_by'] ?? null,
            ]);

            // Atualiza cache de saldo em users (para performance)
            $user->pontos = $balanceAfter;
            $user->save();

            return $ledger;
        });
    }

    /**
     * Retorna saldo atual do usuário
     * 
     * @param int $userId
     * @param bool $lock Se deve usar lockForUpdate
     * @return int
     */
    public function getBalance(int $userId, bool $lock = false): int
    {
        // Opção 1: Busca do cache (rápido)
        if (!$lock) {
            $user = User::find($userId);
            if ($user && $user->pontos !== null) {
                return (int) $user->pontos;
            }
        }

        // Opção 2: Calcula do ledger (fonte da verdade)
        $query = Ledger::where('user_id', $userId)
            ->orderBy('id', 'desc')
            ->limit(1);

        if ($lock) {
            $query->lockForUpdate();
        }

        $lastTransaction = $query->first();

        return $lastTransaction ? (int) $lastTransaction->balance_after : 0;
    }

    /**
     * Retorna saldo reservado (pendente de confirmação)
     * 
     * @param int $userId
     * @return int
     */
    public function getReservedBalance(int $userId): int
    {
        // Soma todas as reservas que não foram liberadas ou revertidas
        $reservations = Ledger::where('user_id', $userId)
            ->where('transaction_type', Ledger::TYPE_RESERVED)
            ->whereDoesntHave('reversals')
            ->sum('points');

        return abs((int) $reservations);
    }

    /**
     * Retorna histórico de transações
     * 
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @return \Illuminate\Support\Collection
     */
    public function getHistory(int $userId, int $limit = 50, int $offset = 0)
    {
        return Ledger::where('user_id', $userId)
            ->with(['company', 'creator'])
            ->orderBy('created_at', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get();
    }

    /**
     * Auditoria: verifica integridade do ledger
     * 
     * @param int $userId
     * @return array [
     *   'valid' => bool,
     *   'calculated_balance' => int,
     *   'cached_balance' => int,
     *   'discrepancy' => int,
     *   'transactions_count' => int
     * ]
     */
    public function audit(int $userId): array
    {
        $transactions = Ledger::where('user_id', $userId)
            ->orderBy('id', 'asc')
            ->get();

        $calculatedBalance = 0;
        $errors = [];

        foreach ($transactions as $tx) {
            $expectedBalanceAfter = $calculatedBalance + $tx->points;

            if ($tx->balance_before !== $calculatedBalance) {
                $errors[] = "Transaction {$tx->id}: balance_before mismatch. Expected {$calculatedBalance}, got {$tx->balance_before}";
            }

            if ($tx->balance_after !== $expectedBalanceAfter) {
                $errors[] = "Transaction {$tx->id}: balance_after mismatch. Expected {$expectedBalanceAfter}, got {$tx->balance_after}";
            }

            $calculatedBalance = $expectedBalanceAfter;
        }

        $user = User::find($userId);
        $cachedBalance = $user ? (int) $user->pontos : 0;

        return [
            'valid' => empty($errors),
            'calculated_balance' => $calculatedBalance,
            'cached_balance' => $cachedBalance,
            'discrepancy' => $calculatedBalance - $cachedBalance,
            'transactions_count' => $transactions->count(),
            'errors' => $errors,
        ];
    }
}
