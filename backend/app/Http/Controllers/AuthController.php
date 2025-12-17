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
use App\Models\Cupom;

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
                'perfil' => 'required|string|in:cliente,empresa',
            ]);

            $perfil = $request->perfil;
            Log::info('Perfil selecionado', ['perfil' => $perfil]);

            // Validações específicas por perfil
            $validationRules = $this->getValidationRulesForPerfil($perfil);

            try {
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
            }

            // Perfil será usado diretamente

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
            Log::error('Erro CRÍTICO na validação do registro', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['password', 'password_confirmation'])
            ]);

            // Em produção, retornar erro detalhado para debug
            return response()->json([
                'success' => false,
                'message' => 'Erro na validação dos dados: ' . $e->getMessage(),
                'error_details' => [
                    'message' => $e->getMessage(),
                    'file' => basename($e->getFile()),
                    'line' => $e->getLine()
                ]
            ], 400);
        }

        try {
            DB::beginTransaction();
            Log::info('Transação do banco iniciada para perfil: ' . $perfil);

            // Criar usuário baseado no perfil
            $userData = $this->prepareUserDataForPerfil($perfil, $request);
            $user = User::create($userData);

            // Garantir que $user->id está definido antes de criar empresa
            if (!$user || !$user->id) {
                Log::error('Usuário criado, mas ID não disponível para criação de empresa', ['user' => $user]);
                throw new \Exception('Erro interno: falha ao criar usuário.');
            }
            Log::info('Usuário criado no banco', ['user_id' => $user->id, 'email' => $user->email, 'perfil' => $perfil]);

            // Se for empresa, criar registro na tabela empresas
            if ($perfil === 'empresa') {
                try {
                    Log::info('Tentando criar empresa com dados:', [
                        'nome' => $request->name,
                        'endereco' => $request->endereco,
                        'telefone' => $request->telefone,
                        'cnpj' => $request->cnpj,
                        'owner_id' => $user->id,
                    ]);
                    $empresa = \App\Models\Empresa::create([
                        'nome' => $request->name,
                        'endereco' => $request->endereco,
                        'telefone' => $request->telefone,
                        'cnpj' => $request->cnpj,
                        'owner_id' => $user->id,
                        'ativo' => DB::raw('true'),
                        'points_multiplier' => DB::raw('1.0'),
                    ]);

                    Log::info('Empresa criada com sucesso', ['empresa_id' => $empresa->id, 'user_id' => $user->id]);
                } catch (\Exception $e) {
                    Log::error('Erro ao criar empresa', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString(), 'user_id' => $user->id]);
                    throw $e;
                }
            }

            // Gerar Sanctum token
            $token = $user->createToken('auth_token')->plainTextToken;
            Log::info('Token Sanctum gerado', ['user_id' => $user->id]);

            // Gerar QR Code para cliente
            if ($perfil === 'cliente') {
                try {
                    $qrCodeService = app(\App\Services\QRCodeService::class);
                    $qrCodeService->gerarQRCodeCliente($user);
                    Log::info('QR Code do cliente gerado', ['user_id' => $user->id]);
                } catch (\Exception $e) {
                    Log::warning('Erro ao gerar QR Code do cliente', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Gerar QR Code para empresa (se aplicável)
            if ($perfil === 'empresa' && isset($empresa)) {
                try {
                    $qrCodeService = app(\App\Services\QRCodeService::class);
                    $qrCodeService->gerarQRCodeEmpresa($empresa);
                    Log::info('QR Code da empresa gerado', ['empresa_id' => $empresa->id]);
                } catch (\Exception $e) {
                    Log::warning('Erro ao gerar QR Code da empresa', [
                        'empresa_id' => $empresa->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

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

            // Retornar erro detalhado do banco de dados
            return response()->json([
                'success' => false,
                'message' => 'Erro no banco de dados. Tente novamente em alguns instantes.',
                'error_details' => [
                    'type' => 'QueryException',
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'sql_state' => $e->errorInfo[0] ?? null,
                    'driver_code' => $e->errorInfo[1] ?? null,
                    'driver_message' => $e->errorInfo[2] ?? null,
                    'file' => basename($e->getFile()),
                    'line' => $e->getLine()
                ]
            ], 500);

        } catch (\Exception $e) {
            DB::rollBack();
            RateLimiter::hit($key, 300);

            Log::error('Erro CRÍTICO no registro', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['password', 'password_confirmation'])
            ]);

            // Retornar erro detalhado para debug
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar conta: ' . $e->getMessage(),
                'error_details' => [
                    'type' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => basename($e->getFile()),
                    'line' => $e->getLine()
                ]
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
                    'user' => array_merge($user->toArray(), ['perfil' => $user->user_type]),  // Corrigido: era $user->perfil
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'expires_in' => 60 * 60, // 1 hora em segundos
                    'redirect_to' => $this->getRedirectUrlForPerfil($user->user_type)  // Corrigido: era $user->perfil
                ]
            ];

            Log::info('Login realizado com sucesso', [
                'user_id' => $user->id,
                'email' => $user->email,
                'perfil' => $user->user_type,  // Corrigido: era $user->perfil
                'ip' => $request->ip()
            ]);

            return response()->json($response, 200);

        } catch (\Exception $e) {
            RateLimiter::hit($key, 300);

            Log::error('Erro CRÍTICO no login', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'email' => $request->email,
                'ip' => $request->ip()
            ]);

            // Retornar erro detalhado para debug
            return response()->json([
                'success' => false,
                'message' => 'Erro no login: ' . $e->getMessage(),
                'error_details' => [
                    'type' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => basename($e->getFile()),
                    'line' => $e->getLine()
                ]
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
     * Obter permissões baseadas no perfil
     */
    private function getUserPermissions($perfil)
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

        return $permissions[$perfil] ?? $permissions['cliente'];
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
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string|min:8',
            'terms' => 'required|boolean|accepted',
        ];

        switch ($perfil) {
            case 'cliente':
                return array_merge($baseRules, [
                    'telefone' => 'nullable|string|max:20',
                ]);

            case 'empresa':
                return array_merge($baseRules, [
                    'cnpj' => 'required|string|regex:/^\d{2}\.\d{3}\.\d{3}\/\d{4}\-\d{2}$/|unique:empresas',
                    'endereco' => 'required|string|max:500',
                    'telefone' => 'required|string|max:20',
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
            'user_type' => $perfil,  // Corrigido: era 'perfil', agora 'user_type' (nome da coluna no banco)
            'status' => 'ativo',
        ];

        switch ($perfil) {
            case 'cliente':
                return array_merge($baseData, [
                    'telefone' => $request->telefone,
                ]);

            case 'empresa':
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
            'ativo' => DB::raw('true'),
            'points_multiplier' => DB::raw('1.0'),
        ]);
    }

    /**
     * Obter mensagem de sucesso baseada no perfil
     */
    private function getSuccessMessageForPerfil(string $perfil): string
    {
        switch ($perfil) {
            case 'cliente':
                return 'Conta de cliente criada com sucesso! Você pode ganhar pontos em suas compras.';
            case 'empresa':
                return 'Conta de empresa criada com sucesso! Você pode oferecer pontos aos seus clientes.';
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
            case 'cliente':
                return '/dashboard-cliente.html';
            case 'empresa':
                return '/dashboard-estabelecimento.html';
            default:
                return '/dashboard-cliente.html';
        }
    }

    /**
     * Obter perfil baseado no role do banco
     */
    private function getPerfilFromRole(string $role): string
    {
        return $role;
    }

    /**
     * Login de admin
     */
    public function adminLogin(Request $request)
    {
        Log::info('=== INÍCIO DO LOGIN ADMIN ===', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'email' => $request->email
        ]);

        // Rate limiting para login admin
        $key = 'admin-login-attempts:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            Log::warning('Rate limit excedido para login admin', ['ip' => $request->ip(), 'key' => $key]);
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

            Log::info('Validação do login admin passou', ['email' => $request->email]);

        } catch (ValidationException $e) {
            Log::warning('Erro de validação no login admin', [
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
            // Buscar usuário admin
            $user = User::where('email', $request->email)->where('perfil', 'admin')->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                RateLimiter::hit($key, 300); // 5 minutos
                Log::warning('Tentativa de login admin falhou', [
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
                Log::warning('Tentativa de login admin com usuário inativo', [
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
            $token = $user->createToken('admin_auth_token')->plainTextToken;

            // Log do evento de auditoria
            $this->logAuditEvent('admin_login', $user->id, $request);

            RateLimiter::clear($key);

            $response = [
                'success' => true,
                'message' => 'Login administrativo realizado com sucesso!',
                'data' => [
                    'user' => array_merge($user->toArray(), ['perfil' => 'admin']),
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'expires_in' => 60 * 60, // 1 hora em segundos
                    'redirect_to' => '/admin.html'
                ]
            ];

            Log::info('Login admin realizado com sucesso', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip()
            ]);

            return response()->json($response, 200);

        } catch (\Exception $e) {
            RateLimiter::hit($key, 300);

            Log::error('Erro geral no login admin', [
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
     * Logout de admin
     */
    public function adminLogout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Logout administrativo realizado com sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao fazer logout'
            ], 500);
        }
    }

    /**
     * Perfil do admin
     */
    public function adminProfile(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => array_merge($user->toArray(), ['perfil' => 'admin'])
            ]
        ]);
    }

    /**
     * Verificar se o token é válido
     */
    public function verify(Request $request)
    {
        try {
            $user = $request->user();

            return response()->json([
                'success' => true,
                'message' => 'Token válido',
                'data' => [
                    'user' => array_merge($user->toArray(), ['perfil' => $user->user_type]),  // Corrigido
                    'valid' => true
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido',
                'valid' => false
            ], 401);
        }
    }

    /**
     * Refresh token
     */
    public function refreshToken(Request $request)
    {
        try {
            $user = $request->user();

            // Revogar token atual
            $request->user()->currentAccessToken()->delete();

            // Gerar novo token
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Token renovado com sucesso',
                'data' => [
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'expires_in' => 60 * 60
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao renovar token'
            ], 500);
        }
    }

    /**
     * Dashboard data para cliente
     */
    public function clienteDashboard(Request $request)
    {
        try {
            $user = $request->user();

            // Dados básicos do cliente
            $pontos = $user->pontos ?? 0;
            $nivel = $this->calcularNivel($pontos);

            // Contar cupons ativos
            $cuponsAtivos = \App\Models\Cupom::where('user_id', $user->id)
                ->where('status', 'ativo')
                ->where('validade', '>', now())
                ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'pontos_totais' => $pontos,
                    'nivel' => $nivel,
                    'cupons_ativos' => $cuponsAtivos,
                    'user' => $user
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao carregar dashboard cliente', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar dados do dashboard'
            ], 500);
        }
    }


}
