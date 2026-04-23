<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RequestLogger
{
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);
        
        // Contexto da requisição
        $context = [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_id' => auth()->id(),
            'user_agent' => $request->userAgent(),
        ];
        
        // Log início da requisição
        Log::info('Request started', $context);
        
        // Processa requisição
        $response = $next($request);
        
        // Calcula tempo de resposta
        $duration = (microtime(true) - $startTime) * 1000; // ms
        
        // Log fim da requisição
        Log::info('Request completed', array_merge($context, [
            'status' => $response->status(),
            'duration_ms' => round($duration, 2),
        ]));
        
        // Adiciona header com tempo de resposta
        $response->headers->set('X-Response-Time', round($duration, 2) . 'ms');
        
        // Alerta se request lenta (> 1s)
        if ($duration > 1000) {
            Log::warning('Slow request detected', array_merge($context, [
                'duration_ms' => round($duration, 2),
            ]));
        }
        
        return $response;
    }
}
