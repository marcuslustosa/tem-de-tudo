# Script para verificar arquivos app-*.html que estão sem CSS
$basePath = "c:/Users/marcu/OneDrive/Desktop/TDD/backend/public"
$files = Get-ChildItem -Path $basePath -Filter "app-*.html"

$withoutCSS = @()
$withCSS = @()

foreach ($file in $files) {
    $content = Get-Content $file.FullName -Raw -Encoding UTF8
    if ($content -match 'vivo-styles\.css') {
        $withCSS += $file.Name
    } else {
        $withoutCSS += $file.Name
    }
}

Write-Host "Arquivos app-*.html COM CSS: $($withCSS.Count)" -ForegroundColor Green
Write-Host "Arquivos app-*.html SEM CSS: $($withoutCSS.Count)" -ForegroundColor Red

if ($withoutCSS.Count -gt 0) {
    Write-Host "`nArquivos sem CSS:" -ForegroundColor Yellow
    foreach ($f in $withoutCSS) {
        Write-Host "  - $f" -ForegroundColor Red
    }
}
