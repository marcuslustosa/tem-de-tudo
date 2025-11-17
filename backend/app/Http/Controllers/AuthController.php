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
     * Registro de usuário com múltiplos perfis
     */
    public function register(Request $request)
    {
        Log::info('=== INÍCIO DO REGISTRO ===', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'headers' => $request->headers->all(),
            'data' => $request->all()
        ]);

        // Rate limiting para registro
        $key = 'register-attempts:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            Log::warning('Rate limit excedido para registro', ['ip' => $request->ip(), 'key' => $key]);
            return response()->json([
                'success' => false,
                'message' => 'Muitas tentativas de registro. Tente novamente em ' . RateLimiter::availableIn($key) . ' segundos.'
            ], 429);
        }

        try {
            // Validação inicial do perfil
            $request->validate([
                'perfil' => 'required|string|in:administrador,gestor,recepcionista,usuario_comum',
            ]);

            $perfil = $request->perfil;
            Log::info('Perfil selecionado', ['perfil' => $perfil]);

            // Validações específicas por perfil
            $validationRules = $this->getValidationRulesForPerfil($perfil);

            $validatedData = $request->validate($validationRules);
            Log::info('Validação passou', ['perfil' => $perfil, 'validated' => array_merge($validatedData, ['password' => '[HIDDEN]'])]);

        } catch (ValidationException $e) {
            Log::warning('Erro de validação no registro', [
                'errors' => $e->errors(),
                'data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos. Verifique os campos e tente novamente.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erro inesperado na validação', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro na validação dos dados. Tente novamente.'
            ], 400);
        }

        try {
            DB::beginTransaction();
            Log::info('Transação do banco iniciada para perfil: ' . $perfil);

            // Criar usuário baseado no perfil
            $userData = $this->prepareUserDataForPerfil($perfil, $request);
            $user = User::create($userData);

            Log::info('Usuário criado no banco', ['user_id' => $user->id, 'email' => $user->email, 'perfil' => $perfil]);

            // Gerar Sanctum token
            $token = $user->createToken('auth_token')->plainTextToken;
            Log::info('Token Sanctum gerado', ['user_id' => $user->id]);

            DB::commit();
            Log::info('Transação confirmada com sucesso');

            // Log do evento de auditoria
            $this->logAuditEvent('user_registered', $user->id, $request);

            RateLimiter::clear($key);

            $response = [
                'success' => true,
                'message' => $this->getSuccessMessageForPerfil($perfil),
                'data' => [
                    'user' => array_merge($user->toArray(), ['perfil' => $perfil]),
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'expires_in' => 60 * 60, // 1 hora em segundos
                    'redirect_to' => $this->getRedirectUrlForPerfil($perfil)
                ]
            ];

            Log::info('Registro concluído com sucesso', [
                'user_id' => $user->id,
                'perfil' => $perfil,
                'response_size' => strlen(json_encode($response))
            ]);

            return response()->json($response, 201);

        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            RateLimiter::hit($key, 300);

            Log::error('Erro de banco de dados no registro', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings()
            ]);

            // Verificar se é erro de email duplicado
            if ($e->getCode() == 23000) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este email já está cadastrado. Tente fazer login ou use outro email.'
                ], 422);
            }

            return response()->json([
                'success' => false,
                'message' => 'Erro no banco de dados. Tente novamente em alguns instantes.'
            ], 500);

        } catch (\Exception $e) {
            DB::rollBack();
            RateLimiter::hit($key, 300);

            Log::error('Erro geral no registro', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor. Nossa equipe foi notificada.'
            ], 500);
        }
    }

    /**
     * Login de usuário com redirecionamento baseado no perfil
     */
    public function login(Request $request)
    {
        Log::info('=== INÍCIO DO LOGIN ===', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'headers' => $request->headers->all(),
            'email' => $request->email
        ]);

        // Rate limiting para login
        $key = 'login-attempts:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            Log::warning('Rate limit excedido para login', ['ip' => $request->ip(), 'key' => $key]);
            return response()->json([
                'success' => false,
                'message' => 'Muitas tentativas de login. Tente novamente em ' . RateLimiter::availableIn($key) . ' segundos.'
            ], 429);
        }

        try {
            $validatedData = $request->validate([
                'email' => 'required|string|email',
                'password' => 'required|string',
            ]);

            Log::info('Validação do login passou', ['email' => $request->email]);

        } catch (ValidationException $e) {
            Log::warning('Erro de validação no login', [
                'errors' => $e->errors(),
                'email' => $request->email
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Email ou senha inválidos.',
                'errors' => $e->errors()
            ], 422);
        }

        try {
            // Buscar usuário por email
            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                RateLimiter::hit($key, 300); // 5 minutos
                Log::warning('Tentativa de login falhou', [
                    'email' => $request->email,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Email ou senha incorretos.'
                ], 401);
            }

            // Verificar se usuário está ativo
            if ($user->status !== 'ativo') {
                Log::warning('Tentativa de login com usuário inativo', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'status' => $user->status
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Sua conta está inativa. Entre em contato com o suporte.'
                ], 403);
            }

            // Gerar token Sanctum
            $token = $user->createToken('auth_token')->plainTextToken;

            // Log do evento de auditoria
            $this->logAuditEvent('user_login', $user->id, $request);

            RateLimiter::clear($key);

            $response = [
                'success' => true,
                'message' => 'Login realizado com sucesso!',
                'data' => [
                    'user' => array_merge($user->toArray(), ['perfil' => $this->getPerfilFromRole($user->role)]),
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'expires_in' => 60 * 60, // 1 hora em segundos
                    'redirect_to' => $this->getRedirectUrlForPerfil($this->getPerfilFromRole($user->role))
                ]
            ];

            Log::info('Login realizado com sucesso', [
                'user_id' => $user->id,
                'email' => $user->email,
                'perfil' => $user->perfil,
                'ip' => $request->ip()
            ]);

            return response()->json($response, 200);

        } catch (\Exception $e) {
            RateLimiter::hit($key, 300);

            Log::error('Erro geral no login', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'email' => $request->email,
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor. Tente novamente em alguns instantes.'
            ], 500);
        }
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

    /**
     * Obter regras de validação específicas por perfil
     */
    private function getValidationRulesForPerfil(string $perfil): array
    {
        $baseRules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'terms' => 'required|boolean|accepted',
        ];

        switch ($perfil) {
            case 'administrador':
            case 'gestor':
            case 'recepcionista':
                return array_merge($baseRules, [
                    'telefone' => 'nullable|string|max:20',
                ]);

            case 'usuario_comum':
                return array_merge($baseRules, [
                    'telefone' => 'nullable|string|max:20',
                ]);

            default:
                return $baseRules;
        }
    }

    /**
     * Preparar dados do usuário baseado no perfil
     */
    private function prepareUserDataForPerfil(string $perfil, Request $request): array
    {
        $baseData = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'perfil' => $perfil,
            'status' => 'ativo',
        ];

        switch ($perfil) {
            case 'administrador':
            case 'gestor':
            case 'recepcionista':
                return array_merge($baseData, [
                    'telefone' => $request->telefone,
                ]);

            case 'usuario_comum':
                return array_merge($baseData, [
                    'telefone' => $request->telefone,
                ]);

            default:
                return $baseData;
        }
    }

    /**
     * Criar empresa para usuário do tipo empresa
     */
    private function createEmpresaForUser(User $user, Request $request)
    {
        return \App\Models\Empresa::create([
            'nome' => $request->name,
            'endereco' => $request->endereco,
            'telefone' => $request->telefone,
            'cnpj' => $request->cnpj,
            'owner_id' => $user->id,
            'ativo' => true,
            'points_multiplier' => 1.0,
        ]);
    }

    /**
     * Obter mensagem de sucesso baseada no perfil
     */
    private function getSuccessMessageForPerfil(string $perfil): string
    {
        switch ($perfil) {
            case 'administrador':
                return 'Conta de administrador criada com sucesso! Você tem acesso total ao sistema.';
            case 'gestor':
                return 'Conta de gestor criada com sucesso! Você pode gerenciar operações.';
            case 'recepcionista':
                return 'Conta de recepcionista criada com sucesso! Você pode atender clientes.';
            case 'usuario_comum':
                return 'Conta criada com sucesso! Bem-vindo ao sistema.';
            default:
                return 'Conta criada com sucesso!';
        }
    }

    /**
     * Obter URL de redirecionamento baseada no perfil
     */
    private function getRedirectUrlForPerfil(string $perfil): string
    {
        switch ($perfil) {
            case 'administrador':
                return '/admin/dashboard.html';
            case 'gestor':
                return '/gestor/home.html';
            case 'recepcionista':
                return '/recepcao/index.html';
            case 'usuario_comum':
                return '/app/home.html';
            default:
                return '/app/home.html';
        }
    }

    /**
     * Obter perfil baseado no role do banco
     */
    private function getPerfilFromRole(string $role): string
    {
        $roleToPerfil = [
            'admin' => 'administrador',
            'gestor' => 'gestor',
            'recepcionista' => 'recepcionista',
            'cliente' => 'usuario_comum'
        ];

        return $roleToPerfil[$role] ?? 'usuario_comum';
    }


}
