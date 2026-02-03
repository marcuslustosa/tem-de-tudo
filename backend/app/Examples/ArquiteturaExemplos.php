<?php

/**
 * ========================================
 * GUIA PRÁTICO - ARQUITETURA ENTERPRISE
 * ========================================
 * 
 * Este arquivo demonstra como usar todos os componentes
 * da arquitetura implementada.
 */

namespace App\Examples;

use App\DTOs\Auth\RegisterDTO;
use App\DTOs\CheckIn\CheckInDTO;
use App\Services\AuthService;
use App\Services\CheckInService;
use App\Repositories\UserRepository;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Log;

class ArquiteturaExemplos
{
    /**
     * ========================================
     * EXEMPLO 1: Cadastro de Usuário
     * ========================================
     */
    public function exemploCadastroUsuario()
    {
        // 1. Controller recebe dados validados
        $dadosValidados = [
            'nome' => 'João Silva',
            'email' => 'joao@email.com',
            'senha' => '123456',
            'telefone' => '(11) 99999-9999'
        ];

        // 2. Cria DTO (Data Transfer Object)
        $dto = RegisterDTO::fromArray($dadosValidados);
        
        // 3. DTO é imutável e tipado
        // $dto->email → garantido ser string
        // $dto->name → readonly, não pode mudar
        
        // 4. Passa DTO para Service
        $authService = app(AuthService::class);
        $resultado = $authService->register($dto);
        
        // 5. Service retorna array com user + token
        $usuario = $resultado['user'];
        $token = $resultado['token'];
        
        // 6. Formata response com Resource
        return new UserResource($usuario);
        
        // BENEFÍCIOS:
        // ✅ Validação em 3 camadas (FormRequest, DTO, Service)
        // ✅ Código limpo e testável
        // ✅ Logs automáticos
        // ✅ Transações de banco automáticas
    }

    /**
     * ========================================
     * EXEMPLO 2: Check-in com Pontos
     * ========================================
     */
    public function exemploCheckIn()
    {
        // 1. Dados do check-in
        $dados = [
            'empresa_id' => 1,
            'latitude' => -23.550520,
            'longitude' => -46.633308,
            'metodo' => 'qrcode'
        ];
        
        $userId = 1; // Usuário autenticado
        
        // 2. Cria DTO
        $dto = CheckInDTO::fromArray($dados, $userId);
        
        // 3. Service faz TUDO (verificações, cálculos, transações)
        $checkInService = app(CheckInService::class);
        $resultado = $checkInService->checkIn($dto);
        
        // 4. Resultado estruturado
        $checkIn = $resultado['checkin'];
        $pontosGanhos = $resultado['pontos_ganhos'];
        $pontosTotais = $resultado['pontos_totais'];
        
        // BENEFÍCIOS:
        // ✅ Verifica se já fez check-in hoje (dentro do Service)
        // ✅ Calcula pontos com multiplicador (dentro do Service)
        // ✅ Cria check-in + ponto + atualiza usuário (transaction)
        // ✅ Rollback automático se der erro
        // ✅ Logs estruturados
    }

    /**
     * ========================================
     * EXEMPLO 3: Repository Pattern
     * ========================================
     */
    public function exemploRepository()
    {
        $userRepo = app(UserRepository::class);
        
        // Buscar usuário
        $usuario = $userRepo->findByEmail('joao@email.com');
        
        // Adicionar pontos
        $userRepo->addPoints($usuario, 50);
        
        // Deduzir pontos
        if ($userRepo->deductPoints($usuario, 30)) {
            echo "Pontos deduzidos com sucesso!";
        }
        
        // Atualizar senha
        $userRepo->updatePassword($usuario, 'novaSenha123');
        
        // BENEFÍCIOS:
        // ✅ Queries complexas centralizadas
        // ✅ Fácil mockar em testes
        // ✅ Pode trocar ORM sem quebrar código
    }

    /**
     * ========================================
     * EXEMPLO 4: Testing
     * ========================================
     */
    public function exemploTestes()
    {
        // UNIT TEST (isolado)
        /*
        public function test_auth_service_registra_usuario() {
            // Mock do Repository
            $userRepo = Mockery::mock(UserRepository::class);
            $userRepo->shouldReceive('emailExists')->andReturn(false);
            $userRepo->shouldReceive('create')->andReturn(new User);
            
            // Service usa o mock
            $service = new AuthService($userRepo);
            
            // DTO de teste
            $dto = new RegisterDTO('João', 'joao@test.com', '123456');
            
            // Executa
            $result = $service->register($dto);
            
            // Verifica
            $this->assertNotNull($result['token']);
        }
        */
        
        // INTEGRATION TEST (API completa)
        /*
        public function test_endpoint_registro() {
            $response = $this->postJson('/api/register', [
                'nome' => 'João',
                'email' => 'joao@test.com',
                'senha' => '123456'
            ]);
            
            $response->assertStatus(201)
                     ->assertJsonStructure([
                         'success',
                         'data' => ['token', 'user']
                     ]);
            
            $this->assertDatabaseHas('users', [
                'email' => 'joao@test.com'
            ]);
        }
        */
    }

    /**
     * ========================================
     * EXEMPLO 5: Fluxo Completo Real
     * ========================================
     */
    public function fluxoCompletoReal()
    {
        /**
         * REQUEST:
         * POST /api/check-in
         * Headers: { Authorization: Bearer TOKEN }
         * Body: { empresa_id: 1 }
         * 
         * PIPELINE:
         * 
         * 1️⃣ Middleware: SanitizeInput
         *    └─ Remove HTML tags, trim
         * 
         * 2️⃣ Middleware: auth:sanctum
         *    └─ Valida token, carrega $request->user()
         * 
         * 3️⃣ Middleware: CheckUserActive
         *    └─ Verifica se user->ativo == true
         * 
         * 4️⃣ Middleware: LogApiRequests
         *    └─ Log: "API Request | POST /api/check-in | user_id: 1"
         * 
         * 5️⃣ FormRequest: CheckInRequest
         *    └─ Valida: empresa_id required|exists
         * 
         * 6️⃣ Controller: CheckInController::checkIn()
         *    └─ DTO = CheckInDTO::fromArray(...)
         *    └─ $result = $service->checkIn($dto)
         *    └─ return CheckInResource($result)
         * 
         * 7️⃣ Service: CheckInService::checkIn()
         *    └─ Busca empresa (Repository)
         *    └─ Verifica check-in duplicado (Repository)
         *    └─ Calcula pontos
         *    └─ DB::transaction {
         *         - Cria check-in
         *         - Cria ponto
         *         - Atualiza user.pontos
         *       }
         *    └─ Log: "Check-in realizado | user_id: 1 | empresa_id: 1"
         *    └─ return ['checkin' => ..., 'pontos_ganhos' => 10]
         * 
         * 8️⃣ Resource: CheckInResource
         *    └─ Formata JSON padronizado
         * 
         * 9️⃣ Response:
         *    {
         *      "success": true,
         *      "message": "Check-in realizado com sucesso!",
         *      "data": {
         *        "checkin": { ... },
         *        "pontos_ganhos": 10,
         *        "pontos_totais": 110
         *      }
         *    }
         */
    }

    /**
     * ========================================
     * COMPARAÇÃO: Antes vs Depois
     * ========================================
     */
    public function comparacao()
    {
        // ❌ ANTES (100 linhas, acoplado, não testável)
        /*
        class AuthController extends Controller {
            public function register(Request $request) {
                $validator = Validator::make($request->all(), [
                    'nome' => 'required|string|max:255',
                    'email' => 'required|email|unique:users',
                    'senha' => 'required|min:6',
                ]);
                
                if ($validator->fails()) {
                    return response()->json([...], 422);
                }
                
                if (User::where('email', $request->email)->exists()) {
                    return response()->json([...], 400);
                }
                
                $user = User::create([
                    'name' => $request->nome,
                    'email' => $request->email,
                    'password' => Hash::make($request->senha),
                ]);
                
                $token = $user->createToken('auth_token')->plainTextToken;
                
                Log::info('Usuário registrado', ['user_id' => $user->id]);
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'token' => $token,
                        'user' => [
                            'id' => $user->id,
                            'nome' => $user->name,
                            'email' => $user->email,
                            ...
                        ]
                    ]
                ], 201);
            }
        }
        */
        
        // ✅ AGORA (15 linhas, desacoplado, testável)
        /*
        class AuthController extends Controller {
            public function __construct(
                private AuthService $authService
            ) {}
            
            public function register(RegisterRequest $request) {
                $dto = RegisterDTO::fromArray($request->validated());
                $result = $this->authService->register($dto);
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'token' => $result['token'],
                        'user' => new UserResource($result['user'])
                    ]
                ], 201);
            }
        }
        */
        
        // BENEFÍCIOS:
        // ✅ Controller: 15 linhas (vs 100)
        // ✅ Validação: FormRequest reutilizável
        // ✅ Lógica: Service testável isoladamente
        // ✅ Dados: DTO imutável e tipado
        // ✅ Response: Resource padronizado
        // ✅ Logs: Automáticos no Service
    }
}

/**
 * ========================================
 * COMO ADICIONAR NOVAS FEATURES
 * ========================================
 * 
 * Exemplo: Sistema de Cupons
 * 
 * 1. Criar DTO
 *    app/DTOs/Cupom/ResgatarCupomDTO.php
 * 
 * 2. Criar Repository
 *    app/Repositories/CupomRepository.php
 * 
 * 3. Criar Service
 *    app/Services/CupomService.php
 * 
 * 4. Criar FormRequest
 *    app/Http/Requests/Cupom/ResgatarCupomRequest.php
 * 
 * 5. Criar Resource
 *    app/Http/Resources/CupomResource.php
 * 
 * 6. Criar Controller
 *    app/Http/Controllers/Api/CupomController.php
 *    └─ Apenas chama Service e retorna Resource
 * 
 * 7. Adicionar Rota
 *    routes/api.php
 *    Route::post('/cupons/{id}/resgatar', [CupomController::class, 'resgatar']);
 * 
 * 8. Criar Testes
 *    tests/Unit/CupomServiceTest.php
 *    tests/Feature/CupomControllerTest.php
 * 
 * PRONTO! Feature completa, testada e seguindo os padrões!
 */
