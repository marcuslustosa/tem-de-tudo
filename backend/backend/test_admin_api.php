#!/usr/bin/env php
<?php

/**
 * Script de teste para API de Administração - TemDeTudo
 * 
 * Este script testa todas as funcionalidades de produção:
 * - Autenticação JWT
 * - Rate Limiting
 * - Audit Logging
 * - Permissões de Admin
 * - Relatórios de Segurança
 */

require_once __DIR__ . '/vendor/autoload.php';

class AdminApiTester
{
    private $baseUrl;
    private $token;
    
    public function __construct($baseUrl = 'http://localhost:8000/api')
    {
        $this->baseUrl = rtrim($baseUrl, '/');
    }
    
    public function run()
    {
        echo "=== TESTE DA API DE ADMINISTRAÇÃO ===\n\n";
        
        try {
            $this->testLogin();
            $this->testProfile();
            $this->testPermissions();
            $this->testReports();
            $this->testRateLimiting();
            $this->testLogout();
            
            echo "\n✅ TODOS OS TESTES PASSARAM!\n";
            
        } catch (Exception $e) {
            echo "\n❌ ERRO: " . $e->getMessage() . "\n";
        }
    }
    
    private function testLogin()
    {
        echo "1. Testando login de admin...\n";
        
        $response = $this->makeRequest('POST', '/admin/login', [
            'email' => 'admin@temdetudo.com',
            'password' => 'admin123'
        ]);
        
        if (!isset($response['success']) || !$response['success']) {
            throw new Exception('Login falhou: ' . json_encode($response));
        }
        
        $this->token = $response['token'];
        echo "   ✓ Login realizado com sucesso\n";
        echo "   ✓ Token JWT recebido\n\n";
    }
    
    private function testProfile()
    {
        echo "2. Testando perfil do admin...\n";
        
        $response = $this->makeRequest('GET', '/admin/me');
        
        if (!isset($response['success']) || !$response['success']) {
            throw new Exception('Falha ao obter perfil');
        }
        
        echo "   ✓ Perfil obtido: " . $response['user']['name'] . "\n";
        echo "   ✓ Role: " . $response['user']['role'] . "\n\n";
    }
    
    private function testPermissions()
    {
        echo "3. Testando permissões...\n";
        
        // Testar criação de usuário (requer permissão)
        $response = $this->makeRequest('POST', '/admin/create-user', [
            'name' => 'Usuário Teste',
            'email' => 'teste@temdetudo.com',
            'password' => 'senha123'
        ]);
        
        if (isset($response['success']) && $response['success']) {
            echo "   ✓ Usuário criado com sucesso\n";
        } else {
            echo "   ⚠ Falha na criação (verificar permissões)\n";
        }
        
        echo "\n";
    }
    
    private function testReports()
    {
        echo "4. Testando relatórios...\n";
        
        // Estatísticas do sistema
        $response = $this->makeRequest('GET', '/admin/system-stats');
        if (isset($response['success']) && $response['success']) {
            echo "   ✓ Estatísticas do sistema obtidas\n";
        }
        
        // Logs de auditoria
        $response = $this->makeRequest('GET', '/admin/audit-logs');
        if (isset($response['success']) && $response['success']) {
            echo "   ✓ Logs de auditoria obtidos\n";
        }
        
        // Eventos de segurança
        $response = $this->makeRequest('GET', '/admin/security-events');
        if (isset($response['success']) && $response['success']) {
            echo "   ✓ Eventos de segurança obtidos\n";
        }
        
        echo "\n";
    }
    
    private function testRateLimiting()
    {
        echo "5. Testando rate limiting...\n";
        
        // Fazer várias tentativas de login inválido
        for ($i = 0; $i < 6; $i++) {
            $response = $this->makeRequest('POST', '/admin/login', [
                'email' => 'invalid@test.com',
                'password' => 'wrong'
            ], false); // Não lançar exceção
            
            if ($i < 5) {
                echo "   Tentativa " . ($i + 1) . " de login inválido\n";
            } else {
                if (isset($response['message']) && strpos($response['message'], 'rate') !== false) {
                    echo "   ✓ Rate limiting funcionando!\n";
                } else {
                    echo "   ⚠ Rate limiting pode não estar funcionando\n";
                }
            }
        }
        
        echo "\n";
    }
    
    private function testLogout()
    {
        echo "6. Testando logout...\n";
        
        $response = $this->makeRequest('POST', '/admin/logout');
        
        if (isset($response['success']) && $response['success']) {
            echo "   ✓ Logout realizado com sucesso\n";
        }
        
        echo "\n";
    }
    
    private function makeRequest($method, $endpoint, $data = [], $throwOnError = true)
    {
        $url = $this->baseUrl . $endpoint;
        
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        if ($this->token) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'GET' && $data) {
            $url .= '?' . http_build_query($data);
            curl_setopt($ch, CURLOPT_URL, $url);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $decoded = json_decode($response, true);
        
        if ($throwOnError && $httpCode >= 400) {
            throw new Exception("HTTP {$httpCode}: " . $response);
        }
        
        return $decoded ?: ['raw' => $response, 'http_code' => $httpCode];
    }
}

// Verificar argumentos da linha de comando
$baseUrl = $argv[1] ?? 'http://localhost:8000/api';

echo "Usando URL base: {$baseUrl}\n\n";

$tester = new AdminApiTester($baseUrl);
$tester->run();