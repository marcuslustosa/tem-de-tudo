<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class IdempotencyMiddleware
{
    private const KEY_HEADER = 'X-Idempotency-Key';
    private const ALT_KEY_HEADER = 'Idempotency-Key';
    private const PROCESSING_SUFFIX = ':processing';

    /**
     * @param int|null $ttlSeconds Cached response TTL in seconds
     */
    public function handle(Request $request, Closure $next, ?int $ttlSeconds = null): Response
    {
        if (!$this->isUnsafeMethod($request->method())) {
            return $next($request);
        }

        $ttl = max(60, (int) ($ttlSeconds ?? config('idempotency.default_ttl', 3600)));
        $requiresKey = $this->requiresIdempotencyKey();
        $rawKey = $this->extractRawIdempotencyKey($request);

        if ($rawKey === null) {
            if ($requiresKey) {
                return response()->json([
                    'success' => false,
                    'message' => 'X-Idempotency-Key e obrigatorio para esta operacao em producao.',
                ], 400)->header('X-Idempotency-Status', 'missing');
            }

            $response = $next($request);
            return $response->header('X-Idempotency-Status', 'bypass');
        }

        $idempotencyKey = $this->normalizeIdempotencyKey($rawKey);
        if ($idempotencyKey === null) {
            return response()->json([
                'success' => false,
                'message' => 'X-Idempotency-Key invalido. Use 8-128 caracteres alfanumericos.',
            ], 422)->header('X-Idempotency-Status', 'invalid');
        }

        $cacheKey = $this->buildCacheKey($request, $idempotencyKey);
        $fingerprint = $this->fingerprint($request);

        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            if (($cached['fingerprint'] ?? '') !== $fingerprint) {
                return response()->json([
                    'success' => false,
                    'message' => 'Idempotency-Key reutilizado com payload diferente.',
                ], 409)->header(self::KEY_HEADER, $idempotencyKey);
            }

            return response($cached['content'] ?? '', $cached['status'] ?? 200)
                ->withHeaders($cached['headers'] ?? [])
                ->header('X-Idempotency-Status', 'replay')
                ->header(self::KEY_HEADER, $idempotencyKey);
        }

        $processingKey = $cacheKey . self::PROCESSING_SUFFIX;
        if (!Cache::add($processingKey, true, 30)) {
            return response()->json([
                'success' => false,
                'message' => 'Requisicao identica em processamento. Aguarde alguns segundos.',
            ], 409)->header(self::KEY_HEADER, $idempotencyKey);
        }

        try {
            $response = $next($request);

            if ($response->getStatusCode() < 500) {
                Cache::put($cacheKey, [
                    'status' => $response->getStatusCode(),
                    'content' => $response->getContent(),
                    'headers' => $response->headers->all(),
                    'fingerprint' => $fingerprint,
                ], $ttl);
            }

            return $response
                ->header('X-Idempotency-Status', 'stored')
                ->header(self::KEY_HEADER, $idempotencyKey);
        } finally {
            Cache::forget($processingKey);
        }
    }

    private function isUnsafeMethod(string $method): bool
    {
        return in_array(strtoupper($method), ['POST', 'PUT', 'PATCH', 'DELETE'], true);
    }

    private function requiresIdempotencyKey(): bool
    {
        if (!config('idempotency.enforce_header', true)) {
            return false;
        }

        $appEnv = strtolower((string) config('app.env', app()->environment()));
        $isProduction = $appEnv === 'production';

        if ($isProduction) {
            return (bool) config('idempotency.require_in_production', true);
        }

        return !(bool) config('idempotency.allow_bypass_non_production', true);
    }

    private function extractRawIdempotencyKey(Request $request): ?string
    {
        $value = $request->header(self::KEY_HEADER) ?? $request->header(self::ALT_KEY_HEADER);
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);
        return $value === '' ? null : $value;
    }

    private function normalizeIdempotencyKey(string $value): ?string
    {
        if (strlen($value) < 8 || strlen($value) > 128) {
            return null;
        }

        if (!preg_match('/^[A-Za-z0-9._:-]+$/', $value)) {
            return null;
        }

        return $value;
    }

    private function buildCacheKey(Request $request, string $idempotencyKey): string
    {
        $userPart = (string) ($request->user()?->id ?? $request->ip() ?? 'guest');

        return sprintf(
            'idempotency:v2:%s:%s:%s:%s',
            $userPart,
            strtoupper($request->method()),
            $request->path(),
            $idempotencyKey
        );
    }

    private function fingerprint(Request $request): string
    {
        return hash('sha256', implode('|', [
            strtoupper($request->method()),
            $request->path(),
            (string) $request->getContent(),
        ]));
    }
}

