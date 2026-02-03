<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Registrar novo usuário
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nome' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'senha' => 'required|string|min:6',
            'cpf' => 'nullable|string|max:14',
            'telefone' => 'nullable|string|max:20',
            'data_nascimento' => 'nullable|date',
            'perfil' => 'nullable|string|in:cliente,empresa,admin',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::create([
                'name' => $request->nome,
                'email' => $request->email,
                'password' => Hash::make($request->senha),
                'cpf' => $request->cpf,
                'telefone' => $request->telefone,
                'data_nascimento' => $request->data_nascimento,
                'perfil' => $request->perfil ?? 'cliente',
                'status' => 'ativo',
                'pontos' => 0,
                'nivel' => 'Bronze',
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Cadastro realizado com sucesso!',
                'data' => [
                    'token' => $token,
                    'user' => [
                        'id' => $user->id,
                        'nome' => $user->name,
                        'email' => $user->email,
                        'cpf' => $user->cpf,
                        'telefone' => $user->telefone,
                        'data_nascimento' => $user->data_nascimento,
                        'perfil' => $user->perfil,
                        'pontos' => $user->pontos,
                        'nivel' => $user->nivel,
                        'foto_url' => $user->foto_url,
                    ]
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar usuário: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Login de usuário
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'senha' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->senha, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email ou senha incorretos'
            ], 401);
        }

        if ($user->status !== 'ativo') {
            return response()->json([
                'success' => false,
                'message' => 'Usuário inativo. Entre em contato com o suporte.'
            ], 403);
        }

        // Atualiza último login
        $user->update([
            'ultimo_login' => now(),
            'ip_ultimo_login' => $request->ip(),
        ]);

        // Revoga tokens anteriores
        $user->tokens()->delete();

        // Cria novo token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login realizado com sucesso!',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'nome' => $user->name,
                    'email' => $user->email,
                    'cpf' => $user->cpf,
                    'telefone' => $user->telefone,
                    'data_nascimento' => $user->data_nascimento,
                    'perfil' => $user->perfil,
                    'pontos' => $user->pontos,
                    'nivel' => $user->nivel,
                    'foto_url' => $user->foto_url,
                ]
            ]
        ], 200);
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout realizado com sucesso'
        ]);
    }

    /**
     * Obter usuário autenticado
     */
    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'nome' => $user->name,
                    'email' => $user->email,
                    'cpf' => $user->cpf,
                    'telefone' => $user->telefone,
                    'data_nascimento' => $user->data_nascimento,
                    'perfil' => $user->perfil,
                    'pontos' => $user->pontos,
                    'nivel' => $user->nivel,
                    'foto_url' => $user->foto_url,
                ]
            ]
        ]);
    }

    /**
     * Atualizar perfil
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'nome' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'telefone' => 'nullable|string|max:20',
            'data_nascimento' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user->update([
                'name' => $request->nome ?? $user->name,
                'email' => $request->email ?? $user->email,
                'telefone' => $request->telefone ?? $user->telefone,
                'data_nascimento' => $request->data_nascimento ?? $user->data_nascimento,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Perfil atualizado com sucesso!',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'nome' => $user->name,
                        'email' => $user->email,
                        'cpf' => $user->cpf,
                        'telefone' => $user->telefone,
                        'data_nascimento' => $user->data_nascimento,
                        'perfil' => $user->perfil,
                        'pontos' => $user->pontos,
                        'nivel' => $user->nivel,
                        'foto_url' => $user->foto_url,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar perfil: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Alterar senha
     */
    public function changePassword(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'senha_atual' => 'required',
            'nova_senha' => 'required|string|min:6|different:senha_atual',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        if (!Hash::check($request->senha_atual, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Senha atual incorreta'
            ], 401);
        }

        try {
            $user->update([
                'password' => Hash::make($request->nova_senha)
            ]);

            // Revoga todos os tokens
            $user->tokens()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Senha alterada com sucesso! Faça login novamente.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao alterar senha: ' . $e->getMessage()
            ], 500);
        }
    }
}
