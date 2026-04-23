<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RequestLogger
{
    private const SKIP_PATHS = [
        'api/ping',
        'api/health',
        'api/metrics',
    ];

    public function handle(Request $request, Closure $next)
    {
        $requestId = $request->headers->get('X-Request-Id') ?: (string) Str::uuid();
        $request->headers->set('X-Request-Id', $requestId);

        $startTime = microtime(true);
        $context = $this->buildContext($request, $requestId);

        if (!$this->shouldSkipLogging($request)) {
            Log::info('request.started', $context);
        }

        $response = $next($request);

        $durationMs = (microtime(true) - $startTime) * 1000;
        $finalContext = array_merge($context, [
            'status' => $response->status(),
            'duration_ms' => round($durationMs, 2),
        ]);

        if (!$this->shouldSkipLogging($request)) {
            Log::info('request.completed', $finalContext);
        }

        $response->headers->set('X-Response-Time', round($durationMs, 2) . 'ms');
        $response->headers->set('X-Request-Id', $requestId);

        if ($durationMs > 1000) {
            Log::warning('request.slow', $finalContext);
        }

        return $response;
    }

    private function shouldSkipLogging(Request $request): bool
    {
        return in_array($request->path(), self::SKIP_PATHS, true);
    }

    private function buildContext(Request $request, string $requestId): array
    {
        return [
            'request_id' => $requestId,
            'method' => $request->method(),
            'path' => '/' . ltrim($request->path(), '/'),
            'query_keys' => array_keys($request->query()),
            'ip' => $request->ip(),
            'user_id' => auth()->id(),
            'user_agent' => $request->userAgent(),
        ];
    }
}

