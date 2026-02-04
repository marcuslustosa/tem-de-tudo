# Script para corrigir todas as URLs da API nos arquivos HTML

Write-Host "🔧 Corrigindo URLs da API em todos os arquivos HTML..." -ForegroundColor Cyan

$htmlFiles = Get-ChildItem -Path "backend\public" -Filter "*.html" -Recurse

$contador = 0

foreach ($file in $htmlFiles) {
    $content = Get-Content $file.FullName -Raw -Encoding UTF8
    
    # Padrão antigo: http://localhost:8000
    if ($content -match "localhost:8000") {
        $newContent = $content -replace "localhost:8000", "localhost:8001"
        Set-Content -Path $file.FullName -Value $newContent -Encoding UTF8
        Write-Host "  ✅ $($file.Name)" -ForegroundColor Green
        $contador++
    }
}

Write-Host ""
Write-Host "✨ $contador arquivo(s) corrigido(s)!" -ForegroundColor Green
Write-Host "📡 API agora aponta para: http://localhost:8001" -ForegroundColor Yellow
