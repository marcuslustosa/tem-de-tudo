# Script de Teste Funcional Completo
# Testa cadastro, login, navegação

Write-Host ""
Write-Host "=== TESTE FUNCIONAL COMPLETO - TEM DE TUDO ===" -ForegroundColor Cyan
Write-Host "Servidor: http://127.0.0.1:8001" -ForegroundColor Yellow
Write-Host ""

$baseUrl = "http://127.0.0.1:8001"
$testResults = @()

function Test-Endpoint {
    param($name, $url, $method = "GET", $body = $null)
    
    Write-Host "Testando: $name..." -NoNewline
    
    try {
        $params = @{
            Uri = "$baseUrl$url"
            Method = $method
            UseBasicParsing = $true
            TimeoutSec = 10
        }
        
        if ($body) {
            $params.Body = ($body | ConvertTo-Json)
            $params.ContentType = "application/json"
            $params.Headers = @{"Accept" = "application/json"}
        }
        
        $response = Invoke-WebRequest @params -ErrorAction Stop
        
        if ($response.StatusCode -eq 200 -or $response.StatusCode -eq 201) {
            Write-Host " ✓ OK" -ForegroundColor Green
            return @{Name=$name; Status="OK"; Code=$response.StatusCode}
        } else {
            Write-Host " ✗ ERRO ($($response.StatusCode))" -ForegroundColor Red
            return @{Name=$name; Status="ERRO"; Code=$response.StatusCode}
        }
    } catch {
        Write-Host " ✗ FALHOU ($($_.Exception.Message))" -ForegroundColor Red
        return @{Name=$name; Status="FALHOU"; Error=$_.Exception.Message}
    }
}

# TESTE 1: Páginas HTML carregam
Write-Host ""
Write-Host "[1] TESTE DE PÁGINAS HTML" -ForegroundColor Magenta
$testResults += Test-Endpoint "Login Page" "/entrar.html"
$testResults += Test-Endpoint "Cadastro Page" "/cadastro.html"
$testResults += Test-Endpoint "Cadastro Empresa" "/cadastro-empresa.html"
$testResults += Test-Endpoint "Admin Login" "/admin-login.html"
$testResults += Test-Endpoint "App Início" "/app-inicio.html"
$testResults += Test-Endpoint "Dashboard Cliente" "/dashboard-cliente.html"
$testResults += Test-Endpoint "Dashboard Empresa" "/empresa-dashboard.html"

# TESTE 2: Arquivos JS carregam
Write-Host "`n[2] TESTE DE ARQUIVOS JAVASCRIPT" -ForegroundColor Magenta
$testResults += Test-Endpoint "config.js" "/js/config.js"
$testResults += Test-Endpoint "auth-manager.js" "/js/auth-manager.js"
$testResults += Test-Endpoint "api-client.js" "/js/api-client.js"
$testResults += Test-Endpoint "validators.js" "/js/validators.js"
$testResults += Test-Endpoint "ui-helpers.js" "/js/ui-helpers.js"
$testResults += Test-Endpoint "auth-guard.js" "/js/auth-guard.js"

# TESTE 3: API está respondendo
Write-Host "`n[3] TESTE DE API" -ForegroundColor Magenta
$testResults += Test-Endpoint "API Debug" "/api/debug"

# TESTE 4: Cadastro de usuário
Write-Host "`n[4] TESTE DE CADASTRO (Cliente)" -ForegroundColor Magenta
$timestamp = Get-Date -Format "HHmmss"
$cadastroData = @{
    perfil = "cliente"
    name = "Teste Automatico $timestamp"
    sobrenome = "Sobrenome"
    email = "teste$timestamp@teste.com"
    telefone = "11999999999"
    cpf = "12345678900"
    password = "senha123456"
    password_confirmation = "senha123456"
}
$testResults += Test-Endpoint "Cadastro Cliente" "/api/auth/register" "POST" $cadastroData

# TESTE 5: Login
Write-Host "`n[5] TESTE DE LOGIN" -ForegroundColor Magenta
$loginData = @{
    email = "teste$timestamp@teste.com"
    password = "senha123456"
}

try {
    Write-Host "Testando login..." -NoNewline
    $response = Invoke-RestMethod -Uri "$baseUrl/api/auth/login" -Method POST -Body ($loginData | ConvertTo-Json) -ContentType "application/json" -ErrorAction Stop
    
    if ($response.access_token) {
        Write-Host " ✓ OK (Token recebido)" -ForegroundColor Green
        $token = $response.access_token
        $testResults += @{Name="Login Cliente"; Status="OK"; Token="Sim"}
        
        # TESTE 6: Acesso autenticado
        Write-Host "`n[6] TESTE DE ACESSO AUTENTICADO" -ForegroundColor Magenta
        try {
            Write-Host "Testando perfil autenticado..." -NoNewline
            $headers = @{
                "Authorization" = "Bearer $token"
                "Accept" = "application/json"
            }
            $profile = Invoke-RestMethod -Uri "$baseUrl/api/cliente/me" -Headers $headers -ErrorAction Stop
            Write-Host " ✓ OK (Perfil: $($profile.name))" -ForegroundColor Green
            $testResults += @{Name="Perfil Autenticado"; Status="OK"; User=$profile.name}
        } catch {
            Write-Host " ✗ ERRO" -ForegroundColor Red
            $testResults += @{Name="Perfil Autenticado"; Status="ERRO"}
        }
    } else {
        Write-Host " ✗ ERRO (Sem token)" -ForegroundColor Red
        $testResults += @{Name="Login Cliente"; Status="ERRO"}
    }
} catch {
    Write-Host " ✗ FALHOU" -ForegroundColor Red
    Write-Host "   Erro: $($_.Exception.Message)" -ForegroundColor DarkRed
    $testResults += @{Name="Login Cliente"; Status="FALHOU"; Error=$_.Exception.Message}
}

# RELATÓRIO FINAL
Write-Host ""
Write-Host ""
Write-Host "=== RELATÓRIO DE TESTES ===" -ForegroundColor Cyan

$totalTests = $testResults.Count
$passedTests = ($testResults | Where-Object {$_.Status -eq "OK"}).Count
$failedTests = $totalTests - $passedTests

Write-Host ""
Write-Host "Total de Testes: $totalTests" -ForegroundColor White
Write-Host "Aprovados: $passedTests" -ForegroundColor Green
Write-Host "Repr"
    Write-Host "ados: $failedTests" -ForegroundColor Red

if ($failedTests -gt 0) {
    Write-Host "`nTestes que falharam:" -ForegroundColor Yellow
    $testResults | Where-Object {$_.Status -ne "OK"} | ForEach-Object {
        Write-Host "  ✗ $($_.Name)" -ForegroundColor Red
        if ($_.Error) {
            Write-Host "    Erro: $($_.Error)" -ForegroundColor DarkRed
        }
    }
}

$successRate"
Write-Host "Taxa de Sucesso: $successRate%" -ForegroundColor $(if ($successRate -ge 80) {"Green"} else {"Red"})

Write-Host ""
Write-Host "=== FIM DOS TESTES ===" -ForegroundColor Cyan
Write-Host ""
Write-Host "`n=== FIM DOS TESTES ===`n" -ForegroundColor Cyan
