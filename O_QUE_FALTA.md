# 📋 O QUE FALTA IMPLEMENTAR - Roadmap Completo

**Última atualização:** 06/04/2026  
**Status atual:** 20% concluído

---

## 🔥 URGENTE - FAZER HOJE (30 minutos)

### 1. ⚠️ Trocar Senha do Banco PostgreSQL (5 min)
**Status:** ⏸️ Pendente (só você pode fazer)  
**Como fazer:**
- Render Dashboard → PostgreSQL → Reset Password
- Atualizar `.env` de produção com nova senha
- Detalhes: [ACOES_IMEDIATAS.md](ACOES_IMEDIATAS.md#1-trocar-senha-do-banco-5-minutos--crítico)

### 2. 🔧 Registrar Middleware de Cache (5 min)
**Status:** ❌ NÃO FEITO  
**Arquivo:** `backend/bootstrap/app.php`

**Adicionar na linha 31** (dentro de `$middleware->alias([...])`):
```php
$middleware->alias([
    'auth.sanctum' => \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
    'sanctum.auth' => \App\Http\Middleware\SanctumMiddleware::class,
    'admin.permission' => \App\Http\Middleware\AdminPermissionMiddleware::class,
    'role.permission' => \App\Http\Middleware\RolePermissionMiddleware::class,
    'security' => \App\Http\Middleware\SecurityMiddleware::class,
    'cache.response' => \App\Http\Middleware\CacheResponse::class, // ⬅️ ADICIONAR ESTA LINHA
]);
```

### 3. 📦 Aplicar Cache nas Rotas (10 min)
**Status:** ❌ NÃO FEITO  
**Arquivo:** `backend/routes/api.php`

**Encontrar e modificar estas rotas:**

```php
// ANTES (sem cache)
Route::get('/empresas', [EmpresaController::class, 'index']);

// DEPOIS (com cache de 5 minutos)
Route::get('/empresas', [EmpresaController::class, 'index'])
    ->middleware('cache.response:300');
```

**Rotas prioritárias para cachear:**
- ✅ `GET /empresas` → 5 min (300s)
- ✅ `GET /empresas/categoria/{categoria}` → 10 min (600s)
- ✅ `GET /categorias` → 1 hora (3600s)
- ✅ `GET /admin/totals` → 1 hora (3600s)
- ✅ `GET /banners` → 30 min (1800s)

### 4. 🏗️ Rodar Build de Assets (5 min)
**Status:** ❌ NÃO FEITO  
**Como fazer:**
```powershell
cd C:\Users\X472795\Desktop\tem-de-tudo\tem-de-tudo\backend
.\build-assets.ps1
```

**Resultado esperado:**
- Gera `public/dist/stitch-app.min.js` (~50KB ao invés de 150KB)
- Redução de 67% no tamanho do arquivo

### 5. 📤 Commit e Push (5 min)
**Status:** ⏸️ Você vai fazer  
**Comandos:**
```bash
git status
git add .
git commit -m "🔒 SECURITY: Remove exposed credentials and add optimizations"
git push origin main
```

---

## ⚡ PERFORMANCE - SEMANA 1 (6-8 horas)

### 6. 🗄️ Adicionar Cache em Controllers (2h)
**Status:** ❌ NÃO FEITO  
**Prioridade:** Alta

Arquivos para modificar:
- `backend/app/Http/Controllers/EmpresaController.php`
- `backend/app/Http/Controllers/AdminController.php`
- `backend/app/Http/Controllers/CategoriaController.php`

**Exemplo de implementação:**

```php
// Em EmpresaController.php
use Illuminate\Support\Facades\Cache;

public function index()
{
    $empresas = Cache::remember('empresas.ativas', 300, function () {
        return Empresa::where('status', 'ativo')
            ->with(['categoria'])
            ->get();
    });
    
    return response()->json(['empresas' => $empresas]);
}
```

### 7. 🏃 Otimizar Queries do Banco (3h)
**Status:** ❌ NÃO FEITO  
**Prioridade:** Alta

#### 7.1. Adicionar Índices (1h)
Criar migration: `php artisan make:migration add_performance_indexes`

```php
Schema::table('users', function (Blueprint $table) {
    $table->index('email'); // já existe unique, mas verificar
    $table->index(['tipo_usuario', 'status']);
});

Schema::table('empresas', function (Blueprint $table) {
    $table->index(['status', 'categoria']);
    $table->index('user_id');
});

Schema::table('pontos', function (Blueprint $table) {
    $table->index(['user_id', 'created_at']);
    $table->index('empresa_id');
});

Schema::table('cupons', function (Blueprint $table) {
    $table->index(['user_id', 'status', 'validade']);
});
```

#### 7.2. Eager Loading (1h)
Encontrar queries N+1 e adicionar `with()`:

```php
// ANTES (N+1 queries)
$empresas = Empresa::all();
foreach ($empresas as $empresa) {
    echo $empresa->user->name; // +1 query por empresa
}

// DEPOIS (2 queries)
$empresas = Empresa::with('user')->get();
```

#### 7.3. Remover hasEmpresasTable() Runtime Check (1h)
**Arquivo:** `backend/app/Http/Controllers/EmpresaController.php`

**ANTES:**
```php
if (!$this->hasEmpresasTable()) {
    return response()->json(['error' => 'Tabela empresas não existe'], 500);
}
```

**DEPOIS:** Remover completamente (tabela sempre existe em produção)

### 8. 🖼️ Migrar QR Codes de Base64 para Filesystem (3h)
**Status:** ❌ NÃO FEITO  
**Prioridade:** Média

**Problema atual:**
- QR codes salvos como base64 TEXT no campo `qr_image`
- Cada QR ~500KB armazenado no PostgreSQL
- Queries lentas ao buscar empresas

**Solução:**

#### 8.1. Criar migration (30 min)
```php
Schema::table('empresas', function (Blueprint $table) {
    $table->string('qr_path')->nullable()->after('qr_code');
});
```

#### 8.2. Modificar QRCodeService (1h)
```php
public function gerarQRCodeEmpresa(Empresa $empresa): bool
{
    $qrCode = QrCode::format('png')
        ->size(500)
        ->generate($empresa->qr_code);
    
    // Salvar no filesystem ao invés de base64
    $filename = "qrcodes/empresa_{$empresa->id}.png";
    Storage::disk('public')->put($filename, $qrCode);
    
    $empresa->update(['qr_path' => $filename]);
    
    return true;
}
```

#### 8.3. Script de migração de dados (1h)
Migrar QR codes existentes para filesystem:
```bash
php artisan make:command migrate:qrcodes
```

#### 8.4. Atualizar controllers (30 min)
```php
// Retornar URL pública
return response()->json([
    'qr_code_url' => Storage::url($empresa->qr_path)
]);
```

---

## 🎨 VISUAL - SEMANA 1 (4-6 horas)

### 9. 🔤 Substituir JS Minificado nas Páginas (1h)
**Status:** ❌ NÃO FEITO  
**Após rodar build-assets.ps1**

Substituir em TODAS as 30 páginas HTML:

**Buscar e substituir:**
```
BUSCAR: /js/stitch-app.js?v=20260401-stab14
SUBSTITUIR: /dist/stitch-app.min.js?v=20260406-prod
```

**PowerShell script:**
```powershell
cd backend/public
Get-ChildItem *.html -Recurse | ForEach-Object {
    (Get-Content $_.FullName) `
        -replace '/js/stitch-app\.js\?v=20260401-stab14', '/dist/stitch-app.min.js?v=20260406-prod' |
    Set-Content $_.FullName
}
```

### 10. 🎨 Padronizar Cores VIPUS (2h)
**Status:** ⏸️ Aguardando referência VIPUS  
**Prioridade:** Média

**Cores atuais:**
- Primary: `#7A2C8F` (purple)
- Secondary: `#E10098` (magenta)
- Dark: `#003B49` (teal)

**Referência:** Seed.js menciona `#9b59b6` (roxo do VIPUS)

**Aguardando:**
- Screenshots do app VIPUS
- Logo oficial
- Paleta de cores completa

### 11. 📱 Testar Responsividade (1h)
**Status:** ❌ NÃO FEITO  

Abrir cada página em:
- Desktop (1920x1080)
- Tablet (768px)
- Mobile (375px)

**Páginas críticas:**
1. [meus_pontos.html](backend/public/meus_pontos.html) (cliente)
2. [dashboard_parceiro.html](backend/public/dashboard_parceiro.html) (empresa)
3. [dashboard_admin_master.html](backend/public/dashboard_admin_master.html) (admin)

### 12. 🧹 Remover Funções decodeMojibake (30 min)
**Status:** ⏸️ Aguardando correção UTF-8 no banco  
**Após aplicar DB_CHARSET=utf8mb4**

**Arquivo:** `backend/public/js/stitch-app.js` (linhas 87-101)

Pode remover após confirmar que charset está correto:
```javascript
// REMOVER esta função (linhas 87-101)
function decodeMojibake(value) {
  // ... código de workaround UTF-8
}
```

---

## 🧪 QUALIDADE - SEMANA 2 (8-10 horas)

### 13. ✅ Criar Testes Automatizados (6h)
**Status:** ❌ NÃO FEITO (0% coverage)  
**Prioridade:** Média

**Testes prioritários:**

#### 13.1. Testes de API (3h)
```bash
php artisan make:test AuthTest
php artisan make:test PontosTest
php artisan make:test EmpresaTest
```

**Exemplo:**
```php
public function test_user_can_register_as_cliente()
{
    $response = $this->postJson('/api/auth/register', [
        'nome' => 'Teste Cliente',
        'email' => 'teste@email.com',
        'password' => 'senha123',
        'tipo_usuario' => 'cliente',
    ]);

    $response->assertStatus(201)
             ->assertJsonStructure(['token', 'user']);
}
```

#### 13.2. Testes de Integração (2h)
- Check-in gerando pontos
- Resgate de cupom diminuindo saldo
- Bônus de aniversário

#### 13.3. Testes de Segurança (1h)
- Tentativa de acesso sem token
- Tentativa de acessar rota admin como cliente
- SQL injection nos inputs

### 14. 🔍 Configurar Análise Estática (2h)
**Status:** ❌ NÃO FEITO  

#### 14.1. PHP Stan (1h)
```bash
composer require --dev phpstan/phpstan
./vendor/bin/phpstan analyse app
```

#### 14.2. PHP CS Fixer (1h)
```bash
composer require --dev friendsofphp/php-cs-fixer
./vendor/bin/php-cs-fixer fix app --dry-run
```

---

## 🚀 DEPLOY - SEMANA 2 (4 horas)

### 15. 📊 Configurar Monitoramento (2h)
**Status:** ❌ NÃO FEITO  

#### 15.1. Sentry (1h)
```bash
composer require sentry/sentry-laravel
php artisan sentry:publish --dsn=<SEU_DSN>
```

Já tem a variável `SENTRY_DSN` no .env.example

#### 15.2. UptimeRobot (30 min)
- Criar conta: https://uptimerobot.com
- Monitor: https://tem-de-tudo.onrender.com/up
- Alerta: email quando down > 2 min

#### 15.3. Logs Estruturados (30 min)
```php
// config/logging.php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['daily', 'sentry'],
    ],
],
```

### 16. 🔐 Hardening de Segurança (2h)
**Status:** ❌ NÃO FEITO  

#### 16.1. Rate Limiting (30 min)
Já existe em AuthController, mas adicionar em outras rotas:

```php
Route::middleware(['throttle:60,1'])->group(function () {
    Route::get('/empresas', [EmpresaController::class, 'index']);
});
```

#### 16.2. CORS Restritivo (30 min)
```php
// config/cors.php
'allowed_origins' => ['https://tem-de-tudo.onrender.com'],
'allowed_origins_patterns' => [],
'supports_credentials' => true,
```

#### 16.3. Headers de Segurança (1h)
Criar middleware SecurityHeaders:
```php
X-Frame-Options: DENY
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
Strict-Transport-Security: max-age=31536000
Content-Security-Policy: default-src 'self'
```

---

## 📚 DOCUMENTAÇÃO - SEMANA 2 (2 horas)

### 17. 📖 README.md Completo (1h)
**Status:** ⏸️ Parcialmente feito  

Adicionar seções:
- [ ] Como rodar localmente
- [ ] Endpoints da API (Swagger/OpenAPI?)
- [ ] Comandos úteis
- [ ] Deploy (link para DEPLOY_CHECKLIST.md)
- [ ] Contribuindo

### 18. 📝 Documentar API (1h)
**Status:** ❌ NÃO FEITO  

**Opção 1:** Swagger/OpenAPI
```bash
composer require darkaonline/l5-swagger
php artisan l5-swagger:generate
```

**Opção 2:** Markdown simples
Criar `API.md` com todos os endpoints

---

## 🎯 RESUMO DE PRIORIDADES

### 🔥 HOJE (30 min - URGENTE)
1. ✅ Trocar senha do banco
2. ✅ Registrar middleware de cache
3. ✅ Aplicar cache nas rotas
4. ✅ Rodar build de assets
5. ✅ Commit e push

### ⚡ SEMANA 1 - Performance (12-16h)
6. Cache em controllers (2h)
7. Otimizar queries (3h)
8. Migrar QR codes (3h)
9. Substituir JS minificado (1h)
10. Padronizar cores (2h - aguardando VIPUS)
11. Testar responsividade (1h)

### 🧪 SEMANA 2 - Qualidade (14-16h)
13. Testes automatizados (6h)
14. Análise estática (2h)
15. Monitoramento (2h)
16. Hardening (2h)
17. README (1h)
18. Documentar API (1h)

---

## ✅ CHECKLIST DE PROGRESSO

**Total estimado:** 26-32 horas de trabalho

- [x] ✅ Segurança: Credenciais removidas (FEITO)
- [x] ✅ Segurança: Logs sanitizados (FEITO)
- [x] ✅ Middleware criado (FEITO)
- [x] ✅ Scripts de build (FEITO)
- [x] ✅ Documentação inicial (FEITO)
- [ ] ❌ Middleware registrado
- [ ] ❌ Cache aplicado nas rotas
- [ ] ❌ Build executado
- [ ] ❌ Senha do banco alterada
- [ ] ❌ Cache em controllers
- [ ] ❌ Índices no banco
- [ ] ❌ QR codes migrados
- [ ] ❌ JS minificado nas páginas
- [ ] ❌ Testes criados
- [ ] ❌ Monitoramento configurado

**Progresso:** 5/20 = 25% concluído

---

**Próximo passo:** Começar pelos 5 itens URGENTES de hoje (30 min)
