# Teste Funcional Simples - Tem de Tudo
Write-Host "=== TESTE FUNCIONAL ===" -ForegroundColor Cyan
$base = "http://127.0.0.1:8001"

# 1. Testar páginas HTML
Write-Host "`n[1] Páginas HTML" -ForegroundColor Yellow
$paginas = @("entrar.html", "cadastro.html", "admin-login.html", "app-inicio.html")
foreach ($p in $paginas) {
    try {
        $r = Invoke-WebRequest -Uri "$base/$p" -UseBasicParsing -TimeoutSec 5
        Write-Host "  $p - OK ($($r.StatusCode))" -ForegroundColor Green
    } catch {
        Write-Host "  $p - ERRO" -ForegroundColor Red
    }
}

# 2. Testar arquivos JS
Write-Host "`n[2] Arquivos JavaScript" -ForegroundColor Yellow
$scripts = @("js/config.js", "js/auth-manager.js", "js/api-client.js")
foreach ($s in $scripts) {
    try {
        $r = Invoke-WebRequest -Uri "$base/$s" -UseBasicParsing -TimeoutSec 5
        Write-Host "  $s - OK ($($r.StatusCode))" -ForegroundColor Green
    } catch {
        Write-Host "  $s - ERRO" -ForegroundColor Red
    }
}

# 3. Testar API
Write-Host "`n[3] API Debug" -ForegroundColor Yellow
try {
    $r = Invoke-RestMethod -Uri "$base/api/debug"
    Write-Host "  API Status: $($r.status)" -ForegroundColor Green
} catch {
    Write-Host "  API - ERRO" -ForegroundColor Red
}

# 4. Cadastro
Write-Host "`n[4] Cadastro de Cliente" -ForegroundColor Yellow
$ts = Get-Date -Format "HHmmss"
$dados = @{
    perfil = "cliente"
    name = "Teste"
    sobrenome = "Auto"
    email = "teste$ts@teste.com"
    telefone = "11999999999"
    cpf = "12345678900"
    password = "senha123456"
    password_confirmation = "senha123456"
} | ConvertTo-Json

try {
    $r = Invoke-RestMethod -Uri "$base/api/auth/register" -Method POST -Body $dados -ContentType "application/json"
    Write-Host "  Cadastro OK - Usuário: $($r.user.name)" -ForegroundColor Green
    
    # 5. Login
    Write-Host "`n[5] Login" -ForegroundColor Yellow
    $login = @{
        email = "teste$ts@teste.com"
        password = "senha123456"
    } | ConvertTo-Json
    
    $l = Invoke-RestMethod -Uri "$base/api/auth/login" -Method POST -Body $login -ContentType "application/json"
    Write-Host "  Login OK - Token: $($l.access_token.Substring(0,20))..." -ForegroundColor Green
    
    # 6. Perfil autenticado
    Write-Host "`n[6] Acesso Autenticado" -ForegroundColor Yellow
    $headers = @{Authorization = "Bearer $($l.access_token)"}
    $perfil = Invoke-RestMethod -Uri "$base/api/cliente/me" -Headers $headers
    Write-Host "  Perfil: $($perfil.name) - Email: $($perfil.email)" -ForegroundColor Green
    
} catch {
    Write-Host "  ERRO: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "`n=== FIM ===" -ForegroundColor Cyan
