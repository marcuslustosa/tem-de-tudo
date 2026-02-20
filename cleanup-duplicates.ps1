# ========================================
# SCRIPT DE LIMPEZA - TEM DE TUDO
# Remove arquivos duplicados, backups e testes
# ========================================

$publicPath = "c:\Users\marcu\OneDrive\Desktop\TDD\tem-de-tudo\backend\public"
$cssPath = "c:\Users\marcu\OneDrive\Desktop\TDD\tem-de-tudo\backend\public\css"

Write-Host "================================" -ForegroundColor Cyan
Write-Host "INICIANDO LIMPEZA DE ARQUIVOS" -ForegroundColor Cyan
Write-Host "================================" -ForegroundColor Cyan
Write-Host ""

$deletedCount = 0

# DASHBOARDS DUPLICADOS
Write-Host "[1/8] Removendo dashboards duplicados..." -ForegroundColor Yellow
$dashboards = @(
    "dashboard-cliente-backup.html",
    "dashboard-cliente-novo.html",
    "dashboard-cliente-funcional.html",
    "dashboard-empresa-backup.html",
    "dashboard-empresa-novo.html",
    "dashboard-empresa.html"
)

foreach ($file in $dashboards) {
    $fullPath = Join-Path $publicPath $file
    if (Test-Path $fullPath) {
        Remove-Item $fullPath -Force
        Write-Host "  ✓ Deletado: $file" -ForegroundColor Green
        $deletedCount++
    }
}

# CADASTROS DUPLICADOS
Write-Host "[2/8] Removendo cadastros duplicados..." -ForegroundColor Yellow
$cadastros = @(
    "cadastro-backup.html",
    "cadastro-novo.html",
    "cadastro-unificado.html",
    "cadastro-empresa.html"
)

foreach ($file in $cadastros) {
    $fullPath = Join-Path $publicPath $file
    if (Test-Path $fullPath) {
        Remove-Item $fullPath -Force
        Write-Host "  ✓ Deletado: $file" -ForegroundColor Green
        $deletedCount++
    }
}

# LOGIN/ENTRADA DUPLICADOS
Write-Host "[3/8] Removendo login/entrada duplicados..." -ForegroundColor Yellow
$logins = @(
    "entrar-backup.html",
    "entrar-novo.html",
    "login-unificado.html"
)

foreach ($file in $logins) {
    $fullPath = Join-Path $publicPath $file
    if (Test-Path $fullPath) {
        Remove-Item $fullPath -Force
        Write-Host "  ✓ Deletado: $file" -ForegroundColor Green
        $deletedCount++
    }
}

# APP INÍCIO DUPLICADOS
Write-Host "[4/8] Removendo app-inicio duplicados..." -ForegroundColor Yellow
$inicios = @(
    "app-inicio-novo.html",
    "app-inicio-redirect.html",
    "app-inicio-vivo.html"
)

foreach ($file in $inicios) {
    $fullPath = Join-Path $publicPath $file
    if (Test-Path $fullPath) {
        Remove-Item $fullPath -Force
        Write-Host "  ✓ Deletado: $file" -ForegroundColor Green
        $deletedCount++
    }
}

# PERFIS DUPLICADOS
Write-Host "[5/8] Removendo perfis duplicados..." -ForegroundColor Yellow
$perfis = @(
    "app-editar-perfil-novo.html",
    "app-perfil-novo.html",
    "perfil-backup.html",
    "app-perfil-cliente.html"
)

foreach ($file in $perfis) {
    $fullPath = Join-Path $publicPath $file
    if (Test-Path $fullPath) {
        Remove-Item $fullPath -Force
        Write-Host "  ✓ Deletado: $file" -ForegroundColor Green
        $deletedCount++
    }
}

# OUTROS DUPLICADOS
Write-Host "[6/8] Removendo outros duplicados..." -ForegroundColor Yellow
$outros = @(
    "app-checkin-old.html",
    "app-scanner-vivo.html",
    "app-buscar-vivo.html",
    "index-backup.html"
)

foreach ($file in $outros) {
    $fullPath = Join-Path $publicPath $file
    if (Test-Path $fullPath) {
        Remove-Item $fullPath -Force
        Write-Host "  ✓ Deletado: $file" -ForegroundColor Green
        $deletedCount++
    }
}

# ARQUIVOS DE TESTE
Write-Host "[7/8] Removendo arquivos de teste..." -ForegroundColor Yellow
$testes = @(
    "teste-api.html",
    "teste-empresas.html",
    "teste-login.html",
    "teste-sistema.html",
    "test-login.html",
    "test-login-debug.html",
    "gerar-icones.html"
)

foreach ($file in $testes) {
    $fullPath = Join-Path $publicPath $file
    if (Test-Path $fullPath) {
        Remove-Item $fullPath -Force
        Write-Host "  ✓ Deletado: $file" -ForegroundColor Green
        $deletedCount++
    }
}

# CSS NÃO UTILIZADOS
Write-Host "[8/8] Removendo CSS não utilizados..." -ForegroundColor Yellow

# CSS da pasta principal
$cssFiles = @(
    "theme-escuro.css",
    "vale-bonus-theme.css",
    "global-unified.css",
    "admin-glassmorphism.css"
)

foreach ($file in $cssFiles) {
    $fullPath = Join-Path $cssPath $file
    if (Test-Path $fullPath) {
        Remove-Item $fullPath -Force
        Write-Host "  ✓ Deletado: css/$file" -ForegroundColor Green
        $deletedCount++
    }
}

# Remover pasta old-css-backup inteira
$backupPath = Join-Path $cssPath "old-css-backup"
if (Test-Path $backupPath) {
    Remove-Item $backupPath -Recurse -Force
    Write-Host "  ✓ Deletado: css/old-css-backup/ (pasta completa)" -ForegroundColor Green
    $deletedCount += 5
}

Write-Host ""
Write-Host "================================" -ForegroundColor Cyan
Write-Host "LIMPEZA CONCLUIDA!" -ForegroundColor Green
Write-Host "Total de arquivos deletados: $deletedCount" -ForegroundColor Green
Write-Host "================================" -ForegroundColor Cyan
