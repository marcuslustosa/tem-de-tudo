<?php

namespace App\Repositories;

use App\Models\CheckIn;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

class CheckInRepository
{
    /**
     * Criar check-in
     */
    public function create(array $data): CheckIn
    {
        return CheckIn::create($data);
    }

    /**
     * Buscar check-ins do usuário (paginado)
     */
    public function getUserCheckIns(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return CheckIn::with('empresa')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Buscar último check-in do usuário na empresa
     */
    public function getLastCheckIn(int $userId, int $empresaId): ?CheckIn
    {
        return CheckIn::where('user_id', $userId)
            ->where('empresa_id', $empresaId)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Verificar se usuário já fez check-in hoje
     */
    public function hasCheckedInToday(int $userId, int $empresaId): bool
    {
        return CheckIn::where('user_id', $userId)
            ->where('empresa_id', $empresaId)
            ->whereDate('created_at', Carbon::today())
            ->exists();
    }

    /**
     * Contar check-ins do usuário
     */
    public function countUserCheckIns(int $userId): int
    {
        return CheckIn::where('user_id', $userId)->count();
    }

    /**
     * Buscar check-in por ID
     */
    public function findById(int $id): ?CheckIn
    {
        return CheckIn::with('empresa')->find($id);
    }

    /**
     * Total de pontos ganhos pelo usuário
     */
    public function getTotalPoints(int $userId): int
    {
        return CheckIn::where('user_id', $userId)->sum('pontos');
    }
}
