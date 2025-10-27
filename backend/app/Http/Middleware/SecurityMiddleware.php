<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Forçar HTTPS em produção
        if (config('security.force_https') && !$request->secure()) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        $response = $next($request);

        // Adicionar cabeçalhos de segurança
        $headers = config('security.security_headers', []);
        
        foreach ($headers as $header => $value) {
            $response->header($header, $value);
        }

        // Content Security Policy
        $csp = "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; connect-src 'self'; font-src 'self'; frame-ancestors 'none';";
        $response->header('Content-Security-Policy', $csp);

        // HSTS Header para HTTPS
        if ($request->secure()) {
            $response->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // CORS headers para API
        if ($request->is('api/*')) {
            $response->header('Access-Control-Allow-Origin', '*');
            $response->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept');
            $response->header('Access-Control-Max-Age', '86400');
            
            // Handle preflight OPTIONS requests
            if ($request->getMethod() === 'OPTIONS') {
                return response('', 200);
            }
        }

        return $response;
    }
}