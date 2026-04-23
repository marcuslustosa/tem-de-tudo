<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HealthController extends Controller
{
    public function index()
    {
        try {
            return response()->json([
                'status' => 'OK',
                'message' => 'Sistema Tem de Tudo funcionando!',
                'timestamp' => now()->toISOString(),
                'environment' => app()->environment(),
                'database' => $this->checkDatabase(),
                'laravel_version' => app()->version(),
                'php_version' => PHP_VERSION,
                'memory_usage' => memory_get_usage(true),
                'disk_space' => disk_free_space('/'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Erro no sistema',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    public function simple()
    {
        return response()->json([
            'status' => 'OK',
            'message' => 'Sistema funcionando!',
            'time' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Ping rápido para load balancer
     */
    public function ping()
    {
        return response()->json(['status' => 'pong']);
    }

    /**
     * Health check completo para monitoring
     */
    public function health()
    {
        try {
            $db = $this->checkDatabase();
            $disk = disk_free_space('/');
            $memory = memory_get_usage(true);
            
            return response()->json([
                'status' => 'healthy',
                'timestamp' => now()->toISOString(),
                'checks' => [
                    'database' => $db['status'] === 'connected' ? 'ok' : 'fail',
                    'disk' => $disk > 1024*1024*100 ? 'ok' : 'warning',
                    'memory' => $memory < 128*1024*1024 ? 'ok' : 'warning',
                ],
                'details' => [
                    'database' => $db,
                    'disk_free' => round($disk / 1024 / 1024, 2) . ' MB',
                    'memory_usage' => round($memory / 1024 / 1024, 2) . ' MB',
                    'php_version' => PHP_VERSION,
                    'laravel_version' => app()->version(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ], 503);
        }
    }

    /**
     * Métricas do sistema para Prometheus/Grafana
     */
    public function metrics()
    {
        try {
            $users = \DB::table('users')->count();
            $companies = \DB::table('empresas')->count();
            $ledgerToday = \DB::table('ledger')->whereDate('created_at', today())->count();
            $reservedPoints = \DB::table('ledger')->where('transaction_type', 'reserved')->sum('points');
            
            return response()->json([
                'timestamp' => now()->toISOString(),
                'metrics' => [
                    'users_total' => $users,
                    'companies_total' => $companies,
                    'transactions_today' => $ledgerToday,
                    'points_reserved' => $reservedPoints,
                    'memory_bytes' => memory_get_usage(true),
                    'disk_free_bytes' => disk_free_space('/'),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function checkDatabase()
    {
        try {
            \DB::connection()->getPdo();
            $dbConfig = config('database.connections.' . config('database.default'));
            return [
                'status' => 'connected',
                'driver' => $dbConfig['driver'] ?? 'unknown',
                'database' => $dbConfig['database'] ?? 'unknown'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}