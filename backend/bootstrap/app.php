<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // CORS deve vir antes de outros middlewares
        $middleware->group('api', [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \App\Http\Middleware\ForceJsonResponse::class,
        ]);
        
        // Middleware global de segurança
        $middleware->web(append: [
            \App\Http\Middleware\SecurityMiddleware::class,
        ]);
        
        $middleware->alias([
            'auth.sanctum' => \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'sanctum.auth' => \App\Http\Middleware\SanctumMiddleware::class,
            'admin.permission' => \App\Http\Middleware\AdminPermissionMiddleware::class,
            'role.permission' => \App\Http\Middleware\RolePermissionMiddleware::class,
            'security' => \App\Http\Middleware\SecurityMiddleware::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Processar bônus de aniversário diariamente às 8h da manhã
        $schedule->command('bonus:aniversario')
            ->dailyAt('08:00')
            ->timezone('America/Sao_Paulo')
            ->onSuccess(function () {
                \Illuminate\Support\Facades\Log::info('Bônus de aniversário processado com sucesso');
            })
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::error('Falha ao processar bônus de aniversário');
            });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
