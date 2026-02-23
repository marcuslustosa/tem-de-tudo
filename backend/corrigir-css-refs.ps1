# Script para corrigir referências CSS incorretas
$publicPath = "c:\Users\X472795\Desktop\Projetos\tem-de-tudo\backend\public"
$htmlFiles = Get-ChildItem -Path $publicPath -Filter "*.html" -Recurse

Write-Host "Corrigindo referências CSS..." -ForegroundColor Yellow

$fixedCount = 0

foreach ($file in $htmlFiles) {
    $content = Get-Content $file.FullName -Raw -Encoding UTF8
    $originalContent = $content
    
    # Corrige todas as referências para vivo-global.css
    $content = $content -replace 'href="/css/vivo-global\.css"', 'href="css/vivo-styles.css"'
    $content = $content -replace 'href="/css/vivo-global"', 'href="css/vivo-styles.css"'
    $content = $content -replace 'href="css/vivo-global\.css"', 'href="css/vivo-styles.css"'
    
    if ($content -ne $originalContent) {
        Set-Content -Path $file.FullName -Value $content -Encoding UTF8 -NoNewline
        Write-Host "✅ Corrigido: $($file.Name)" -ForegroundColor Green
        $fixedCount++
    }
}

Write-Host "`n🎉 Correção concluída!" -ForegroundColor Green
Write-Host "📄 Arquivos corrigidos: $fixedCount" -ForegroundColor Cyan
Write-Host "✨ Todas as páginas agora usam css/vivo-styles.css" -ForegroundColor White