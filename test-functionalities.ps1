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
        Write-Host "  OK - $js existe" -ForegroundColor Green
    } else {
        Write-Host "  FALTA - $js" -ForegroundColor Red
    }
}

# 2. Verificar formularios
Write-Host ""
Write-Host "[2] Verificando formularios de login..." -ForegroundColor Yellow
$loginPages = @("entrar.html", "login.html", "admin-login.html")
foreach ($page in $loginPages) {
    $fullPath = "$basePath/$page"
    if (Test-Path $fullPath) {
        $content = Get-Content $fullPath -Raw
        if ($content -match "<form") {
            Write-Host "  OK - $page tem formulario" -ForegroundColor Green
        } else {
            Write-Host "  FALTA - $page SEM formulario" -ForegroundColor Red
        }
    }
}

# 3. Verificar botões de ação
Write-Host ""
Write-Host "[3] Verificando botões de ação..." -ForegroundColor Yellow
$buttonPatterns = @(
    "onclick.*login",
    "onclick.*register", 
    "onclick.*logout",
    "onclick.*resgatar"
)
$pagesWithButtons = @("entrar.html", "cadastro.html", "app-perfil.html", "app-bonus-aniversario.html")
foreach ($page in $pagesWithButtons) {
    $fullPath = "$basePath/$page"
    if (Test-Path $fullPath) {
        $content = Get-Content $fullPath -Raw
        $hasOnclick = $content -match "onclick="
        if ($hasOnclick) {
            Write-Host "  ✓ $page tem botões com eventos" -ForegroundColor Green
        } else {
            Write-Host "  ⚠ $page pode não ter eventos" -ForegroundColor Yellow
        }
    }
}

# 4. Verificar referências a APIs
Write-Host ""
Write-Host "[4] Verificando referências de API..." -ForegroundColor Yellow
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
        Write-Host "  ✓ $file tem $apiCount referências de API" -ForegroundColor Green
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
        Write-Host "  ✓ $css ($size bytes)" -ForegroundColor Green
    } else {
        Write-Host "  ✗ $css FALTA!" -ForegroundColor Red
    }
}

# 6. Verificar arquivos de imagens e manifest
Write-Host ""
Write-Host "[6] Verificando recursos..." -ForegroundColor Yellow
$resources = @("manifest.json", "service-worker.js", "sw.js")
foreach ($res in $resources) {
    $fullPath = "$basePath/$res"
    if (Test-Path $fullPath) {
        Write-Host "  ✓ $res existe" -ForegroundColor Green
    } else {
        Write-Host "  ✗ $res FALTA!" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "VERIFICAÇÃO CONCLUÍDA" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
