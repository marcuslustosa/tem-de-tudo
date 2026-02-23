# Script Final de Limpeza CSS
$publicPath = "c:\Users\X472795\Desktop\Projetos\tem-de-tudo\backend\public"
$htmlFiles = Get-ChildItem -Path $publicPath -Filter "*.html" -Recurse

Write-Host "Corrigindo tags orfaos de CSS..." -ForegroundColor Yellow

foreach ($file in $htmlFiles) {
    $content = Get-Content $file.FullName -Raw -Encoding UTF8
    $originalContent = $content
    
    # Remove tags </style> orfaos
    $content = $content -replace '</head></style>', '</head>'
    
    # Remove </style> solto
    $content = $content -replace '(?<!<style[^>]*>.*)</style>', ''
    
    if ($content -ne $originalContent) {
        Set-Content -Path $file.FullName -Value $content -Encoding UTF8 -NoNewline
        Write-Host "Corrigido: $($file.Name)" -ForegroundColor Green
    }
}

Write-Host "Limpeza concluida!" -ForegroundColor Green