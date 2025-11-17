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
                'role' => 'required|string|in:cliente,empresa,funcionario',
            ]);

            $role = $request->role;
            Log::info('Perfil selecionado', ['role' => $role]);

            // Validações específicas por perfil
            $validationRules = $this->getValidationRulesForRole($role);

            $validatedData = $request->validate($validationRules);
            Log::info('Validação passou', ['role' => $role, 'validated' => array_merge($validatedData, ['password' => '[HIDDEN]'])]);

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
            Log::info('Transação do banco iniciada para perfil: ' . $role);

            // Criar usuário baseado no perfil
            $userData = $this->prepareUserDataForRole($role, $request);
            $user = User::create($userData);

            Log::info('Usuário criado no banco', ['user_id' => $user->id, 'email' => $user->email, 'role' => $role]);

            // Lógica específica por perfil
            if ($role === 'empresa') {
                $empresa = $this->createEmpresaForUser($user, $request);
                Log::info('Empresa criada', ['empresa_id' => $empresa->id, 'user_id' => $user->id]);
            }

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
                'message' => $this->getSuccessMessageForRole($role),
                'data' => [
                    'user' => $user,
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'expires_in' => 60 * 60, // 1 hora em segundos
                    'redirect_to' => $this->getRedirectUrlForRole($role)
                ]
            ];

            Log::info('Registro concluído com sucesso', [
                'user_id' => $user->id,
                'role' => $role,
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
            // Tentar login como usuário comum primeiro
            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                // Se não encontrou como usuário comum, tentar como admin
                $admin = Admin::where('email', $request->email)->first();

                if (!$admin || !Hash::check($request->password, $admin->password)) {
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

                // Login como admin
                return $this->handleAdminLogin($admin, $request, $key);
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

            // Atualizar último login
            $user->update([
                'ultimo_login' => now(),
                'ip_ultimo_login' => $request->ip()
            ]);

            // Gerar token Sanctum
            $token = $user->createToken('auth_token')->plainTextToken;

            // Log do evento de auditoria
            $this->logAuditEvent('user_login', $user->id, $request);

            RateLimiter::clear($key);

            $response = [
                'success' => true,
                'message' => 'Login realizado com sucesso!',
                'data' => [
                    'user' => $user,
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'expires_in' => 60 * 60, // 1 hora em segundos
                    'redirect_to' => $this->getRedirectUrlForRole($user->role)
                ]
            ];

            Log::info('Login realizado com sucesso', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
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
    private function getValidationRulesForRole(string $role): array
    {
        $baseRules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'terms' => 'required|boolean|accepted',
        ];

        switch ($role) {
            case 'cliente':
                return array_merge($baseRules, [
                    'phone' => 'nullable|string|max:20',
                ]);

            case 'empresa':
                return array_merge($baseRules, [
                    'cnpj' => 'required|string|size:14|unique:empresas',
                    'endereco' => 'required|string|max:500',
                    'telefone' => 'required|string|max:20',
                ]);

            case 'funcionario':
                return array_merge($baseRules, [
                    'empresa_id' => 'required|exists:empresas,id',
                    'phone' => 'nullable|string|max:20',
                ]);

            default:
                return $baseRules;
        }
    }

    /**
     * Preparar dados do usuário baseado no perfil
     */
    private function prepareUserDataForRole(string $role, Request $request): array
    {
        $baseData = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $role,
            'status' => 'ativo',
            'email_notifications' => true,
            'points_notifications' => true,
            'security_notifications' => true,
            'promotional_notifications' => false,
        ];

        switch ($role) {
            case 'cliente':
                return array_merge($baseData, [
                    'pontos' => 100, // Bônus de boas-vindas
                    'pontos_pendentes' => 0,
                    'nivel' => 'Bronze',
                    'telefone' => $request->phone,
                ]);

            case 'empresa':
                return array_merge($baseData, [
                    'pontos' => 0,
                    'pontos_pendentes' => 0,
                    'nivel' => 'Bronze',
                    'telefone' => $request->telefone,
                ]);

            case 'funcionario':
                return array_merge($baseData, [
                    'pontos' => 0,
                    'pontos_pendentes' => 0,
                    'nivel' => 'Bronze',
                    'telefone' => $request->phone,
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
    private function getSuccessMessageForRole(string $role): string
    {
        switch ($role) {
            case 'cliente':
                return 'Conta criada com sucesso! Você ganhou 100 pontos de boas-vindas!';
            case 'empresa':
                return 'Conta empresarial criada com sucesso! Agora você pode gerenciar seus estabelecimentos.';
            case 'funcionario':
                return 'Conta de funcionário criada com sucesso! Bem-vindo à equipe.';
            default:
                return 'Conta criada com sucesso!';
        }
    }

    /**
     * Obter URL de redirecionamento baseada no perfil
     */
    private function getRedirectUrlForRole(string $role): string
    {
        switch ($role) {
            case 'cliente':
                return '/dashboard-cliente.html';
            case 'empresa':
                return '/dashboard-estabelecimento.html';
            case 'funcionario':
                return '/dashboard-funcionario.html';
            case 'admin':
                return '/admin.html';
            default:
                return '/dashboard-cliente.html';
        }
    }

    /**
     * Handle login para administradores
     */
    private function handleAdminLogin(Admin $admin, Request $request, string $key)
    {
        // Verificar se admin está ativo
        if (!$admin->isActive()) {
            Log::warning('Tentativa de login com admin inativo', [
                'admin_id' => $admin->id,
                'email' => $admin->email,
                'status' => $admin->status
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Sua conta administrativa está inativa.'
            ], 403);
        }

        // Atualizar último login
        $admin->updateLastLogin();

        // Gerar token Sanctum (se Admin usar Sanctum, senão criar JWT)
        // Por enquanto, vamos usar uma resposta simples para admin
        RateLimiter::clear($key);

        $response = [
            'success' => true,
            'message' => 'Login administrativo realizado com sucesso!',
            'data' => [
                'admin' => $admin,
                'role' => 'admin',
                'redirect_to' => '/admin.html'
            ]
        ];

        Log::info('Login administrativo realizado com sucesso', [
            'admin_id' => $admin->id,
            'email' => $admin->email,
            'ip' => $request->ip()
        ]);

        return response()->json($response, 200);
    }
}
