<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use App\Models\Admin;
use App\Models\AuditLog;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Registro de usuário cliente
     */
    public function register(Request $request)
    {
        // Rate limiting para registro
        $key = 'register-attempts:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return response()->json([
                'success' => false,
                'message' => 'Muitas tentativas de registro. Tente novamente em ' . RateLimiter::availableIn($key) . ' segundos.'
            ], 429);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
        ]);

        try {
            DB::beginTransaction();

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'cliente',
                'pontos' => 100, // Bônus de boas-vindas
                'telefone' => $request->phone,
                'status' => 'ativo'
            ]);

            // Gerar JWT token
            $token = JWTAuth::fromUser($user);

            DB::commit();

            // Log do evento
            $this->logAuditEvent('user_registered', $user->id, $request);

            RateLimiter::clear($key);

            return response()->json([
                'success' => true,
                'message' => 'Usuário criado com sucesso! Você ganhou 100 pontos de boas-vindas!',
                'data' => [
                    'user' => $user,
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'expires_in' => config('jwt.ttl') * 60
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            RateLimiter::hit($key, 300); // 5 minutos
            Log::error('Erro no registro', ['error' => $e->getMessage(), 'request' => $request->all()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar usuário. Tente novamente.'
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais fornecidas estão incorretas.'],
            ]);
        }

        $user = Auth::user();
        $token = JWTAuth::fromUser($user);

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function user(Request $request)
    {
        $user = $request->user();
        
        // Calcular nível baseado nos pontos
        $nivel = 'Bronze';
        if ($user->pontos >= 10000) {
            $nivel = 'Diamante';
        } elseif ($user->pontos >= 5000) {
            $nivel = 'Ouro';
        } elseif ($user->pontos >= 1000) {
            $nivel = 'Prata';
        }
        
        return response()->json([
            'user' => array_merge($user->toArray(), ['nivel' => $nivel])
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout realizado com sucesso'
        ]);
    }
    
    public function addPontos(Request $request)
    {
        $request->validate([
            'pontos' => 'required|integer|min:1',
            'descricao' => 'nullable|string|max:255',
        ]);
        
        $user = $request->user();
        $user->pontos += $request->pontos;
        $user->save();
        
        return response()->json([
            'message' => "Você ganhou {$request->pontos} pontos!",
            'pontos_total' => $user->pontos,
            'nivel' => $this->calculateLevel($user->pontos)
        ]);
    }
    
    private function calculateLevel($pontos)
    {
        if ($pontos >= 10000) return 'Diamante';
        if ($pontos >= 5000) return 'Ouro';
        if ($pontos >= 1000) return 'Prata';
        return 'Bronze';
    }
}
