# Verificador de classes CSS faltantes
$cssPath = "c:\Users\X472795\Desktop\Projetos\tem-de-tudo\backend\public\css\vivo-styles.css"
$publicPath = "c:\Users\X472795\Desktop\Projetos\tem-de-tudo\backend\public"

Write-Host "Verificando classes CSS..." -ForegroundColor Yellow

# Le o CSS e extrai todas as classes
$cssContent = Get-Content $cssPath -Raw
$cssClasses = [regex]::Matches($cssContent, '\.([a-zA-Z][a-zA-Z0-9_-]+)') | 
              ForEach-Object { $_.Groups[1].Value } | 
              Sort-Object -Unique

Write-Host "Classes definidas no CSS: $($cssClasses.Count)" -ForegroundColor Green

# Testa algumas páginas críticas
$testPages = @(
    "dashboard-cliente.html",
    "admin-usuarios.html", 
    "empresa-dashboard.html",
    "app-perfil.html"
)

$missingClasses = @()

foreach ($page in $testPages) {
    $htmlPath = Join-Path $publicPath $page
    if (Test-Path $htmlPath) {
        $htmlContent = Get-Content $htmlPath -Raw
        
        # Extrai classes do HTML
        $htmlClasses = [regex]::Matches($htmlContent, 'class="([^"]+)"') | 
                       ForEach-Object { $_.Groups[1].Value.Split(' ') } | 
                       Where-Object { $_ -ne '' } |
                       Sort-Object -Unique
        
        # Verifica classes que não existem no CSS
        foreach ($htmlClass in $htmlClasses) {
            if ($htmlClass -notin $cssClasses) {
                $missingClasses += "$page : .$htmlClass"
            }
        }
    }
}

if ($missingClasses.Count -gt 0) {
    Write-Host "`nClasses faltantes encontradas:" -ForegroundColor Red
    $missingClasses | Sort-Object | ForEach-Object { Write-Host "  $_" -ForegroundColor Red }
} else {
    Write-Host "`nTodas as classes estão definidas no CSS!" -ForegroundColor Green
}

Write-Host "`nResumo:" -ForegroundColor Cyan
Write-Host "  CSS Classes: $($cssClasses.Count)" -ForegroundColor White
Write-Host "  Pages tested: $($testPages.Count)" -ForegroundColor White  
Write-Host "  Missing classes: $($missingClasses.Count)" -ForegroundColor White