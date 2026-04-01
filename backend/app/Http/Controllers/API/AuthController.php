<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Registro de usuario (API legacy compativel com nome/senha e name/password).
     */
    public function register(Request $request)
    {
        $payload = [
            'name' => $request->input('name', $request->input('nome')),
            'email' => $request->input('email'),
            'password' => $request->input('password', $request->input('senha')),
            'cpf' => $request->input('cpf'),
            'telefone' => $request->input('telefone'),
            'data_nascimento' => $request->input('data_nascimento'),
            'perfil' => $request->input('perfil', 'cliente'),
        ];

        $validator = Validator::make($payload, [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'cpf' => 'nullable|string|max:14',
            'telefone' => 'nullable|string|max:20',
            'data_nascimento' => 'nullable|date',
            'perfil' => 'nullable|string|in:cliente,empresa,admin',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados invalidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = User::create([
                'name' => $payload['name'],
                'email' => $payload['email'],
                'password' => Hash::make($payload['password']),
                'cpf' => $payload['cpf'],
                'telefone' => $payload['telefone'],
                'data_nascimento' => $payload['data_nascimento'],
                'perfil' => $payload['perfil'] ?? 'cliente',
                'status' => 'ativo',
                'pontos' => 0,
                'nivel' => 'Bronze',
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Cadastro realizado com sucesso!',
                'token' => $token,
                'user' => $this->serializeUser($user),
            ], 201);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar usuario.',
            ], 500);
        }
    }

    /**
     * Login (compativel com password e senha) com contrato plano.
     */
    public function login(Request $request)
    {
        $payload = [
            'email' => $request->input('email'),
            'password' => $request->input('password', $request->input('senha')),
        ];

        $validator = Validator::make($payload, [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados invalidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = User::where('email', $payload['email'])->first();

            if (!$user || !Hash::check($payload['password'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email ou senha incorretos.',
                ], 401);
            }

            if (($user->status ?? 'ativo') !== 'ativo') {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario inativo. Entre em contato com o suporte.',
                ], 403);
            }

            $updateData = [];
            if (Schema::hasColumn('users', 'ultimo_login')) {
                $updateData['ultimo_login'] = now();
            }
            if (Schema::hasColumn('users', 'ip_ultimo_login')) {
                $updateData['ip_ultimo_login'] = $request->ip();
            }
            if (!empty($updateData)) {
                $user->forceFill($updateData)->save();
            }

            $user->tokens()->delete();
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login realizado com sucesso!',
                'token' => $token,
                'user' => $this->serializeUser($user),
            ], 200);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erro interno ao autenticar.',
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout realizado com sucesso.',
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Nao autenticado.',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'user' => $this->serializeUser($user),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Nao autenticado.',
            ], 401);
        }

        $payload = [
            'name' => $request->input('name', $request->input('nome')),
            'email' => $request->input('email'),
            'telefone' => $request->input('telefone'),
            'data_nascimento' => $request->input('data_nascimento'),
        ];

        $validator = Validator::make($payload, [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'telefone' => 'nullable|string|max:20',
            'data_nascimento' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados invalidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user->fill(array_filter([
                'name' => $payload['name'],
                'email' => $payload['email'],
                'telefone' => $payload['telefone'],
                'data_nascimento' => $payload['data_nascimento'],
            ], static fn ($v) => $v !== null));
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Perfil atualizado com sucesso!',
                'user' => $this->serializeUser($user),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar perfil.',
            ], 500);
        }
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Nao autenticado.',
            ], 401);
        }

        $payload = [
            'current_password' => $request->input('current_password', $request->input('senha_atual')),
            'new_password' => $request->input('password', $request->input('nova_senha')),
            'password_confirmation' => $request->input('password_confirmation', $request->input('nova_senha_confirmacao')),
        ];

        $validator = Validator::make($payload, [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|different:current_password|confirmed',
        ], [], [
            'new_password' => 'password',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados invalidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        if (!Hash::check($payload['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Senha atual incorreta.',
            ], 401);
        }

        try {
            $user->password = Hash::make($payload['new_password']);
            $user->save();
            $user->tokens()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Senha alterada com sucesso. Faca login novamente.',
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao alterar senha.',
            ], 500);
        }
    }

    private function serializeUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'nome' => $user->name,
            'email' => $user->email,
            'cpf' => $user->cpf,
            'telefone' => $user->telefone,
            'data_nascimento' => $user->data_nascimento,
            'perfil' => $user->perfil,
            'pontos' => $user->pontos,
            'nivel' => $user->nivel,
            'foto_url' => $user->foto_url,
            'status' => $user->status,
        ];
    }
}
