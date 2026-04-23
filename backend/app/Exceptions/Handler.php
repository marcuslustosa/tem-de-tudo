<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontReport = [
        //
    ];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            $request = request();

            $context = [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'url' => $request?->fullUrl(),
                'method' => $request?->method(),
                'ip' => $request?->ip(),
                'user_id' => auth()->id(),
            ];

            if (config('app.debug')) {
                $context['trace'] = $e->getTraceAsString();
            }

            Log::error('exception.reported', $context);

            // Integracao opcional com Sentry quando o SDK estiver instalado.
            if (app()->bound('sentry')) {
                try {
                    app('sentry')->captureException($e);
                } catch (\Throwable $sentryError) {
                    Log::warning('exception.sentry_capture_failed', [
                        'message' => $sentryError->getMessage(),
                    ]);
                }
            }
        });
    }
}

