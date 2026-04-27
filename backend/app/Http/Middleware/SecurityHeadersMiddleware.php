<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        if (app()->environment('production')) {
            $response->headers->set('Content-Security-Policy', $this->buildCspValue());
        }

        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        return $response;
    }

    private function buildCspValue(): string
    {
        $connectSources = ["'self'", 'https://*.vipus.com.br'];
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        if (is_string($appHost) && $appHost !== '') {
            $connectSources[] = 'https://' . $appHost;
        }

        return implode(' ', [
            "default-src 'self';",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com;",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.tailwindcss.com;",
            "img-src 'self' data: https: blob:;",
            "font-src 'self' data: https://fonts.gstatic.com;",
            'connect-src ' . implode(' ', array_unique($connectSources)) . ';',
            "frame-ancestors 'self';",
            "base-uri 'self';",
            "form-action 'self';",
        ]);
    }
}

