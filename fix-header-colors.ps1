# Script para corrigir cores de texto em headers com gradiente roxo
$publicPath = "c:\Users\marcu\OneDrive\Desktop\TDD\tem-de-tudo\backend\public"

Write-Host "CORRIGINDO CORES DE TEXTO EM HEADERS..." -ForegroundColor Cyan

$htmlFiles = Get-ChildItem -Path $publicPath -Filter "*.html" -Recurse

$contador = 0

foreach ($file in $htmlFiles) {
    $content = Get-Content $file.FullName -Raw -Encoding UTF8
    $originalContent = $content
    
    # Corrigir cor de texto em headers com gradiente roxo
    # Headers com gradiente roxo devem ter texto BRANCO, não escuro
    $content = $content -replace '(background:\s*linear-gradient\(135deg,\s*#6F1AB6[^}]*color:\s*)#1D1D1F', '$1white'
    $content = $content -replace '(background:\s*linear-gradient\(135deg,\s*#6F1AB6,\s*#9333EA[^}]*color:\s*)#1D1D1F', '$1white'
    
    # Botões em headers roxos devem ter texto branco
    $content = $content -replace '(btn-logout[^}]*color:\s*)#1D1D1F', '$1white'
    $content = $content -replace '(btn-back[^}]*color:\s*)#1D1D1F', '$1white'
    $content = $content -replace '(btn-add[^}]*color:\s*)#1D1D1F', '$1white'
    
    # Ícones em headers roxos devem ser brancos
    $content = $content -replace '(\.stat-card \.icon i[^}]*color:\s*)#1D1D1F', '$1white'
    
    if ($content -ne $originalContent) {
        $content | Set-Content $file.FullName -Encoding UTF8 -NoNewline
        $contador++
        Write-Host "OK: $($file.Name)" -ForegroundColor Green
    }
}

Write-Host "CONCLUIDO! $contador arquivos corrigidos" -ForegroundColor Green
