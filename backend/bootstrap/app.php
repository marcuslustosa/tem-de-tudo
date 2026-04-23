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
            \App\Http\Middleware\SanitizeInput::class,
            'throttle:api',
        ]);
        
        // Middleware global de segurança
        $middleware->web(append: [
            \App\Http\Middleware\SecurityMiddleware::class,
            \App\Http\Middleware\SecurityHeadersMiddleware::class,
        ]);
        
        $middleware->alias([
            'auth.sanctum' => \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'sanctum.auth' => \App\Http\Middleware\SanctumMiddleware::class,
            'admin.permission' => \App\Http\Middleware\AdminPermissionMiddleware::class,
            'role.permission' => \App\Http\Middleware\RolePermissionMiddleware::class,
            'security' => \App\Http\Middleware\SecurityMiddleware::class,
            'cache.response' => \App\Http\Middleware\CacheResponse::class,
            'rate.limit' => \App\Http\Middleware\RateLimitMiddleware::class,
            'security.headers' => \App\Http\Middleware\SecurityHeadersMiddleware::class,
            'subscription.check' => \App\Http\Middleware\CheckCompanySubscription::class,
            'request.logger' => \App\Http\Middleware\RequestLogger::class,
            'idempotency' => \App\Http\Middleware\IdempotencyMiddleware::class,
        ]);
        
        // Middleware global de logging (apenas produção)
        if (($_ENV['APP_ENV'] ?? env('APP_ENV')) === 'production') {
            $middleware->api(append: [
                \App\Http\Middleware\RequestLogger::class,
            ]);
        }
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

        // Notificar sobre pontos expirando (7 dias antes)
        $schedule->command('pontos:notificar-expiracao')
            ->dailyAt('09:00')
            ->timezone('America/Sao_Paulo')
            ->onSuccess(function () {
                \Illuminate\Support\Facades\Log::info('Notificações de expiração enviadas com sucesso');
            })
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::error('Falha ao enviar notificações de expiração');
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
        
        // Processar billing (faturas e suspensões) - diariamente às 06h
        $schedule->command('billing:process')
            ->dailyAt('06:00')
            ->timezone('America/Sao_Paulo')
            ->onSuccess(function () {
                \Illuminate\Support\Facades\Log::info('Billing processado com sucesso');
            })
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::error('Falha ao processar billing');
            });
        
        // Processar reservas expiradas - a cada 5 minutos
        $schedule->command('redemptions:process-expired')
            ->everyFiveMinutes()
            ->onSuccess(function () {
                \Illuminate\Support\Facades\Log::info('Reservas expiradas processadas');
            })
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::error('Falha ao processar reservas expiradas');
            });
        
        // Monitorar sistema - a cada 10 minutos
        $schedule->command('monitor:system')
            ->everyTenMinutes()
            ->onSuccess(function () {
                \Illuminate\Support\Facades\Log::info('Sistema monitorado');
            })
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::error('Falha ao monitorar sistema');
            });

        // Rotação de secrets - mensalmente (1º dia do mês às 00:00)
        // NOTA: Este comando apenas GERA os novos secrets, a aplicação é manual
        $schedule->command('secrets:rotate --force')
            ->monthlyOn(1, '00:00')
            ->timezone('America/Sao_Paulo')
            ->onSuccess(function () {
                \Illuminate\Support\Facades\Log::warning('⚠️ SECRETS GERADOS - Aplicar manualmente no .env');
            })
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::error('Falha ao gerar novos secrets');
            });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
