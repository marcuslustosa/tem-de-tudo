# 🏗️ Arquitetura Enterprise - Tem de Tudo

## 📋 Índice
- [Visão Geral](#visão-geral)
- [Padrões Implementados](#padrões-implementados)
- [Estrutura de Diretórios](#estrutura-de-diretórios)
- [Fluxo de Dados](#fluxo-de-dados)
- [Boas Práticas](#boas-práticas)
- [Performance](#performance)
- [Testes](#testes)

---

## 🎯 Visão Geral

Este projeto implementa uma **arquitetura enterprise-level** com:
- ✅ **SOLID Principles** (Single Responsibility, Open/Closed, Liskov Substitution, Interface Segregation, Dependency Inversion)
- ✅ **Clean Architecture** (separação de camadas, independência de frameworks)
- ✅ **Repository Pattern** (abstração do acesso a dados)
- ✅ **Service Layer** (lógica de negócio isolada)
- ✅ **DTOs** (Data Transfer Objects para validação e transferência)
- ✅ **API Resources** (padronização de responses JSON)
- ✅ **Form Requests** (validação robusta)
- ✅ **Middlewares** (segurança, logs, sanitização)
- ✅ **Dependency Injection** (IoC Container do Laravel)

---

## 🏛️ Padrões Implementados

### 1. SOLID Principles

#### ✅ Single Responsibility Principle (SRP)
Cada classe tem **uma única responsabilidade**:
- `AuthController` → Apenas recebe requisições HTTP
- `AuthService` → Apenas lógica de autenticação
- `UserRepository` → Apenas acesso ao banco de usuários

```php
// ❌ ANTES (Controller fazia tudo)
class AuthController {
    public function register(Request $request) {
        // Validação
        // Criação do usuário
        // Hash da senha
        // Geração de token
        // Log
        // Response
    }
}

// ✅ AGORA (Responsabilidades separadas)
class AuthController {
    public function register(RegisterRequest $request) {
        $dto = RegisterDTO::fromArray($request->validated());
        $result = $this->authService->register($dto);
        return new UserResource($result['user']);
    }
}
```

#### ✅ Open/Closed Principle (OCP)
Aberto para extensão, fechado para modificação:

```php
// Fácil adicionar novos repositórios sem modificar existentes
interface UserRepositoryInterface {
    public function create(array $data): User;
    public function findByEmail(string $email): ?User;
}

class UserRepository implements UserRepositoryInterface {
    // Implementação PostgreSQL
}

// Posso criar EloquentUserRepository, MongoUserRepository, etc.
```

#### ✅ Liskov Substitution Principle (LSP)
Subclasses podem substituir classes pai:

```php
// Todos os Resources seguem o mesmo contrato
class UserResource extends JsonResource { }
class EmpresaResource extends JsonResource { }
class CheckInResource extends JsonResource { }
```

#### ✅ Interface Segregation Principle (ISP)
Interfaces específicas ao invés de genéricas:

```php
// ❌ Ruim
interface Repository {
    public function create();
    public function update();
    public function delete();
    public function addPoints(); // Nem todo repository precisa disso!
}

// ✅ Bom
interface UserRepositoryInterface {
    public function addPoints(User $user, int $points): bool;
}
```

#### ✅ Dependency Inversion Principle (DIP)
Dependa de abstrações, não de implementações:

```php
// ✅ Service depende da interface, não da implementação concreta
class AuthService {
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}
}
```

---

### 2. Clean Architecture (Arquitetura Limpa)

```
┌─────────────────────────────────────────────────────────┐
│                    PRESENTATION LAYER                    │
│   (Controllers, Requests, Resources, Middlewares)       │
├─────────────────────────────────────────────────────────┤
│                    APPLICATION LAYER                     │
│        (Services, DTOs, Use Cases, Business Logic)      │
├─────────────────────────────────────────────────────────┤
│                      DOMAIN LAYER                        │
│            (Models, Entities, Value Objects)            │
├─────────────────────────────────────────────────────────┤
│                  INFRASTRUCTURE LAYER                    │
│         (Repositories, Database, External APIs)         │
└─────────────────────────────────────────────────────────┘
```

**Benefícios:**
- ✅ Lógica de negócio **independente** do framework
- ✅ Fácil trocar banco de dados (PostgreSQL → MongoDB)
- ✅ Fácil adicionar novos métodos de autenticação
- ✅ Testável (cada camada pode ser testada isoladamente)

---

## 📁 Estrutura de Diretórios

```
backend/app/
├── DTOs/                           # Data Transfer Objects
│   ├── Auth/
│   │   ├── RegisterDTO.php        # Dados de cadastro
│   │   └── LoginDTO.php           # Dados de login
│   ├── CheckIn/
│   │   └── CheckInDTO.php         # Dados de check-in
│   └── User/
│       └── UpdateProfileDTO.php   # Dados de atualização
│
├── Services/                       # Lógica de Negócio
│   ├── AuthService.php            # Autenticação
│   └── CheckInService.php         # Check-ins
│
├── Repositories/                   # Acesso a Dados
│   ├── UserRepository.php
│   ├── CheckInRepository.php
│   └── EmpresaRepository.php
│
├── Http/
│   ├── Controllers/Api/           # Controllers (apenas ponte)
│   │   ├── AuthControllerClean.php
│   │   └── CheckInControllerClean.php
│   │
│   ├── Requests/                  # Validação de Entrada
│   │   ├── Auth/
│   │   │   ├── RegisterRequest.php
│   │   │   └── LoginRequest.php
│   │   └── CheckIn/
│   │       └── CheckInRequest.php
│   │
│   ├── Resources/                 # Formatação de Saída
│   │   ├── UserResource.php
│   │   ├── EmpresaResource.php
│   │   ├── CheckInResource.php
│   │   └── PromocaoResource.php
│   │
│   └── Middleware/                # Middlewares Customizados
│       ├── CheckUserActive.php    # Verifica se usuário está ativo
│       ├── LogApiRequests.php     # Log de requisições
│       └── SanitizeInput.php      # Sanitização de entrada
│
└── Models/                        # Eloquent Models
    ├── User.php
    ├── Empresa.php
    ├── CheckIn.php
    └── Promocao.php
```

---

## 🔄 Fluxo de Dados

### Exemplo: Cadastro de Usuário

```
1. HTTP Request
   POST /api/register
   { "nome": "João", "email": "joao@email.com", "senha": "123456" }
   
2. Middleware Pipeline
   ├── SanitizeInput (limpa dados)
   └── RegisterRequest (valida dados)
   
3. Controller (Thin - apenas ponte)
   AuthController::register(RegisterRequest $request)
   └── Cria DTO a partir dos dados validados
   
4. DTO (Data Transfer Object)
   RegisterDTO::fromArray($request->validated())
   └── Objeto imutável com dados validados
   
5. Service (Lógica de Negócio)
   AuthService::register(RegisterDTO $dto)
   ├── Verifica se email existe
   ├── Chama Repository para criar usuário
   ├── Gera token
   └── Registra log
   
6. Repository (Acesso a Dados)
   UserRepository::create($data)
   ├── Hash da senha
   └── INSERT no PostgreSQL
   
7. Resource (Formatação de Saída)
   UserResource::make($user)
   └── Transforma Model em JSON padronizado
   
8. HTTP Response
   {
     "success": true,
     "data": {
       "token": "...",
       "user": { ... }
     }
   }
```

---

## 💡 Boas Práticas Implementadas

### 1. DTOs (Data Transfer Objects)

**Por que usar?**
- ✅ Tipagem forte (PHP 8.1+ readonly properties)
- ✅ Imutabilidade (dados não podem ser alterados)
- ✅ Validação em camadas
- ✅ Separação entre dados de entrada e modelo

```php
// ✅ COM DTO
$dto = RegisterDTO::fromArray($request->validated());
$user = $this->authService->register($dto);
// $dto->email → garantido que é string válida
// $dto é imutável, não pode ser alterado acidentalmente

// ❌ SEM DTO
$user = $this->authService->register($request->all());
// $request['email'] → pode ser qualquer coisa
// Pode ser modificado acidentalmente
```

### 2. Repository Pattern

**Por que usar?**
- ✅ Abstrai acesso ao banco de dados
- ✅ Fácil mockar em testes
- ✅ Centraliza queries complexas
- ✅ Pode trocar ORM sem quebrar código

```php
// ✅ COM Repository
class CheckInService {
    public function __construct(
        private CheckInRepository $checkInRepository
    ) {}
    
    public function checkIn(CheckInDTO $dto) {
        if ($this->checkInRepository->hasCheckedInToday(...)) {
            throw new Exception('Já fez check-in hoje');
        }
        return $this->checkInRepository->create([...]);
    }
}

// ❌ SEM Repository (acoplado ao Eloquent)
class CheckInService {
    public function checkIn(CheckInDTO $dto) {
        if (CheckIn::where(...)->exists()) { // ⚠️ Acoplado!
            throw new Exception('Já fez check-in hoje');
        }
        return CheckIn::create([...]); // ⚠️ Acoplado!
    }
}
```

### 3. Service Layer

**Por que usar?**
- ✅ Lógica de negócio **fora** do controller
- ✅ Controllers ficam com 5-10 linhas (thin controllers)
- ✅ Reutilizável (web, API, CLI, jobs)
- ✅ Testável isoladamente

```php
// ✅ COM Service (Thin Controller)
class AuthController {
    public function register(RegisterRequest $request) {
        $dto = RegisterDTO::fromArray($request->validated());
        $result = $this->authService->register($dto);
        return response()->json([...]);
    }
}

// ❌ SEM Service (Fat Controller - 100+ linhas)
class AuthController {
    public function register(Request $request) {
        // 10 linhas de validação
        // 20 linhas de lógica de negócio
        // 15 linhas de criação de usuário
        // 10 linhas de geração de token
        // 5 linhas de log
        // 10 linhas de response
        // = Controller de 70+ linhas impossível de testar
    }
}
```

### 4. Form Requests

**Por que usar?**
- ✅ Validação **antes** de chegar no controller
- ✅ Mensagens de erro personalizadas
- ✅ Código limpo e organizado
- ✅ Reutilizável

```php
// ✅ COM Form Request
public function register(RegisterRequest $request) {
    // Dados já validados aqui!
    $dto = RegisterDTO::fromArray($request->validated());
    // ...
}

// ❌ SEM Form Request
public function register(Request $request) {
    $validator = Validator::make($request->all(), [
        'nome' => 'required|string|max:255',
        'email' => 'required|email|unique:users',
        // ... 20 linhas de validação
    ]);
    if ($validator->fails()) {
        return response()->json([...], 422);
    }
    // ...
}
```

### 5. API Resources

**Por que usar?**
- ✅ Padroniza formato JSON
- ✅ Controla quais campos expor
- ✅ Formatação consistente
- ✅ Versionamento de API facilitado

```php
// ✅ COM Resource
return new UserResource($user);
// Sempre retorna:
// { "id": 1, "nome": "João", "pontos": 100, ... }

// ❌ SEM Resource
return $user->toArray();
// Pode expor campos sensíveis: password_hash, tokens, etc.
// Formato inconsistente
```

---

## ⚡ Performance

### Melhorias Implementadas

#### 1. Eager Loading (N+1 Prevention)
```php
// ✅ OTIMIZADO
$checkIns = CheckIn::with('empresa')->get();
// 2 queries: SELECT * FROM check_ins + SELECT * FROM empresas WHERE id IN (...)

// ❌ PROBLEMA N+1
$checkIns = CheckIn::all();
foreach ($checkIns as $checkIn) {
    echo $checkIn->empresa->nome; // Query para CADA check-in!
}
// 101 queries se tiver 100 check-ins!
```

#### 2. Database Transactions
```php
DB::beginTransaction();
try {
    $checkIn = $this->checkInRepository->create([...]);
    $this->userRepository->addPoints($user, $points);
    Ponto::create([...]);
    DB::commit();
} catch (Exception $e) {
    DB::rollBack(); // ✅ Rollback automático
    throw $e;
}
```

#### 3. Logs Estruturados
```php
Log::info('Check-in realizado', [
    'user_id' => $dto->user_id,
    'empresa_id' => $dto->empresa_id,
    'pontos_ganhos' => $pontosGanhos,
    'timestamp' => now()
]);
// ✅ Fácil buscar no CloudWatch, Kibana, etc.
```

#### 4. Índices de Banco
```php
// migrations/
Schema::table('check_ins', function (Blueprint $table) {
    $table->index(['user_id', 'created_at']); // ✅ Busca rápida
    $table->index(['empresa_id']);
});
```

---

## 🧪 Como Testar

### Unit Tests (Services)
```php
class AuthServiceTest extends TestCase {
    public function test_register_creates_user() {
        $userRepo = Mockery::mock(UserRepository::class);
        $userRepo->shouldReceive('create')->once()->andReturn(new User);
        
        $service = new AuthService($userRepo);
        $dto = new RegisterDTO('João', 'joao@test.com', '123456');
        
        $result = $service->register($dto);
        
        $this->assertNotNull($result['user']);
        $this->assertNotNull($result['token']);
    }
}
```

### Integration Tests (Controllers)
```php
class AuthControllerTest extends TestCase {
    public function test_register_endpoint() {
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
    }
}
```

---

## 📊 Comparação: Antes vs Depois

| Aspecto | Antes (MVC Básico) | Depois (Enterprise) |
|---------|-------------------|---------------------|
| **Linhas por Controller** | 100-200 linhas | 10-30 linhas |
| **Testabilidade** | Difícil (acoplado) | Fácil (isolado) |
| **Reusabilidade** | Baixa | Alta |
| **Manutenibilidade** | Difícil | Fácil |
| **Performance** | N+1 queries | Otimizado |
| **Segurança** | Básica | Camadas de validação |
| **Logs** | Simples | Estruturados |
| **Escalabilidade** | Limitada | Alta |

---

## 🚀 Próximos Passos

- [ ] **Cache** (Redis para empresas, promoções)
- [ ] **Events & Listeners** (UserRegistered, CheckInCompleted)
- [ ] **Queue Jobs** (envio de emails, notificações)
- [ ] **API Versioning** (v1, v2)
- [ ] **Rate Limiting** avançado
- [ ] **Observability** (Prometheus, Grafana)
- [ ] **Feature Flags** (LaunchDarkly)
- [ ] **Circuit Breaker** (falhas em APIs externas)

---

## 📚 Referências

- [Laravel Best Practices](https://github.com/alexeymezenin/laravel-best-practices)
- [Clean Code PHP](https://github.com/jupeter/clean-code-php)
- [SOLID Principles](https://en.wikipedia.org/wiki/SOLID)
- [Repository Pattern](https://designpatternsphp.readthedocs.io/en/latest/More/Repository/README.html)
- [Uncle Bob - Clean Architecture](https://blog.cleancoder.com/uncle-bob/2012/08/13/the-clean-architecture.html)

---

**Criado com ❤️ para Tem de Tudo | Arquitetura Enterprise-Level**
