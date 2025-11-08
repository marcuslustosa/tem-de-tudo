# Script para corrigir caminhos de recursos nas paginas HTML

Write-Host "Corrigindo caminhos de recursos..." -ForegroundColor Cyan

$publicPath = "c:\Users\marcu\OneDrive\Desktop\TDD\tem-de-tudo\backend\public"
$htmlFiles = Get-ChildItem -Path $publicPath -Filter "*.html" -File

$totalFixed = 0

foreach ($file in $htmlFiles) {
    $content = Get-Content $file.FullName -Raw -Encoding UTF8
    $originalContent = $content
    
    # Corrigir logo.png.png para logo.png
    $content = $content -replace '/img/logo\.png\.png', '/img/logo.png'
    
    # Se o arquivo foi modificado
    if ($content -ne $originalContent) {
        Set-Content -Path $file.FullName -Value $content -Encoding UTF8 -NoNewline
        Write-Host "  OK $($file.Name)" -ForegroundColor Green
        $totalFixed++
    }
}

Write-Host "`nTotal de arquivos corrigidos: $totalFixed" -ForegroundColor Yellow
Write-Host "Correcao concluida!" -ForegroundColor Green
