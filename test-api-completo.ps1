# Script de Teste Completo da API
Write-Host "`n🧪 TESTE COMPLETO DA API - TEM DE TUDO`n" -ForegroundColor Cyan

$API = "http://127.0.0.1:8001/api"

# 1. REGISTRO
Write-Host "1️⃣  Testando REGISTRO..." -ForegroundColor Yellow
try {
    $body = @{
        name = "Teste API"
        email = "testeapi_$(Get-Random)@email.com"
        password = "senha123"
        password_confirmation = "senha123"
        perfil = "cliente"
        telefone = "(11) 99999-9999"
    } | ConvertTo-Json
    
    $register = Invoke-RestMethod -Uri "$API/auth/register" -Method Post -Body $body -ContentType "application/json"
    $token = $register.data.token
    Write-Host "   ✅ REGISTRO OK - Token: $($token.Substring(0,20))..." -ForegroundColor Green
} catch {
    Write-Host "   ❌ ERRO: $($_.Exception.Message)" -ForegroundColor Red
}

# 2. LOGIN
Write-Host "`n2️⃣  Testando LOGIN..." -ForegroundColor Yellow
try {
    $body = @{
        email = "cliente@teste.com"
        password = "123456"
    } | ConvertTo-Json
    
    $login = Invoke-RestMethod -Uri "$API/auth/login" -Method Post -Body $body -ContentType "application/json"
    $token = $login.data.token
    $headers = @{Authorization = "Bearer $token"}
    Write-Host "   ✅ LOGIN OK - Usuário: $($login.data.user.name)" -ForegroundColor Green
} catch {
    Write-Host "   ❌ ERRO: $($_.Exception.Message)" -ForegroundColor Red
    exit
}

# 3. EMPRESAS
Write-Host "`n3️⃣  Testando EMPRESAS..." -ForegroundColor Yellow
try {
    $empresas = Invoke-RestMethod -Uri "$API/cliente/empresas" -Headers $headers
    Write-Host "   ✅ EMPRESAS OK - Total: $($empresas.data.Length)" -ForegroundColor Green
} catch {
    Write-Host "   ❌ ERRO: $($_.Exception.Message)" -ForegroundColor Red
}

# 4. PROMOÇÕES
Write-Host "`n4️⃣  Testando PROMOÇÕES..." -ForegroundColor Yellow
try {
    $promocoes = Invoke-RestMethod -Uri "$API/cliente/promocoes" -Headers $headers
    Write-Host "   ✅ PROMOÇÕES OK" -ForegroundColor Green
} catch {
    Write-Host "   ⚠️  Nenhuma promoção disponível" -ForegroundColor Yellow
}

# 5. DADOS DO CLIENTE
Write-Host "`n5️⃣  Testando DADOS DO CLIENTE..." -ForegroundColor Yellow
try {
    $cliente = Invoke-RestMethod -Uri "$API/pontos/meus-dados" -Headers $headers
    $pontos = if ($cliente.pontos) { $cliente.pontos } else { 0 }
    Write-Host "   ✅ DADOS OK - Pontos: $pontos" -ForegroundColor Green
} catch {
    Write-Host "   ❌ ERRO: $($_.Exception.Message)" -ForegroundColor Red
}

# 6. HISTÓRICO
Write-Host "`n6️⃣  Testando HISTÓRICO..." -ForegroundColor Yellow
try {
    $historico = Invoke-RestMethod -Uri "$API/pontos/historico" -Headers $headers
    Write-Host "   ✅ HISTÓRICO OK" -ForegroundColor Green
} catch {
    Write-Host "   ⚠️  Sem histórico" -ForegroundColor Yellow
}

# 7. CUPONS
Write-Host "`n7️⃣  Testando CUPONS..." -ForegroundColor Yellow
try {
    $cupons = Invoke-RestMethod -Uri "$API/pontos/meus-cupons" -Headers $headers
    Write-Host "   ✅ CUPONS OK" -ForegroundColor Green
} catch {
    Write-Host "   ⚠️  Sem cupons" -ForegroundColor Yellow
}

Write-Host "`n✨ TESTE COMPLETO FINALIZADO!`n" -ForegroundColor Cyan
