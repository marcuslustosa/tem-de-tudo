# Script para verificar TODOS os arquivos HTML que estão sem CSS vivo-styles
$basePath = "c:/Users/marcu/OneDrive/Desktop/TDD/backend/public"
$files = Get-ChildItem -Path $basePath -Recurse -Filter "*.html" | Where-Object { $_.FullName -notmatch "components|backup|\.backup" }

$withoutCSS = @()
$withCSS = @()

foreach ($file in $files) {
    $content = Get-Content $file.FullName -Raw -Encoding UTF8
    if ($content -match 'vivo-styles\.css') {
        $withCSS += $file.Name
    } else {
        $withoutCSS += $file.FullName.Replace($basePath + "\", "")
    }
}

Write-Host "Total de arquivos HTML COM CSS: $($withCSS.Count)" -ForegroundColor Green
Write-Host "Total de arquivos HTML SEM CSS: $($withoutCSS.Count)" -ForegroundColor Red

if ($withoutCSS.Count -gt 0) {
    Write-Host "`nArquivos sem CSS:" -ForegroundColor Yellow
    foreach ($f in $withoutCSS) {
        Write-Host "  - $f" -ForegroundColor Red
    }
}
