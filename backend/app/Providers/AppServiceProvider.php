<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        $this->registerBooleanQueryMacros();
    }

    /**
     * Macros pg-safe para filtros booleanos. PostgreSQL e estrito (boolean != integer):
     * enviar 1/0 ou bool (Laravel converte bool->int) para coluna boolean estoura.
     * Estes macros usam whereRaw('col = true/false') no pgsql e o where normal nos demais.
     */
    private function registerBooleanQueryMacros(): void
    {
        $make = function (string $sqlValue, bool $boolValue, string $rawMethod, string $whereMethod) {
            return function ($column) use ($sqlValue, $boolValue, $rawMethod, $whereMethod) {
                if ($this->getConnection()->getDriverName() === 'pgsql') {
                    return $this->{$rawMethod}($column . ' = ' . $sqlValue);
                }

                return $this->{$whereMethod}($column, $boolValue);
            };
        };

        foreach ([QueryBuilder::class, EloquentBuilder::class] as $builder) {
            $builder::macro('whereTrue', $make('true', true, 'whereRaw', 'where'));
            $builder::macro('whereFalse', $make('false', false, 'whereRaw', 'where'));
            $builder::macro('orWhereTrue', $make('true', true, 'orWhereRaw', 'orWhere'));
            $builder::macro('orWhereFalse', $make('false', false, 'orWhereRaw', 'orWhere'));
        }
    }
}
