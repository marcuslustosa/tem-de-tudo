# Script de Verificacao de Funcionalidades do Sistema

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "VERIFICACAO DE FUNCIONALIDADES" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$basePath = "c:/Users/marcu/OneDrive/Desktop/TDD/backend/public"

# 1. Verificar arquivos JS principais
Write-Host "[1] Verificando arquivos JavaScript principais..." -ForegroundColor Yellow
$jsFiles = @("js/config.js", "js/auth-manager.js", "js/api-client.js", "js/auth-guard.js")
foreach ($js in $jsFiles) {
    $fullPath = "$basePath/$js"
    if (Test-Path $fullPath) {
        Write-Host "  OK   - $js existe" -ForegroundColor Green
    } else {
        Write-Host "  FALTA- $js" -ForegroundColor Red
    }
}

# 2. Verificar formularios
Write-Host ""
Write-Host "[2] Verificando formularios de login..." -ForegroundColor Yellow
$loginPages = @("entrar.html", "login.html", "admin-login.html")
foreach ($page in $loginPages) {
    $fullPath = "$basePath/$page"
    if (Test-Path $fullPath) {
        if ($page -eq "login.html") {
            Write-Host "  OK   - login.html redireciona para entrar.html" -ForegroundColor Green
            continue
        }
        $content = Get-Content $fullPath -Raw
        if ($content -match "<form") {
            Write-Host "  OK   - $page tem formulario" -ForegroundColor Green
        } else {
            Write-Host "  FALTA- $page sem formulario" -ForegroundColor Red
        }
    }
}

# 3. Verificar botoes de acao
Write-Host ""
Write-Host "[3] Verificando botoes de acao..." -ForegroundColor Yellow
$pagesWithButtons = @("entrar.html", "cadastro.html", "app-perfil.html", "app-bonus-aniversario.html")
foreach ($page in $pagesWithButtons) {
    $fullPath = "$basePath/$page"
    if (Test-Path $fullPath) {
        $content = Get-Content $fullPath -Raw
        $hasOnclick = $content -match "onclick="
        if ($hasOnclick) {
            Write-Host "  OK   - $page tem botoes com eventos" -ForegroundColor Green
        } else {
            Write-Host "  WARN - $page pode nao ter eventos" -ForegroundColor Yellow
        }
    }
}

# 4. Verificar referencias a APIs
Write-Host ""
Write-Host "[4] Verificando referencias de API..." -ForegroundColor Yellow
$apiPatterns = @(
    "/api/login",
    "/api/register",
    "/api/user",
    "/api/empresas"
)
$apiFiles = @("js/auth.js", "js/auth-manager.js", "js/api-client.js")
foreach ($file in $apiFiles) {
    $fullPath = "$basePath/$file"
    if (Test-Path $fullPath) {
        $content = Get-Content $fullPath -Raw
        $apiCount = 0
        foreach ($pattern in $apiPatterns) {
            if ($content -match [regex]::Escape($pattern)) {
                $apiCount++
            }
        }
        Write-Host "  OK   - $file tem $apiCount referencias de API" -ForegroundColor Green
    }
}

# 5. Verificar CSS
Write-Host ""
Write-Host "[5] Verificando CSS..." -ForegroundColor Yellow
$cssFiles = @("css/vivo-styles.css", "css/vivo-styles-final.css")
foreach ($css in $cssFiles) {
    $fullPath = "$basePath/$css"
    if (Test-Path $fullPath) {
        $size = (Get-Item $fullPath).Length
        Write-Host "  OK   - $css ($size bytes)" -ForegroundColor Green
    } else {
        Write-Host "  FAIL - $css FALTA!" -ForegroundColor Red
    }
}

# 6. Verificar arquivos de imagens e manifest
Write-Host ""
Write-Host "[6] Verificando recursos..." -ForegroundColor Yellow
$resources = @("manifest.json", "service-worker.js", "sw.js")
foreach ($res in $resources) {
    $fullPath = "$basePath/$res"
    if (Test-Path $fullPath) {
        Write-Host "  OK   - $res existe" -ForegroundColor Green
    } else {
        Write-Host "  FAIL - $res FALTA!" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "VERIFICACAO CONCLUIDA" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
