<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Exception;

class OpenAIController extends Controller
{
    /**
     * Caminho para o serviço Node.js OpenAI
     */
    private $openaiServicePath;

    public function __construct()
    {
        $this->openaiServicePath = base_path('openai-service.js');
    }

    /**
     * Validar se o OpenAI está configurado
     */
    private function validateOpenAIConfig(): bool
    {
        // Verificar se a API key está configurada
        if (empty(env('OPENAI_API_KEY'))) {
            return false;
        }

        // Verificar se o serviço Node.js existe
        if (!file_exists($this->openaiServicePath)) {
            return false;
        }

        return true;
    }

    /**
     * Executar comando Node.js de forma segura
     */
    private function executeNodeCommand(string $command): array
    {
        try {
            // Sanitizar comando
            $command = escapeshellcmd($command);
            
            // Executar com timeout
            $output = [];
            $returnCode = 0;
            
            exec($command . ' 2>&1', $output, $returnCode);
            
            $outputString = implode("\n", $output);
            
            if ($returnCode !== 0) {
                Log::error('Erro OpenAI Node.js', [
                    'command' => $command,
                    'return_code' => $returnCode,
                    'output' => $outputString
                ]);
                
                return [
                    'success' => false,
                    'error' => 'Erro interno no serviço OpenAI'
                ];
            }

            // Tentar decodificar JSON
            $result = json_decode($outputString, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Erro decodificação JSON OpenAI', [
                    'output' => $outputString,
                    'json_error' => json_last_error_msg()
                ]);
                
                return [
                    'success' => false,
                    'error' => 'Resposta inválida do serviço OpenAI'
                ];
            }

            return $result;
            
        } catch (Exception $e) {
            Log::error('Erro execução OpenAI', [
                'error' => $e->getMessage(),
                'command' => $command
            ]);
            
            return [
                'success' => false,
                'error' => 'Falha na comunicação com OpenAI'
            ];
        }
    }

    /**
     * Gerar resposta de chat
     */
    public function chat(Request $request): JsonResponse
    {
        // Validar configuração
        if (!$this->validateOpenAIConfig()) {
            return response()->json([
                'success' => false,
                'error' => 'OpenAI não está configurado corretamente'
            ], 500);
        }

        // Validar entrada
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:1000',
            'context' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Dados inválidos',
                'details' => $validator->errors()
            ], 400);
        }

        // Sanitizar entrada
        $message = addslashes($request->message);
        $context = $request->context ? addslashes($request->context) : '';

        // Montar comando
        $nodeCommand = "node {$this->openaiServicePath} chat \"{$message}\"";
        
        // Executar
        $result = $this->executeNodeCommand($nodeCommand);

        return response()->json($result);
    }

    /**
     * Gerar sugestões de estabelecimentos
     */
    public function suggest(Request $request): JsonResponse
    {
        // Validar configuração
        if (!$this->validateOpenAIConfig()) {
            return response()->json([
                'success' => false,
                'error' => 'OpenAI não está configurado corretamente'
            ], 500);
        }

        // Validar entrada
        $validator = Validator::make($request->all(), [
            'preferences' => 'required|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Dados inválidos',
                'details' => $validator->errors()
            ], 400);
        }

        // Sanitizar entrada
        $preferences = addslashes($request->preferences);

        // Montar comando
        $nodeCommand = "node {$this->openaiServicePath} suggest \"{$preferences}\"";
        
        // Executar
        $result = $this->executeNodeCommand($nodeCommand);

        return response()->json($result);
    }

    /**
     * Testar configuração OpenAI
     */
    public function test(): JsonResponse
    {
        // Validar configuração
        if (!$this->validateOpenAIConfig()) {
            return response()->json([
                'success' => false,
                'error' => 'OpenAI não está configurado',
                'details' => [
                    'api_key_exists' => !empty(env('OPENAI_API_KEY')),
                    'service_file_exists' => file_exists($this->openaiServicePath)
                ]
            ], 500);
        }

        // Executar teste
        $nodeCommand = "node {$this->openaiServicePath} test";
        $result = $this->executeNodeCommand($nodeCommand);

        return response()->json([
            'config_check' => true,
            'openai_response' => $result
        ]);
    }

    /**
     * Status da configuração OpenAI
     */
    public function status(): JsonResponse
    {
        return response()->json([
            'openai_configured' => $this->validateOpenAIConfig(),
            'api_key_exists' => !empty(env('OPENAI_API_KEY')),
            'service_file_exists' => file_exists($this->openaiServicePath),
            'node_available' => $this->checkNodeAvailable()
        ]);
    }

    /**
     * Verificar se Node.js está disponível
     */
    private function checkNodeAvailable(): bool
    {
        $output = [];
        $returnCode = 0;
        exec('node --version 2>&1', $output, $returnCode);
        
        return $returnCode === 0;
    }
}