<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\DTOs\Auth\RegisterDTO;
use App\DTOs\Auth\LoginDTO;
use App\DTOs\User\UpdateProfileDTO;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    /**
     * Registrar novo usuário
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $dto = RegisterDTO::fromArray($request->validated());
            $result = $this->authService->register($dto);

            return response()->json([
                'success' => true,
                'message' => 'Usuário cadastrado com sucesso!',
                'data' => [
                    'token' => $result['token'],
                    'user' => new UserResource($result['user'])
                ]
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Login do usuário
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $dto = LoginDTO::fromArray($request->validated());
            $result = $this->authService->login($dto);

            return response()->json([
                'success' => true,
                'message' => 'Login realizado com sucesso!',
                'data' => [
                    'token' => $result['token'],
                    'user' => new UserResource($result['user'])
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 401);
        }
    }

    /**
     * Logout do usuário
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $this->authService->logout($request->user());

            return response()->json([
                'success' => true,
                'message' => 'Logout realizado com sucesso!'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao fazer logout'
            ], 500);
        }
    }

    /**
     * Dados do usuário autenticado
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'user' => new UserResource($request->user())
            ]
        ]);
    }

    /**
     * Atualizar perfil do usuário
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $request->validate([
            'nome' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255',
            'telefone' => 'sometimes|string|max:20',
            'data_nascimento' => 'sometimes|date',
        ]);

        try {
            $dto = UpdateProfileDTO::fromArray($request->all());
            $user = $this->authService->updateProfile($request->user(), $dto);

            return response()->json([
                'success' => true,
                'message' => 'Perfil atualizado com sucesso!',
                'data' => [
                    'user' => new UserResource($user)
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Alterar senha do usuário
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'senha_atual' => 'required|string',
            'nova_senha' => 'required|string|min:6',
        ]);

        try {
            $this->authService->changePassword(
                $request->user(),
                $request->senha_atual,
                $request->nova_senha
            );

            return response()->json([
                'success' => true,
                'message' => 'Senha alterada com sucesso!'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
