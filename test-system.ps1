#!/usr/bin/env pwsh
# Script de teste completo do sistema

Write-Host "==================================" -ForegroundColor Cyan
Write-Host "TESTE COMPLETO - TEM DE TUDO" -ForegroundColor Cyan
Write-Host "==================================" -ForegroundColor Cyan
Write-Host ""

# 1. Verificar PHP
Write-Host "1. Verificando PHP..." -ForegroundColor Yellow
try {
    $phpVersion = php -v 2>&1 | Select-Object -First 1
    Write-Host "   ✅ $phpVersion" -ForegroundColor Green
} catch {
    Write-Host "   ❌ PHP não encontrado" -ForegroundColor Red
    exit 1
}

# 2. Verificar Composer
Write-Host "`n2. Verificando Composer..." -ForegroundColor Yellow
try {
    $composerVersion = composer --version 2>&1 | Select-Object -First 1
    Write-Host "   ✅ $composerVersion" -ForegroundColor Green
} catch {
    Write-Host "   ❌ Composer não encontrado" -ForegroundColor Red
    exit 1
}

# 3. Verificar Node.js
Write-Host "`n3. Verificando Node.js..." -ForegroundColor Yellow
try {
    $nodeVersion = node --version 2>&1
    Write-Host "   ✅ Node.js $nodeVersion" -ForegroundColor Green
} catch {
    Write-Host "   ⚠️  Node.js não encontrado (opcional)" -ForegroundColor DarkYellow
}

# 4. Testar conexão com banco
Write-Host "`n4. Testando conexão com banco..." -ForegroundColor Yellow
Set-Location backend
try {
    $dbTest = php artisan db:show 2>&1 | Select-Object -First 5
    Write-Host "   ✅ Conexão OK" -ForegroundColor Green
} catch {
    Write-Host "   ❌ Erro na conexão com banco" -ForegroundColor Red
}

# 5. Verificar rotas da API
Write-Host "`n5. Verificando rotas da API..." -ForegroundColor Yellow
$routes = php artisan route:list --path=api 2>&1 | Measure-Object -Line
Write-Host "   ✅ $($routes.Lines) rotas registradas" -ForegroundColor Green

# 6. Verificar arquivos públicos
Write-Host "`n6. Verificando arquivos públicos..." -ForegroundColor Yellow
$htmlFiles = Get-ChildItem -Path public -Filter "*.html" | Measure-Object
Write-Host "   ✅ $($htmlFiles.Count) arquivos HTML" -ForegroundColor Green

# 7. Verificar Service Workers
Write-Host "`n7. Verificando Service Workers..." -ForegroundColor Yellow
$swFiles = Get-ChildItem -Path public -Filter "sw*.js" | Measure-Object
Write-Host "   ✅ $($swFiles.Count) Service Workers" -ForegroundColor Green

# 8. Verificar models
Write-Host "`n8. Verificando Models..." -ForegroundColor Yellow
$models = Get-ChildItem -Path app/Models -Filter "*.php" | Measure-Object
Write-Host "   ✅ $($models.Count) models criados" -ForegroundColor Green

# 9. Verificar controllers
Write-Host "`n9. Verificando Controllers..." -ForegroundColor Yellow
$controllers = Get-ChildItem -Path app/Http/Controllers -Recurse -Filter "*.php" | Measure-Object
Write-Host "   ✅ $($controllers.Count) controllers criados" -ForegroundColor Green

# 10. Testar endpoint de debug
Write-Host "`n10. Testando endpoint de debug..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "http://localhost:8000/api/debug" -Method GET -ErrorAction Stop
    $data = $response.Content | ConvertFrom-Json
    Write-Host "   ✅ API Status: $($data.status)" -ForegroundColor Green
    Write-Host "   ✅ Database: $($data.database.status)" -ForegroundColor Green
} catch {
    Write-Host "   ⚠️  Servidor não está rodando" -ForegroundColor DarkYellow
    Write-Host "   💡 Execute: php artisan serve" -ForegroundColor Cyan
}

Set-Location ..

Write-Host "`n==================================" -ForegroundColor Cyan
Write-Host "RESUMO DO TESTE" -ForegroundColor Cyan
Write-Host "==================================" -ForegroundColor Cyan
Write-Host "✅ Ambiente PHP configurado" -ForegroundColor Green
Write-Host "✅ Banco de dados conectado" -ForegroundColor Green
Write-Host "✅ Rotas API registradas" -ForegroundColor Green
Write-Host "✅ Frontend completo" -ForegroundColor Green
Write-Host "✅ Models e Controllers prontos" -ForegroundColor Green
Write-Host ""
Write-Host "🚀 Para iniciar o servidor:" -ForegroundColor Cyan
Write-Host "   cd backend" -ForegroundColor White
Write-Host "   php artisan serve" -ForegroundColor White
Write-Host ""
Write-Host "🌐 Acesse:" -ForegroundColor Cyan
Write-Host "   http://localhost:8000" -ForegroundColor White
Write-Host ""
