# SCRIPT FINAL PARA LIMPEZA COMPLETA DE CSS ÓRFÃO
# Corrige tags </style> órfãs e estrutura HTML mal formada

$publicPath = "c:\Users\X472795\Desktop\Projetos\tem-de-tudo\backend\public"
$htmlFiles = Get-ChildItem -Path $publicPath -Filter "*.html" -Recurse

Write-Host "🔧 LIMPEZA FINAL DE CSS - Corrigindo tags órfãs..." -ForegroundColor Yellow
Write-Host "📂 Pasta: $publicPath" -ForegroundColor Cyan
Write-Host "📄 Arquivos encontrados: $($htmlFiles.Count)" -ForegroundColor Green

foreach ($file in $htmlFiles) {
    Write-Host "🔍 Processando: $($file.Name)" -ForegroundColor White
    
    $content = Get-Content $file.FullName -Raw -Encoding UTF8
    $originalContent = $content
    
    # Remove tags </style> órfãs (sem <style> correspondente)
    $content = $content -replace '</head></style>', '</head>'
    
    # Remove </style> solto sem contexto adequado
    $content = $content -replace '(?<!<style[^>]*>.*)</style>', ''
    
    # Remove linhas vazias excessivas criadas pela limpeza
    $content = $content -replace "`r?`n\s*`r?`n\s*`r?`n", "`r`n`r`n"
    
    # Só salva se houve mudança
    if ($content -ne $originalContent) {
        Set-Content -Path $file.FullName -Value $content -Encoding UTF8 -NoNewline
        Write-Host "   ✅ CORRIGIDO!" -ForegroundColor Green
    } else {
        Write-Host "   ✓ Já estava correto" -ForegroundColor DarkGreen
    }
}

Write-Host "`n🎉 LIMPEZA FINAL CONCLUÍDA!" -ForegroundColor Green
Write-Host "🔍 Verificando resultados..." -ForegroundColor Yellow

# Verifica se ainda há problemas
$problemFiles = @()
foreach ($file in $htmlFiles) {
    $content = Get-Content $file.FullName -Raw -Encoding UTF8
    if ($content -match '</head></style>' -or $content -match '(?<!<style[^>]*>.*)</style>') {
        $problemFiles += $file.Name
    }
}

if ($problemFiles.Count -gt 0) {
    Write-Host "⚠️ Ainda há problemas em:" -ForegroundColor Red
    foreach ($problem in $problemFiles) {
        Write-Host "   - $problem" -ForegroundColor Red
    }
} else {
    Write-Host "✅ TODAS AS PÁGINAS ESTÃO LIMPAS! Sistema restaurado." -ForegroundColor Green
}