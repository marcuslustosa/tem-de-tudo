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
        // Forca HTTPS apenas em producao quando habilitado.
        if (config('security.force_https') && app()->environment('production') && !$request->secure()) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        $response = $next($request);

        // Aplica apenas os headers definidos na configuracao central.
        $headers = config('security.security_headers', []);

        if (method_exists($response, 'header')) {
            foreach ($headers as $header => $value) {
                $response->header($header, $value);
            }

            if ($request->secure()) {
                $response->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
            }
        }

        return $response;
    }
}

