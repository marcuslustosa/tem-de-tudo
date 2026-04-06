<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de cache simples para respostas GET públicas
 * 
 * Uso em routes/api.php:
 * Route::get('/empresas', [EmpresaController::class, 'index'])
 *     ->middleware('cache.response:300'); // 5 minutos
 */
class CacheResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  int  $ttl  Time to live em segundos (default: 60)
     */
    public function handle(Request $request, Closure $next, int $ttl = 60): Response
    {
        // Só cachear GET requests
        if (!$request->isMethod('GET')) {
            return $next($request);
        }

        // Gerar chave de cache única baseada na URL + query params + user
        $cacheKey = $this->getCacheKey($request);

        // Tentar buscar do cache
        $cachedResponse = Cache::get($cacheKey);
        
        if ($cachedResponse !== null) {
            return response($cachedResponse['content'], $cachedResponse['status'])
                ->withHeaders($cachedResponse['headers'])
                ->header('X-Cache', 'HIT');
        }

        // Processar request normalmente
        $response = $next($request);

        // Só cachear respostas 200 OK
        if ($response->status() === 200) {
            Cache::put($cacheKey, [
                'content' => $response->getContent(),
                'status' => $response->status(),
                'headers' => $response->headers->all(),
            ], $ttl);
        }

        return $response->header('X-Cache', 'MISS');
    }

    /**
     * Gerar chave de cache única
     */
    private function getCacheKey(Request $request): string
    {
        $user = $request->user();
        $userId = $user ? $user->id : 'guest';
        $url = $request->fullUrl();
        
        return 'response:' . md5($userId . ':' . $url);
    }
}
