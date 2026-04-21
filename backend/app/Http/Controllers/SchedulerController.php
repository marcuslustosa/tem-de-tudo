<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class SchedulerController extends Controller
{
    /**
     * Executa o Laravel Scheduler via HTTP.
     */
    public function run(Request $request)
    {
        if (!$this->isTokenValid($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalido',
            ], 401);
        }

        try {
            Artisan::call('schedule:run');
            $output = Artisan::output();

            Log::info('Scheduler executado via HTTP', [
                'ip' => $request->ip(),
                'output' => $output,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Scheduler executado com sucesso',
                'output' => $output,
                'timestamp' => now()->toDateTimeString(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Erro ao executar scheduler via HTTP', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao executar scheduler',
            ], 500);
        }
    }

    /**
     * Executa apenas o comando de bonus aniversario.
     */
    public function runBirthdayBonus(Request $request)
    {
        if (!$this->isTokenValid($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalido',
            ], 401);
        }

        try {
            Artisan::call('bonus:aniversario');
            $output = Artisan::output();

            Log::info('Bonus aniversario executado via HTTP', [
                'ip' => $request->ip(),
                'output' => $output,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Bonus aniversario processado com sucesso',
                'output' => $output,
                'timestamp' => now()->toDateTimeString(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Erro ao executar bonus aniversario via HTTP', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar bonus aniversario',
            ], 500);
        }
    }

    /**
     * Status do scheduler.
     */
    public function status(Request $request)
    {
        if (!$this->isTokenValid($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalido',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'server_time' => now()->toDateTimeString(),
            'timezone' => config('app.timezone'),
            'scheduler_configured' => true,
            'commands' => [
                'bonus:aniversario' => 'Executa diariamente as 08:00',
            ],
        ]);
    }

    private function isTokenValid(Request $request): bool
    {
        $expectedToken = (string) env('SCHEDULER_TOKEN', '');
        if ($expectedToken === '') {
            Log::error('SCHEDULER_TOKEN nao configurado para SchedulerController.');
            return false;
        }

        $provided = (string) ($request->header('X-Scheduler-Token') ?? $request->input('token', ''));
        if ($provided === '') {
            Log::warning('Tentativa de acesso ao scheduler sem token', ['ip' => $request->ip()]);
            return false;
        }

        $valid = hash_equals($expectedToken, $provided);
        if (!$valid) {
            Log::warning('Tentativa de acesso nao autorizado ao scheduler', [
                'ip' => $request->ip(),
                'token_provided' => 'sim',
            ]);
        }

        return $valid;
    }
}
