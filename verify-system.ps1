$base = "c:/Users/marcu/OneDrive/Desktop/TDD/backend/public"

Write-Host "=== VERIFICACAO DO SISTEMA ===" -ForegroundColor Cyan
Write-Host ""

# CSS
Write-Host "1. Arquivos CSS:" -ForegroundColor Yellow
Get-ChildItem "$base/css/*.css" | ForEach-Object { Write-Host "  OK: $($_.Name)" -ForegroundColor Green }

# HTMLs principais
Write-Host ""
Write-Host "2. Páginas HTML principais:" -ForegroundColor Yellow
$pages = @(
    "entrar.html",
    "login.html", 
    "cadastro.html",
    "admin-login.html",
    "app-inicio.html",
    "app-perfil.html",
    "dashboard-cliente.html",
    "empresa-dashboard.html",
    "admin-painel.html",
    "app-bonus-adesao.html",
    "app-bonus-aniversario.html",
    "app-cartoes-fidelidade.html",
    "app-promocoes.html",
    "app-qrcode.html",
    "app-scanner.html"
)

foreach ($p in $pages) {
    if (Test-Path "$base/$p") {
        Write-Host "  OK: $p" -ForegroundColor Green
    } else {
        Write-Host "  FALTA: $p" -ForegroundColor Red
    }
}

# JS
Write-Host ""
Write-Host "3. Arquivos JavaScript:" -ForegroundColor Yellow
$jsFiles = @(
    "js/config.js",
    "js/auth-manager.js",
    "js/api-client.js",
    "js/auth-guard.js",
    "js/auth.js",
    "js/app-mobile.js"
)

foreach ($j in $jsFiles) {
    if (Test-Path "$base/$j") {
        Write-Host "  OK: $j" -ForegroundColor Green
    } else {
        Write-Host "  FALTA: $j" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "=== VERIFICACAO CONCLUIDA ===" -ForegroundColor Cyan
