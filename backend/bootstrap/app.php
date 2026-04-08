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
        // Mantem o grupo "api" stateless para evitar CSRF em endpoints JSON de login/cadastro.
        $middleware->appendToGroup('api', [
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
            'cache.response' => \App\Http\Middleware\CacheResponse::class,
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

        // Enviar lembretes para clientes ausentes há mais de 30 dias
        $schedule->command('lembretes:ausencia')
            ->dailyAt('10:00')
            ->timezone('America/Sao_Paulo')
            ->onSuccess(function () {
                \Illuminate\Support\Facades\Log::info('Lembretes de ausência enviados com sucesso');
            })
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::error('Falha ao enviar lembretes de ausência');
            });

        // Expirar pontos mais antigos que o limite configurado
        $schedule->command('pontos:expirar')
            ->dailyAt('02:00')
            ->timezone('America/Sao_Paulo')
            ->onSuccess(function () {
                \Illuminate\Support\Facades\Log::info('Expiração de pontos processada com sucesso');
            })
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::error('Falha ao expirar pontos');
            });

        // Recalcular ranking de pontos diariamente às 03h
        $schedule->command('ranking:recalcular')
            ->dailyAt('03:00')
            ->timezone('America/Sao_Paulo')
            ->onSuccess(function () {
                \Illuminate\Support\Facades\Log::info('Ranking de pontos recalculado com sucesso');
            })
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::error('Falha ao recalcular ranking de pontos');
            });

        // Avaliação anual de nível: executa todo 1º de janeiro às 01h
        $schedule->command('nivel:avaliar-anual')
            ->yearlyOn(1, 1, '01:00')
            ->timezone('America/Sao_Paulo')
            ->onSuccess(function () {
                \Illuminate\Support\Facades\Log::info('Avaliação anual de nível concluída');
            })
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::error('Falha na avaliação anual de nível');
            });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
