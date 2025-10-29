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