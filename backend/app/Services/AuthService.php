<?php

namespace App\Services;

use App\DTOs\Auth\RegisterDTO;
use App\DTOs\Auth\LoginDTO;
use App\DTOs\User\UpdateProfileDTO;
use App\Repositories\UserRepository;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Exception;

class AuthService
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    /**
     * Registrar novo usuário
     * 
     * @throws Exception
     */
    public function register(RegisterDTO $dto): array
    {
        try {
            // Verifica se email já existe
            if ($this->userRepository->emailExists($dto->email)) {
                throw new Exception('Email já cadastrado');
            }

            // Cria usuário
            $user = $this->userRepository->create($dto->toArray());

            // Gera token de autenticação
            $token = $user->createToken('auth_token')->plainTextToken;

            Log::info('Novo usuário registrado', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return [
                'user' => $user,
                'token' => $token
            ];

        } catch (Exception $e) {
            Log::error('Erro ao registrar usuário', [
                'error' => $e->getMessage(),
                'email' => $dto->email
            ]);
            throw $e;
        }
    }

    /**
     * Autenticar usuário
     * 
     * @throws Exception
     */
    public function login(LoginDTO $dto): array
    {
        try {
            // Busca usuário
            $user = $this->userRepository->findByEmail($dto->email);

            if (!$user || !Hash::check($dto->password, $user->password)) {
                throw new Exception('Email ou senha incorretos');
            }

            // Verifica se usuário está ativo
            if (!$user->ativo) {
                throw new Exception('Usuário inativo');
            }

            // Atualiza último login
            $this->userRepository->updateLastLogin($user);

            // Gera token
            $token = $user->createToken('auth_token')->plainTextToken;

            Log::info('Usuário autenticado', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return [
                'user' => $user,
                'token' => $token
            ];

        } catch (Exception $e) {
            Log::warning('Tentativa de login falhou', [
                'email' => $dto->email,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Logout do usuário
     */
    public function logout(User $user): bool
    {
        try {
            // Revoga token atual
            $user->currentAccessToken()->delete();

            Log::info('Usuário deslogado', ['user_id' => $user->id]);

            return true;

        } catch (Exception $e) {
            Log::error('Erro ao fazer logout', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Atualizar perfil do usuário
     * 
     * @throws Exception
     */
    public function updateProfile(User $user, UpdateProfileDTO $dto): User
    {
        try {
            $data = $dto->toArray();

            // Se está mudando email, verifica se já existe
            if (isset($data['email']) && $data['email'] !== $user->email) {
                if ($this->userRepository->emailExists($data['email'], $user->id)) {
                    throw new Exception('Email já cadastrado por outro usuário');
                }
            }

            $this->userRepository->update($user, $data);

            Log::info('Perfil atualizado', [
                'user_id' => $user->id,
                'campos' => array_keys($data)
            ]);

            return $user->fresh();

        } catch (Exception $e) {
            Log::error('Erro ao atualizar perfil', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Alterar senha do usuário
     * 
     * @throws Exception
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): bool
    {
        try {
            // Verifica senha atual
            if (!Hash::check($currentPassword, $user->password)) {
                throw new Exception('Senha atual incorreta');
            }

            // Atualiza senha
            $this->userRepository->updatePassword($user, $newPassword);

            Log::info('Senha alterada', ['user_id' => $user->id]);

            return true;

        } catch (Exception $e) {
            Log::warning('Erro ao alterar senha', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
