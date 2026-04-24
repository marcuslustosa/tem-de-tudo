<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\Cupom;
use App\Mail\WelcomeMail;
use App\Services\DataPrivacyService;
use Laravel\Sanctum\PersonalAccessToken;

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
            'tipo_usuario' => $request->input('tipo_usuario')
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

            /** @var \App\Services\LoyaltyProgramService $loyaltySettings */
            $loyaltySettings = app(\App\Services\LoyaltyProgramService::class);
            if ($loyaltySettings->isMaintenanceMode()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cadastro temporariamente indisponivel em manutencao programada.',
                ], 503);
            }

            if ($perfil === 'cliente' && !$loyaltySettings->isClienteRegistrationAllowed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Novos cadastros de clientes estao temporariamente desativados.',
                ], 403);
            }

            if ($perfil === 'empresa' && !$loyaltySettings->isEmpresaRegistrationAllowed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Novos cadastros de empresas estao temporariamente desativados.',
                ], 403);
            }

            // Normaliza email para evitar divergencia de caixa/espacos entre cadastro e login.
            $normalizedEmail = strtolower(trim((string) $request->input('email', '')));
            if ($normalizedEmail !== '') {
                $request->merge(['email' => $normalizedEmail]);
            }

            // Barreira adicional para evitar duplicidade mesmo em bases sem constraint unica.
            if ($normalizedEmail !== '' && filter_var($normalizedEmail, FILTER_VALIDATE_EMAIL)) {
                $emailExists = User::whereRaw('LOWER(email) = ?', [$normalizedEmail])->exists();
                if ($emailExists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Este email ja esta cadastrado. Tente fazer login ou use outro email.'
                    ], 422);
                }
            }

            // RESTRIÇÃO: Apenas admin master pode criar empresas
            if ($perfil === 'empresa') {
                $user = $this->resolveAuthUserFromRequest($request);
                $perfilAdmin = $user ? strtolower($user->perfil ?? $user->role ?? '') : '';
                
                if (!$user || !in_array($perfilAdmin, ['admin', 'administrador', 'master'])) {
                    Log::warning('Tentativa de criar empresa sem autorização', [
                        'ip' => $request->ip(),
                        'user_id' => $user ? $user->id : null,
                        'user_role' => $user ? ($user->perfil ?? $user->role) : 'não autenticado'
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Apenas administradores podem criar estabelecimentos. Cliente pode se cadastrar normalmente.'
                    ], 403);
                }
                
                Log::info('Admin autenticado criando empresa', ['admin_id' => $user->id, 'admin_email' => $user->email]);
            }

            // Validações específicas por perfil
            $validationRules = $this->getValidationRulesForPerfil($perfil);

            try {
                $validatedData = $request->validate($validationRules);
                Log::info('Validação passou', ['perfil' => $perfil, 'validated' => array_merge($validatedData, ['password' => '[HIDDEN]'])]);
            } catch (ValidationException $e) {
                Log::warning('Erro de validação no registro', [
                    'errors' => $e->errors(),
                    'data' => $this->safeRequestData($request)
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
                'data' => $this->safeRequestData($request)
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos. Verifique os campos e tente novamente.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erro CRITICO na validacao do registro', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_data' => $this->safeRequestData($request),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro na validacao dos dados.',
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
                    $empresa = $this->createEmpresaForUser($user, $request);

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
                
                // Bonus de adesao configuravel via politica global de fidelidade.
                try {
                    $welcomeBonus = app(\App\Services\LoyaltyProgramService::class)->welcomeBonusPoints();
                    if ($welcomeBonus > 0) {
                        \App\Models\Ponto::create([
                            'user_id' => $user->id,
                            'pontos' => $welcomeBonus,
                            'tipo' => 'bonus_adesao',
                            'descricao' => 'Bonus de boas-vindas Tem de Tudo',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        \App\Models\User::where('id', $user->id)->increment('pontos', $welcomeBonus);

                        Log::info('Bonus de adesao creditado', [
                            'user_id' => $user->id,
                            'points' => $welcomeBonus,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::warning('Erro ao creditar bonus de adesao', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }

                // Programa de indicação: processar código de quem indicou
                if ($request->filled('referral_code')) {
                    try {
                        $referrer = \App\Models\User::where('referral_code', $request->referral_code)
                            ->where('id', '!=', $user->id)
                            ->where($this->resolveUsersRoleColumn(), 'cliente')
                            ->first();

                        if ($referrer) {
                            // Registra quem indicou
                            $user->referred_by = $referrer->id;
                            $user->save();

                            // Bônus para o indicador (50 pts)
                            \App\Models\Ponto::create([
                                'user_id'  => $referrer->id,
                                'pontos'   => 50,
                                'tipo'     => 'bonus_indicacao',
                                'descricao'=> "Bônus por indicar {$user->name}",
                                'data'     => now(),
                            ]);
                            \App\Models\User::where('id', $referrer->id)
                                ->increment('pontos', 50);
                            \App\Models\User::where('id', $referrer->id)
                                ->increment('pontos_lifetime', 50);

                            // Bônus para o novo usuário (25 pts extras por ter sido indicado)
                            \App\Models\Ponto::create([
                                'user_id'  => $user->id,
                                'pontos'   => 25,
                                'tipo'     => 'bonus_indicado',
                                'descricao'=> "Bônus por entrar com código de indicação",
                                'data'     => now(),
                            ]);

                            Log::info('Programa de indicação processado', [
                                'novo_user_id'  => $user->id,
                                'referrer_id'   => $referrer->id,
                                'referral_code' => $request->referral_code,
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::warning('Erro ao processar indicação', [
                            'referral_code' => $request->referral_code,
                            'error'         => $e->getMessage(),
                        ]);
                    }
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

            // E-mail de boas-vindas
            try {
                Mail::to($user->email)->queue(new WelcomeMail($user, $perfil === 'empresa' ? 'company' : 'client'));
            } catch (\Throwable $e) {
                Log::warning('Falha ao enfileirar WelcomeMail', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            }

            // Log do evento de auditoria
            $this->logAuditEvent('user_registered', $user->id, $request);

            RateLimiter::clear($key);

            $resolvedUser = array_merge($user->toArray(), ['perfil' => $perfil]);
            $response = [
                'success' => true,
                'message' => $this->getSuccessMessageForPerfil($perfil),
                'token' => $token,
                'user' => $resolvedUser,
                'data' => [
                    'user' => $resolvedUser,
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
                'sql_state' => $e->errorInfo[0] ?? null,
            ]);

            if ($e->getCode() == 23000 || (($e->errorInfo[0] ?? null) === '23505')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este email ja esta cadastrado. Tente fazer login ou use outro email.'
                ], 422);
            }

            return response()->json([
                'success' => false,
                'message' => 'Erro no banco de dados. Tente novamente em alguns instantes.',
            ], 500);

        } catch (\Exception $e) {
            DB::rollBack();
            RateLimiter::hit($key, 300);

            Log::error('Erro CRITICO no registro', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_data' => $this->safeRequestData($request),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar conta. Tente novamente.',
            ], 500);
        }
    }

    /**
     * Login de usuário com redirecionamento baseado no perfil
     */
    public function login(Request $request)
    {
        // Compatibilidade: frontend usa "password", alguns clientes legados ainda enviam "senha".
        if (!$request->filled('password') && $request->filled('senha')) {
            $request->merge(['password' => $request->input('senha')]);
        }

        Log::info('=== INÍCIO DO LOGIN ===', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
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
                'email' => 'required|string|max:255',
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
            // Buscar usuário por email (case-insensitive)
            $identifier = strtolower(trim((string) $request->input('email')));
            $userQuery = User::query();

            if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
                $userQuery->whereRaw('LOWER(email) = ?', [$identifier]);
            } else {
                $digits = preg_replace('/\D+/', '', $identifier);
                $userQuery->where(function ($q) use ($identifier, $digits) {
                    $q->whereRaw('LOWER(email) = ?', [$identifier]);
                    if ($digits && Schema::hasColumn('users', 'cpf')) {
                        $q->orWhereRaw("REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), '/', '') = ?", [$digits]);
                    }
                });
            }

            $user = $userQuery->first();

            if (!$user || !$this->isValidUserPassword($user, (string) $request->password)) {
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
            $roleColumn = $this->resolveUsersRoleColumn();
            $perfil = $this->getPerfilFromRole((string) ($user->{$roleColumn} ?? $user->perfil ?? $user->role ?? 'cliente'));

            if (Schema::hasColumn('users', 'status') && strtolower((string) $user->status) !== 'ativo') {
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

            $resolvedUser = array_merge($user->toArray(), ['perfil' => $perfil]);
            $response = [
                'success' => true,
                'message' => 'Login realizado com sucesso!',
                'token' => $token,
                'user' => $resolvedUser,
                'token_type' => 'Bearer',
                'expires_in' => 60 * 60, // 1 hora em segundos
                'redirect_to' => $this->getRedirectUrlForPerfil($perfil),
            ];

            Log::info('Login realizado com sucesso', [
                'user_id' => $user->id,
                'email' => $user->email,
                'perfil' => $perfil,
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
                'message' => 'Erro ao processar login. Tente novamente.',
            ], 500);
        }
    }

    /**
     * Calcular nível VIP baseado nos pontos
     */
    private function calcularNivel($pontos)
    {
        if ($pontos >= 5000) return 'Platina';
        if ($pontos >= 1500) return 'Ouro';
        if ($pontos >= 500) return 'Prata';
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
            'tipo' => 'ganho'
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
     * Resolve usuário autenticado mesmo em rota pública com Bearer token.
     */
    private function resolveAuthUserFromRequest(Request $request): ?User
    {
        $user = $request->user();
        if ($user instanceof User) {
            return $user;
        }

        $bearerToken = $request->bearerToken();
        if (!$bearerToken) {
            return null;
        }

        $accessToken = PersonalAccessToken::findToken($bearerToken);
        $tokenable = $accessToken?->tokenable;

        return $tokenable instanceof User ? $tokenable : null;
    }


    /**
     * Log de eventos para auditoria
     */
    private function logAuditEvent(string $event, int $userId, $request = null): void
    {
        try {
            Log::info("AUDIT: {$event}", [
                'user_id' => $userId,
                'ip' => $request ? $request->ip() : null,
                'user_agent' => $request ? $request->userAgent() : null,
                'timestamp' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Erro no log de auditoria: ' . $e->getMessage());
        }
    }

    /**
     * Remove campos sensiveis antes de logar payloads de autenticacao.
     */
    private function safeRequestData(Request $request): array
    {
        return $request->except([
            'password',
            'password_confirmation',
            'current_password',
            'token',
            'remember_token',
        ]);
    }

    /**
     * Obter regras de validacao especificas por perfil
     */
    private function getValidationRulesForPerfil(string $perfil): array
    {
        $baseRules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'password_confirmation' => 'required|string|min:6',
            'terms' => 'required|boolean|accepted',
            'privacy_policy' => 'sometimes|boolean',
            'marketing_consent' => 'sometimes|boolean',
            'consent_version' => 'sometimes|string|max:20',
        ];

        switch ($perfil) {
            case 'cliente':
                return array_merge($baseRules, [
                    'telefone' => 'nullable|string|max:20',
                ]);

            case 'empresa':
                return array_merge($baseRules, [
                    'cnpj' => [
                        'required',
                        'string',
                        'regex:/^(\d{14}|\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2})$/',
                        'unique:empresas',
                    ],
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
        $roleColumn = $this->resolveUsersRoleColumn();
        $baseData = [
            'name' => $request->name,
            'email' => strtolower(trim($request->email)),
            'password' => Hash::make($request->password),
            $roleColumn => $perfil,
        ];

        if (Schema::hasColumn('users', 'status')) {
            $baseData['status'] = 'ativo';
        }
        if (Schema::hasColumn('users', 'terms_accepted_at')) {
            $baseData['terms_accepted_at'] = now();
        }
        if (Schema::hasColumn('users', 'privacy_policy_accepted_at')) {
            $baseData['privacy_policy_accepted_at'] = $request->boolean('privacy_policy', true) ? now() : null;
        }
        if (Schema::hasColumn('users', 'data_processing_consent_at')) {
            $baseData['data_processing_consent_at'] = $request->boolean('terms', true) ? now() : null;
        }
        if (Schema::hasColumn('users', 'marketing_consent')) {
            $baseData['marketing_consent'] = $request->boolean('marketing_consent', false);
        }
        if (Schema::hasColumn('users', 'consent_version')) {
            $baseData['consent_version'] = $request->input('consent_version', config('privacy.default_consent_version', 'v1'));
        }

        $finalData = $baseData;
        switch ($perfil) {
            case 'cliente':
                if (Schema::hasColumn('users', 'telefone')) {
                    $finalData['telefone'] = $request->telefone;
                }
                break;

            case 'empresa':
                if (Schema::hasColumn('users', 'telefone')) {
                    $finalData['telefone'] = $request->telefone;
                }
                break;

            default:
                break;
        }

        return $this->filterUsersColumns($finalData);
    }

    private function resolveUsersRoleColumn(): string
    {
        if (Schema::hasColumn('users', 'perfil')) {
            return 'perfil';
        }
        if (Schema::hasColumn('users', 'role')) {
            return 'role';
        }
        if (Schema::hasColumn('users', 'tipo')) {
            return 'tipo';
        }

        return 'perfil';
    }

    private function filterUsersColumns(array $payload): array
    {
        try {
            if (!Schema::hasTable('users')) {
                return $payload;
            }

            $columns = Schema::getColumnListing('users');
            if (!$columns) {
                return $payload;
            }

            return array_intersect_key($payload, array_flip($columns));
        } catch (\Throwable $e) {
            Log::warning('Nao foi possivel filtrar colunas de users para cadastro', [
                'error' => $e->getMessage(),
            ]);

            return $payload;
        }
    }

    private function filterTableColumns(string $table, array $payload): array
    {
        try {
            if (!Schema::hasTable($table)) {
                return [];
            }

            $columns = Schema::getColumnListing($table);
            if (!$columns) {
                return [];
            }

            return array_intersect_key($payload, array_flip($columns));
        } catch (\Throwable $e) {
            Log::warning('Nao foi possivel filtrar colunas da tabela', [
                'table' => $table,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Criar empresa para usuário do tipo empresa
     */
    private function createEmpresaForUser(User $user, Request $request)
    {
        $payload = [
            'owner_id' => $user->id,
            'user_id' => $user->id,
            'nome' => $request->input('name'),
            'ramo' => 'geral',
            'endereco' => $request->input('endereco'),
            'telefone' => $request->input('telefone'),
            'cnpj' => $request->input('cnpj'),
            'ativo' => true,
            'status' => 'ativo',
            'points_multiplier' => 1.0,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $safePayload = $this->filterTableColumns('empresas', $payload);
        if (empty($safePayload)) {
            throw new \RuntimeException('Tabela empresas indisponivel ou sem colunas compativeis.');
        }

        $empresaId = DB::table('empresas')->insertGetId($safePayload);
        return \App\Models\Empresa::query()->find($empresaId);
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
                // Novo front (Stitch) - dashboard do cliente
                return '/meus_pontos.html';
            case 'empresa':
                // Novo front (Stitch) - dashboard do parceiro/estabelecimento
                return '/dashboard_parceiro.html';
            case 'admin':
                // Novo front (Stitch) - dashboard admin master
                return '/dashboard_admin_master.html';
            default:
                return '/entrar.html';
        }
    }

    /**
     * Obter perfil baseado no role do banco
     */
    private function getPerfilFromRole(string $role): string
    {
        $value = strtolower(trim($role));

        if (in_array($value, ['admin', 'administrador', 'master', 'admin_master', 'administrador_master'], true)) {
            return 'admin';
        }

        if (in_array($value, ['empresa', 'estabelecimento', 'parceiro', 'lojista'], true)) {
            return 'empresa';
        }

        return 'cliente';
    }

    /**
     * Login de admin
     */
    public function adminLogin(Request $request)
    {
        if (!$request->filled('password') && $request->filled('senha')) {
            $request->merge(['password' => $request->input('senha')]);
        }
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
            // Buscar usuário admin (aceita 'admin' ou 'administrador' por compatibilidade)
            $roleColumn = $this->resolveUsersRoleColumn();
            $user = User::where('email', $request->email)
                ->whereIn($roleColumn, ['admin', 'administrador', 'master', 'admin_master', 'administrador_master'])
                ->first();

            if (!$user || !$this->isValidUserPassword($user, (string) $request->password)) {
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
            if (Schema::hasColumn('users', 'status') && strtolower((string) $user->status) !== 'ativo') {
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
                    'redirect_to' => '/dashboard_admin_master.html'
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
                    'user' => array_merge(
                        $user->toArray(),
                        ['perfil' => $this->getPerfilFromRole((string) ($user->{$this->resolveUsersRoleColumn()} ?? 'cliente'))]
                    ),
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

    /**
     * Recuperar senha - enviar email
     */
    public function forgotPassword(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email'
            ]);

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                // Por segurança, não revelar se o email existe
                return response()->json([
                    'success' => true,
                    'message' => 'Se o e-mail existir, você receberá instruções de recuperação.'
                ]);
            }

            // Gerar token de recuperação
            $token = bin2hex(random_bytes(32));
            
            // Salvar token no banco
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $request->email],
                [
                    'token' => Hash::make($token),
                    'created_at' => now()
                ]
            );

            // Enviar e-mail com link de recuperação
            try {
                \Mail::to($user->email)->send(new \App\Mail\ResetPasswordMail($token, $user->email));
                
                Log::info('E-mail de recuperação enviado', [
                    'email' => $user->email,
                    'token_preview' => substr($token, 0, 10) . '...'
                ]);
            } catch (\Exception $mailError) {
                Log::error('Erro ao enviar e-mail de recuperação', [
                    'error' => $mailError->getMessage(),
                    'email' => $user->email
                ]);
                
                // Mesmo com erro no e-mail, retornar sucesso por segurança
            }

            Log::info('Solicitação de recuperação de senha', [
                'email' => $request->email,
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'E-mail de recuperação enviado com sucesso!',
                'debug_token' => app()->environment('local') ? $token : null
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao processar recuperação de senha', [
                'error' => $e->getMessage(),
                'email' => $request->email ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar solicitação'
            ], 500);
        }
    }

    /**
     * Redefinir senha com token
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|min:6|confirmed',
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record || !Hash::check($request->token, $record->token)) {
            return response()->json(['success' => false, 'message' => 'Token inválido ou expirado.'], 422);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        $user->password = $request->password;
        $user->save();

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(['success' => true, 'message' => 'Senha redefinida com sucesso.']);
    }

    /**
     * Alterar senha autenticado
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:6|confirmed',
        ]);

        $user = $request->user();
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Senha atual incorreta.'], 422);
        }

        $user->password = $request->password;
        $user->save();

        return response()->json(['success' => true, 'message' => 'Senha alterada com sucesso.']);
    }

    /**
     * Atualizar perfil do usuário autenticado
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não autenticado'
                ], 401);
            }

            // Validação dos dados
            $validatedData = $request->validate([
                'name' => 'sometimes|string|max:255',
                'nome' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $user->id,
                'telefone' => 'sometimes|string|max:20',
                // CPF e data_nascimento NÃO podem ser alterados pelo próprio cliente.
                // Apenas admins podem alterar via PUT /admin/usuarios/{id}/dados-sensiveis
            ]);

            // Garantir que campos sensíveis nunca sejam atualizados por esta rota,
            // mesmo que o cliente tente enviá-los manualmente.
            $camposBloqueados = ['cpf', 'data_nascimento', 'perfil', 'nivel', 'pontos'];
            $safe = array_diff_key(array_filter($validatedData), array_flip($camposBloqueados));
            $user->update($safe);

            Log::info('Perfil atualizado com sucesso', [
                'user_id' => $user->id,
                'fields' => array_keys($validatedData)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Perfil atualizado com sucesso!',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'nome' => $user->nome ?? $user->name,
                    'email' => $user->email,
                    'telefone' => $user->telefone,
                    'cpf' => $user->cpf,
                    'data_nascimento' => $user->data_nascimento,
                    'perfil' => $this->getPerfilFromRole((string) ($user->{$this->resolveUsersRoleColumn()} ?? 'cliente'))
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar perfil', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * Listar usuários (admin).
     */
    public function listUsers(Request $request)
    {
        $perPage = max(1, min((int) $request->input('per_page', 20), 100));
        $roleColumn = $this->resolveUsersRoleColumn();

        $columns = ['id', 'name', 'email', 'created_at', 'updated_at'];
        foreach ([$roleColumn, 'perfil', 'status', 'telefone', 'pontos'] as $column) {
            if (!in_array($column, $columns, true) && Schema::hasColumn('users', $column)) {
                $columns[] = $column;
            }
        }

        $query = User::query()
            ->select($columns)
            ->orderByDesc('created_at');

        if ($request->filled('perfil')) {
            $query->where($roleColumn, $request->input('perfil'));
        }

        if ($request->filled('status') && Schema::hasColumn('users', 'status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $term = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term);

                if (Schema::hasColumn('users', 'telefone')) {
                    $q->orWhere('telefone', 'like', $term);
                }
            });
        }

        $users = $query->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => [
                'data' => $users->items(),
                'total' => $users->total(),
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'last_page' => $users->lastPage()
            ]
        ]);
    }

    /**
     * Atualizar CPF e/ou data de nascimento de um usuário (somente admin).
     * Clientes não podem alterar esses campos diretamente para evitar fraudes
     * (ex: mudar CPF para obter benefícios de primeira compra duas vezes).
     */
    public function updateDadosSensiveis(Request $request, int $id)
    {
        $admin = Auth::user();
        if (!$admin || $admin->perfil !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Acesso negado.'], 403);
        }

        $user = User::find($id);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Usuário não encontrado.'], 404);
        }

        $payload = $request->validate([
            'cpf'              => 'sometimes|string|max:14',
            'data_nascimento'  => 'sometimes|date',
            'motivo'           => 'required|string|max:500',
        ]);

        $alteracoes = array_diff_key($payload, ['motivo' => true]);
        $user->update($alteracoes);

        Log::info('Admin alterou dados sensíveis de usuário', [
            'admin_id'  => $admin->id,
            'user_id'   => $user->id,
            'campos'    => array_keys($alteracoes),
            'motivo'    => $payload['motivo'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Dados sensíveis atualizados com sucesso.',
            'data'    => ['id' => $user->id, 'cpf' => $user->cpf, 'data_nascimento' => $user->data_nascimento],
        ]);
    }

    /**
     * Atualizar status de usuário (admin).
     */
    public function updateUserStatus(Request $request, int $id)
    {
        $payload = $request->validate([
            'status' => 'required|string|in:ativo,inativo,bloqueado',
        ]);

        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não encontrado.',
            ], 404);
        }

        $user->status = $payload['status'];
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Status atualizado com sucesso.',
            'data' => $user,
        ]);
    }

    /**
     * Criar usuário pelo painel admin.
     */
    public function createUser(Request $request)
    {
        if (!$request->filled('password') && $request->filled('senha')) {
            $request->merge(['password' => $request->input('senha')]);
        }

        $payload = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'perfil' => 'required|string|in:cliente,empresa,admin',
            'status' => 'nullable|string|in:ativo,inativo,bloqueado',
            'telefone' => 'nullable|string|max:20',
            'cnpj' => 'nullable|string|max:20',
            'endereco' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $roleColumn = $this->resolveUsersRoleColumn();
            $userPayload = [
                'name' => $payload['name'],
                'email' => strtolower(trim($payload['email'])),
                'password' => Hash::make($payload['password']),
                $roleColumn => $payload['perfil'],
                'status' => $payload['status'] ?? 'ativo',
                'telefone' => $payload['telefone'] ?? null,
                'email_verified_at' => now(),
                'is_active' => DB::connection()->getDriverName() === 'pgsql' ? 'true' : true,
                'pontos' => 0,
            ];

            $safeUserPayload = $this->filterUsersColumns($userPayload);
            if (empty($safeUserPayload)) {
                throw new \RuntimeException('Tabela users indisponivel ou sem colunas compativeis.');
            }

            $user = User::create($safeUserPayload);

            if ($payload['perfil'] === 'empresa') {
                $empresaPayload = [
                    'owner_id' => $user->id,
                    'user_id' => $user->id,
                    'nome' => $payload['name'],
                    'ramo' => 'geral',
                    'endereco' => $payload['endereco'] ?? 'Nao informado',
                    'telefone' => $payload['telefone'] ?? 'Nao informado',
                    'cnpj' => $payload['cnpj'] ?? sprintf(
                        '%02d.%03d.%03d/%04d-%02d',
                        rand(10, 99),
                        rand(100, 999),
                        rand(100, 999),
                        rand(1000, 9999),
                        rand(10, 99)
                    ),
                    'ativo' => DB::connection()->getDriverName() === 'pgsql' ? 'true' : true,
                    'status' => 'ativo',
                    'points_multiplier' => 1.0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $safeEmpresaPayload = $this->filterTableColumns('empresas', $empresaPayload);
                if (empty($safeEmpresaPayload)) {
                    throw new \RuntimeException('Tabela empresas indisponivel ou sem colunas compativeis.');
                }

                DB::table('empresas')->insert($safeEmpresaPayload);
            }


            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Usuário criado com sucesso.',
                'data' => $user,
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Erro ao criar usuário via admin', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar usuário.',
            ], 500);
        }
    }

    /**
     * Excluir (anonimizar) conta do usuário autenticado — LGPD art. 18
     */
    public function deletarConta(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|string',
            'reason' => 'nullable|string|max:1000',
        ]);

        $user = $request->user();

        if (!$this->isValidUserPassword($user, $request->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Senha incorreta. Por favor, confirme sua senha para excluir a conta.',
            ], 403);
        }

        // Anonimizar dados pessoais (LGPD direito ao apagamento)
        $user->update([
            'name'            => 'Usuário Removido',
            'email'           => 'deleted_' . $user->id . '_' . time() . '@removed.local',
            'telefone'        => null,
            'data_nascimento' => null,
            'fcm_token'       => null,
            'referral_code'   => null,
            'referred_by'     => null,
            'status'          => 'deleted',
            'is_active'       => false,
        ]);

        // Revogar todos os tokens Sanctum
        $user->tokens()->delete();

        app(DataPrivacyService::class)->deleteAccount(
            $user,
            $request->input('reason'),
            [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Sua conta foi excluída com sucesso. Todos os dados pessoais foram removidos.',
        ]);
    }

    /**
     * Valida senha atual e migra automaticamente credenciais legadas em texto puro.
     */
    private function isValidUserPassword(User $user, string $plainPassword): bool
    {
        $storedPassword = (string) ($user->password ?? '');

        if ($storedPassword === '') {
            return false;
        }

        if (Hash::check($plainPassword, $storedPassword)) {
            return true;
        }

        // Fallback de compatibilidade: credenciais legadas eventualmente gravadas sem hash.
        if (hash_equals($storedPassword, $plainPassword)) {
            $user->password = Hash::make($plainPassword);
            $user->save();

            Log::warning('Senha legada migrada para hash seguro no login', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return true;
        }

        return false;
    }
}
