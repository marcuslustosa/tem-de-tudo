# 🚀 OTIMIZAÇÕES DE CACHE - Implementação Rápida

## 1. Registrar Middleware de Cache

### Em `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'cache.response' => \App\Http\Middleware\CacheResponse::class,
    ]);
})
```

## 2. Aplicar Cache em Rotas Públicas

### Em `routes/api.php`, adicione o middleware:

```php
// Listar empresas (cachear por 5 minutos)
Route::get('/empresas', [EmpresaController::class, 'index'])
    ->middleware('cache.response:300');

// Empresas por categoria (10 minutos)
Route::get('/empresas/categoria/{categoria}', [EmpresaController::class, 'byCategoria'])
    ->middleware('cache.response:600');

// Categorias disponíveis (1 hora - dados estáticos)
Route::get('/categorias', [CategoriaController::class, 'index'])
    ->middleware('cache.response:3600');
```

## 3. Cache de Queries Pesadas

### Exemplo: EmpresaController.php

**ANTES (sem cache):**
```php
public function index()
{
    $empresas = Empresa::where('status', 'ativo')
        ->with(['categoria', 'pontos'])
        ->get();
    
    return response()->json(['empresas' => $empresas]);
}
```

**DEPOIS (com cache de 5 minutos):**
```php
use Illuminate\Support\Facades\Cache;

public function index()
{
    $empresas = Cache::remember('empresas.ativas', 300, function () {
        return Empresa::where('status', 'ativo')
            ->with(['categoria', 'pontos'])
            ->get();
    });
    
    return response()->json(['empresas' => $empresas]);
}
```

## 4. Invalidar Cache Quando Necessário

### Exemplo: ao criar/atualizar empresa

```php
use Illuminate\Support\Facades\Cache;

public function store(Request $request)
{
    $empresa = Empresa::create($validated);
    
    // Invalidar cache de empresas
    Cache::forget('empresas.ativas');
    Cache::flush(); // ⚠️ Só use se absolutamente necessário
    
    return response()->json(['empresa' => $empresa]);
}
```

## 5. Cache de Totalizadores (Dashboard Admin)

### Em `AdminController.php`:

```php
public function totals()
{
    // Cache por 1 hora (dados de dashboard não precisam ser real-time)
    $totals = Cache::remember('admin.totals', 3600, function () {
        return [
            'usuarios' => User::count(),
            'empresas' => Empresa::count(),
            'pontos_total' => Ponto::sum('pontos'),
            'resgates_mes' => Resgate::whereMonth('created_at', now()->month)->count(),
        ];
    });
    
    return response()->json($totals);
}
```

## 6. Cache de QR Codes (Alta Prioridade)

### PROBLEMA ATUAL:
QR codes são armazenados como base64 TEXT no banco, causando:
- 🐌 Queries lentas (> 500KB por registro)
- 💾 Desperdício de storage
- 🔥 CPU alto para encode/decode

### SOLUÇÃO IMEDIATA (cache em memória):

```php
// Em QRCodeService.php
public function obterQRCode(int $empresaId): ?string
{
    return Cache::remember("qr.empresa.$empresaId", 86400, function () use ($empresaId) {
        $empresa = Empresa::find($empresaId);
        return $empresa?->qr_image;
    });
}
```

### SOLUÇÃO DEFINITIVA (mover para storage):
```php
// Migrar QR codes para filesystem
Storage::disk('public')->put("qrcodes/{$empresaId}.png", $qrPng);
$empresa->qr_path = "qrcodes/{$empresaId}.png";
```

## 7. Verificar se Cache está Funcionando

### Teste via API:

```bash
# Primeira requisição (X-Cache: MISS)
curl -I https://tem-de-tudo.onrender.com/api/empresas

# Segunda requisição (X-Cache: HIT)
curl -I https://tem-de-tudo.onrender.com/api/empresas
```

**Resposta esperada:**
```
X-Cache: HIT
```

## 8. Limpar Cache Manualmente

```bash
# Via Artisan
php artisan cache:clear

# Ou via API (criar endpoint protegido)
Route::post('/admin/cache/clear', function () {
    Cache::flush();
    return response()->json(['message' => 'Cache limpo com sucesso']);
})->middleware('auth:sanctum', 'role:admin');
```

## 9. Monitorar Performance do Cache

### Criar endpoint de status:

```php
Route::get('/cache/stats', function () {
    return response()->json([
        'driver' => config('cache.default'),
        'prefix' => config('cache.prefix'),
        'stores' => array_keys(config('cache.stores')),
    ]);
})->middleware('auth:sanctum');
```

## ⚡ GANHOS ESPERADOS

| Endpoint | Antes | Depois | Melhoria |
|----------|-------|--------|----------|
| GET /empresas | ~450ms | ~15ms | **96%** |
| GET /admin/totals | ~800ms | ~5ms | **99%** |
| GET QR code | ~200ms | ~2ms | **99%** |

## 🎯 PRIORIDADE DE IMPLEMENTAÇÃO

1. ✅ **Criar middleware** (já feito → CacheResponse.php)
2. 🔥 **Cachear listagem de empresas** (5 min)
3. 🔥 **Cachear totais do dashboard** (1 hora)
4. ⚡ **Cachear QR codes** (24 horas)
5. 📊 **Adicionar header X-Cache** (debug)

---

**Tempo estimado de implementação:** 30-45 minutos  
**Ganho de performance:** 80-95% em endpoints GET
