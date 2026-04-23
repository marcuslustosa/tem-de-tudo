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

        // Prevent clickjacking
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // XSS Protection
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Referrer Policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions Policy (antes Feature-Policy)
        $response->headers->set('Permissions-Policy', 'geolocation=(self), microphone=(), camera=(), payment=()');

        // Content Security Policy (CSP)
        if (config('app.env') === 'production') {
            $response->headers->set('Content-Security-Policy', 
                "default-src 'self'; " .
                "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com; " .
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
                "img-src 'self' data: https: blob:; " .
                "font-src 'self' data: https://fonts.gstatic.com; " .
                "connect-src 'self' https://*.vipus.com.br; " .
                "frame-ancestors 'self'; " .
                "base-uri 'self'; " .
                "form-action 'self';"
            );
        }

        // HTTPS Strict Transport Security (HSTS)
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // Certificate Transparency (Expect-CT)
        if ($request->secure() && config('app.env') === 'production') {
            $response->headers->set('Expect-CT', 'max-age=86400, enforce');
        }

        // Network Error Logging (NEL)
        if (config('app.env') === 'production') {
            $response->headers->set('NEL', json_encode([
                'report_to' => 'default',
                'max_age' => 2592000,
                'include_subdomains' => true
            ]));

            // Report-To header para NEL e CSP reports
            $response->headers->set('Report-To', json_encode([
                'group' => 'default',
                'max_age' => 2592000,
                'endpoints' => [
                    ['url' => config('app.url') . '/api/csp-report']
                ],
                'include_subdomains' => true
            ]));
        }

        // Cross-Origin headers
        $response->headers->set('Cross-Origin-Embedder-Policy', 'require-corp');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');

        return $response;
    }
}
