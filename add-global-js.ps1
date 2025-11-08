# Script para adicionar global.js nas paginas HTML

Write-Host "Adicionando global.js nas paginas..." -ForegroundColor Cyan

$publicPath = "c:\Users\marcu\OneDrive\Desktop\TDD\tem-de-tudo\backend\public"
$htmlFiles = Get-ChildItem -Path $publicPath -Filter "*.html" -File

$totalUpdated = 0
$globalJsTag = '    <script src="/js/global.js"></script>'

foreach ($file in $htmlFiles) {
    $content = Get-Content $file.FullName -Raw -Encoding UTF8
    
    # Verificar se ja tem o global.js
    if ($content -match '/js/global\.js') {
        continue
    }
    
    # Adicionar antes do </body>
    if ($content -match '</body>') {
        $content = $content -replace '(</body>)', "$globalJsTag`r`n`$1"
        Set-Content -Path $file.FullName -Value $content -Encoding UTF8 -NoNewline
        Write-Host "  OK $($file.Name)" -ForegroundColor Green
        $totalUpdated++
    }
}

Write-Host "`nTotal de arquivos atualizados: $totalUpdated" -ForegroundColor Yellow
Write-Host "Atualizacao concluida!" -ForegroundColor Green
