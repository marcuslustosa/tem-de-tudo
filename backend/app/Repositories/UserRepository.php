<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserRepository
{
    /**
     * Criar novo usuário
     */
    public function create(array $data): User
    {
        $data['password'] = Hash::make($data['password']);
        return User::create($data);
    }

    /**
     * Buscar usuário por email
     */
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * Buscar usuário por ID
     */
    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    /**
     * Atualizar usuário
     */
    public function update(User $user, array $data): bool
    {
        return $user->update($data);
    }

    /**
     * Adicionar pontos ao usuário
     */
    public function addPoints(User $user, int $points): bool
    {
        $user->pontos = ($user->pontos ?? 0) + $points;
        return $user->save();
    }

    /**
     * Deduzir pontos do usuário
     */
    public function deductPoints(User $user, int $points): bool
    {
        if (($user->pontos ?? 0) < $points) {
            return false;
        }
        $user->pontos -= $points;
        return $user->save();
    }

    /**
     * Atualizar senha
     */
    public function updatePassword(User $user, string $newPassword): bool
    {
        return $user->update(['password' => Hash::make($newPassword)]);
    }

    /**
     * Verificar se email existe
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $query = User::where('email', $email);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->exists();
    }

    /**
     * Atualizar último login
     */
    public function updateLastLogin(User $user): bool
    {
        return $user->update(['ultimo_login' => now()]);
    }
}
