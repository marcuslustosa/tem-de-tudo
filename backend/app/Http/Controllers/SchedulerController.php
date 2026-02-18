<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class SchedulerController extends Controller
{
    /**
     * Executa o Laravel Scheduler via HTTP
     * 
     * Endpoint protegido por token para ser chamado por cron jobs externos
     * Ideal para Render Free que não suporta cron nativo
     */
    public function run(Request $request)
    {
        // Validar token de segurança
        $token = $request->header('X-Scheduler-Token') ?? $request->input('token');
        $expectedToken = env('SCHEDULER_TOKEN', 'tem-de-tudo-scheduler-2026');
        
        if ($token !== $expectedToken) {
            Log::warning('Tentativa de acesso não autorizado ao scheduler', [
                'ip' => $request->ip(),
                'token_provided' => $token ? 'sim' : 'não'
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Token inválido'
            ], 401);
        }
        
        try {
            // Executar o scheduler
            Artisan::call('schedule:run');
            $output = Artisan::output();
            
            Log::info('Scheduler executado via HTTP', [
                'ip' => $request->ip(),
                'output' => $output
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Scheduler executado com sucesso',
                'output' => $output,
                'timestamp' => now()->toDateTimeString()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erro ao executar scheduler via HTTP', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao executar scheduler',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Executa apenas o comando de bônus aniversário
     * 
     * Útil para testes ou execução manual
     */
    public function runBirthdayBonus(Request $request)
    {
        // Validar token de segurança
        $token = $request->header('X-Scheduler-Token') ?? $request->input('token');
        $expectedToken = env('SCHEDULER_TOKEN', 'tem-de-tudo-scheduler-2026');
        
        if ($token !== $expectedToken) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido'
            ], 401);
        }
        
        try {
            // Executar comando de bônus aniversário
            Artisan::call('bonus:aniversario');
            $output = Artisan::output();
            
            Log::info('Bônus aniversário executado via HTTP', [
                'ip' => $request->ip(),
                'output' => $output
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Bônus aniversário processado com sucesso',
                'output' => $output,
                'timestamp' => now()->toDateTimeString()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erro ao executar bônus aniversário via HTTP', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar bônus aniversário',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Status do scheduler
     * 
     * Retorna informações sobre o último processamento
     */
    public function status(Request $request)
    {
        // Validar token de segurança
        $token = $request->header('X-Scheduler-Token') ?? $request->input('token');
        $expectedToken = env('SCHEDULER_TOKEN', 'tem-de-tudo-scheduler-2026');
        
        if ($token !== $expectedToken) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido'
            ], 401);
        }
        
        return response()->json([
            'success' => true,
            'server_time' => now()->toDateTimeString(),
            'timezone' => config('app.timezone'),
            'scheduler_configured' => true,
            'commands' => [
                'bonus:aniversario' => 'Executa diariamente às 08:00'
            ]
        ]);
    }
}
