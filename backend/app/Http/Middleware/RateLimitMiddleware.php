<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{
    protected $limiter;

    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $limit = '60:1'): Response
    {
        // Parse limit (requests:minutes)
        [$maxAttempts, $decayMinutes] = explode(':', $limit);

        // Key única por IP + rota
        $key = $this->resolveRequestSignature($request);

        // Checa rate limit
        if ($this->limiter->tooManyAttempts($key, (int) $maxAttempts)) {
            $retryAfter = $this->limiter->availableIn($key);
            
            return response()->json([
                'error' => 'Muitas tentativas. Tente novamente em alguns instantes.',
                'retry_after' => $retryAfter,
            ], 429)->header('Retry-After', $retryAfter);
        }

        // Incrementa contador
        $this->limiter->hit($key, (int) $decayMinutes * 60);

        $response = $next($request);

        // Adiciona headers informativos
        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $this->limiter->remaining($key, (int) $maxAttempts),
        ]);

        return $response;
    }

    /**
     * Resolve request signature (IP + rota + user)
     */
    protected function resolveRequestSignature(Request $request): string
    {
        $userId = $request->user()?->id ?? 'guest';
        
        return sha1(
            $request->ip() . '|' . 
            $request->path() . '|' . 
            $userId
        );
    }
}
