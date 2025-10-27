<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index()
    {
        try {
            // Test database connection
            DB::connection()->getPdo();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Sistema Tem de Tudo funcionando!',
                'database' => 'connected',
                'environment' => app()->environment(),
                'version' => app()->version(),
                'timestamp' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro na aplicação',
                'error' => $e->getMessage(),
                'environment' => app()->environment()
            ], 500);
        }
    }
    
    public function health()
    {
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'memory' => memory_get_usage(true),
            'database' => $this->checkDatabase()
        ]);
    }
    
    private function checkDatabase()
    {
        try {
            DB::connection()->getPdo();
            return 'connected';
        } catch (\Exception $e) {
            return 'disconnected: ' . $e->getMessage();
        }
    }
}