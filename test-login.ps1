#!/usr/bin/env pwsh
# Script de Teste de Login - Valida os 3 perfis
# Use: .\test-login.ps1

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "  🔐 TESTE DE LOGIN - TEM DE TUDO" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

$API_BASE = "http://127.0.0.1:8000/api"

# Função para testar login
function Test-Login {
    param(
        [string]$Email,
        [string]$Password,
        [string]$Perfil,
        [string]$ExpectedRedirect
    )
    
    Write-Host "🔑 Testando login: $Perfil" -ForegroundColor Yellow
    Write-Host "   Email: $Email" -ForegroundColor Gray
    
    try {
        $body = @{
            email = $Email
            password = $Password
        } | ConvertTo-Json
        
        $response = Invoke-RestMethod -Uri "$API_BASE/auth/login" `
            -Method POST `
            -Body $body `
            -ContentType "application/json" `
            -ErrorAction Stop
        
        if ($response.success) {
            $redirect = $response.data.redirect_to
            $userPerfil = $response.data.user.perfil
            
            if ($redirect -eq $ExpectedRedirect -and $userPerfil -eq $Perfil) {
                Write-Host "   ✅ Login OK - Redireciona para: $redirect" -ForegroundColor Green
                return $true
            } else {
                Write-Host "   ❌ ERRO: Redirecionamento incorreto!" -ForegroundColor Red
                Write-Host "   Esperado: $ExpectedRedirect" -ForegroundColor Red
                Write-Host "   Recebido: $redirect" -ForegroundColor Red
                return $false
            }
        } else {
            Write-Host "   ❌ ERRO: Login falhou - $($response.message)" -ForegroundColor Red
            return $false
        }
    }
    catch {
        Write-Host "   ❌ ERRO: $($_.Exception.Message)" -ForegroundColor Red
        if ($_.Exception.Message -match "Unable to connect") {
            Write-Host "   💡 Servidor não está rodando! Execute: php artisan serve" -ForegroundColor Yellow
        }
        return $false
    }
    finally {
        Write-Host ""
    }
}

# Verificar se servidor está rodando
Write-Host "🌐 Verificando servidor..." -ForegroundColor Cyan
try {
    $health = Invoke-RestMethod -Uri "$API_BASE/debug" -Method GET -ErrorAction Stop
    if ($health.status -eq "OK") {
        Write-Host "✅ Servidor online`n" -ForegroundColor Green
    }
}
catch {
    Write-Host "❌ Servidor offline!" -ForegroundColor Red
    Write-Host "💡 Execute: cd backend && php artisan serve`n" -ForegroundColor Yellow
    exit 1
}

# Executar testes
$results = @()

$results += Test-Login -Email "cliente@teste.com" -Password "123456" `
    -Perfil "cliente" -ExpectedRedirect "/app-inicio.html"

$results += Test-Login -Email "empresa@teste.com" -Password "123456" `
    -Perfil "empresa" -ExpectedRedirect "/dashboard-empresa.html"

$results += Test-Login -Email "admin@temdetudo.com" -Password "admin123" `
    -Perfil "admin" -ExpectedRedirect "/admin.html"

# Resumo
Write-Host "========================================" -ForegroundColor Cyan
$passed = ($results | Where-Object { $_ -eq $true }).Count
$total = $results.Count

if ($passed -eq $total) {
    Write-Host "✅ TODOS OS TESTES PASSARAM! ($passed/$total)" -ForegroundColor Green
    Write-Host "✅ Sistema pronto para apresentação`n" -ForegroundColor Green
    exit 0
} else {
    Write-Host "❌ ALGUNS TESTES FALHARAM! ($passed/$total)" -ForegroundColor Red
    Write-Host "🔧 Verifique os erros acima`n" -ForegroundColor Yellow
    exit 1
}
