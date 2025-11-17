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
            'telefone' => 'nullable|string|max:20',
            'fcm_token' => 'nullable|string',
            'email_notifications' => 'nullable|boolean',
            'points_notifications' => 'nullable|boolean',
            'security_notifications' => 'nullable|boolean',
            'promotional_notifications' => 'nullable|boolean',
        ]);

        try {
            DB::beginTransaction();

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'cliente',
                'pontos' => 100, // Bônus de boas-vindas
                'telefone' => $request->telefone,
                'fcm_token' => $request->fcm_token,
                'email_notifications' => $request->email_notifications ?? true,
                'points_notifications' => $request->points_notifications ?? true,
                'security_notifications' => $request->security_notifications ?? true,
                'promotional_notifications' => $request->promotional_notifications ?? false,
            ]);

            // Gerar Sanctum token
            $token = $user->createToken('auth_token')->plainTextToken;

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

    /**
     * Login do usuário
     */
    public function login(Request $request)
    {
        // Rate limiting para login
        $key = 'login-attempts:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json([
                'success' => false,
                'message' => 'Muitas tentativas de login. Tente novamente em ' . RateLimiter::availableIn($key) . ' segundos.'
            ], 429);
        }

        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            RateLimiter::hit($key, 300); // 5 minutos
            return response()->json([
                'success' => false,
                'message' => 'Credenciais inválidas'
            ], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;

        // Verificar se usuário está ativo
        if ($user->status !== 'ativo') {
            return response()->json([
                'success' => false,
                'message' => 'Sua conta está inativa. Entre em contato com o suporte.'
            ], 403);
        }

        // Atualizar último login
        $user->update([
            'ultimo_login' => now(),
            'ip_ultimo_login' => $request->ip()
        ]);

        // Calcular nível automaticamente
        $nivel = $this->calcularNivel($user->pontos);

        // Log do evento
        $this->logAuditEvent('user_login', $user->id, $request);

        RateLimiter::clear($key);

        return response()->json([
            'success' => true,
            'message' => 'Login realizado com sucesso!',
            'data' => [
                'user' => array_merge($user->toArray(), ['nivel' => $nivel]),
                'token' => $token,
                'token_type' => 'Bearer',
                'permissions' => $this->getUserPermissions($user->role)
            ]
        ]);
    }

    /**
     * Calcular nível VIP baseado nos pontos
     */
    private function calcularNivel($pontos)
    {
        if ($pontos >= 10000) return 'Diamante';
        if ($pontos >= 5000) return 'Ouro';
        if ($pontos >= 1000) return 'Prata';
        return 'Bronze';
    }

    /**
     * Obter permissões baseadas no role
     */
    private function getUserPermissions($role)
    {
        $permissions = [
            'admin' => [
                'manage_users', 'manage_companies', 'view_reports', 
                'manage_points', 'manage_promotions', 'system_config'
            ],
            'empresa' => [
                'manage_customers', 'create_promotions', 'view_sales', 
                'generate_qrcode', 'manage_discounts'
            ],
            'cliente' => [
                'earn_points', 'redeem_discounts', 'view_history', 
                'qr_checkin', 'view_promotions'
            ]
        ];

        return $permissions[$role] ?? $permissions['cliente'];
    }

    public function user(Request $request)
    {
        $user = $request->user();

        // Calcular nível baseado nos pontos
        $nivel = $this->calcularNivel($user->pontos);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => array_merge($user->toArray(), ['nivel' => $nivel])
            ]
        ]);
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Logout realizado com sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao fazer logout'
            ], 500);
        }
    }
    
    public function addPontos(Request $request)
    {
        $request->validate([
            'pontos' => 'required|integer|min:1',
            'descricao' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $user->increment('pontos', $request->pontos);

        // Registrar no histórico
        \App\Models\Ponto::create([
            'user_id' => $user->id,
            'pontos' => $request->pontos,
            'descricao' => $request->descricao ?? 'Pontos adicionados manualmente',
            'tipo' => 'earn'
        ]);

        return response()->json([
            'success' => true,
            'message' => "Você ganhou {$request->pontos} pontos!",
            'data' => [
                'pontos_total' => $user->fresh()->pontos,
                'nivel' => $this->calcularNivel($user->pontos)
            ]
        ]);
    }

    /**
     * Log de eventos para auditoria
     */
    private function logAuditEvent(string $event, int $userId, $request = null): void
    {
        try {
            // Log simples - em produção, você pode usar um sistema mais robusto
            Log::info("AUDIT: {$event}", [
                'user_id' => $userId,
                'ip' => $request ? $request->ip() : null,
                'user_agent' => $request ? $request->userAgent() : null,
                'timestamp' => now()
            ]);
        } catch (\Exception $e) {
            // Falha silenciosa para não quebrar o fluxo principal
            Log::error("Erro no log de auditoria: " . $e->getMessage());
        }
    }
}
