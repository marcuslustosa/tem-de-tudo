# =====================================================
# SCRIPT DE CORRECAO FINAL - CORES E ELEMENTOS
# Remove TODAS as cores cinza/gradientes incorretos
# =====================================================

$publicPath = "C:\Users\marcu\OneDrive\Desktop\TDD\tem-de-tudo\backend\public"

Write-Host "======================================" -ForegroundColor Cyan
Write-Host "CORRECAO FINAL DE CORES" -ForegroundColor Cyan
Write-Host "======================================" -ForegroundColor Cyan
Write-Host ""

$processedCount = 0
$changesCount = 0

# Arquivos problemáticos mencionados pelo usuário
$problematicFiles = @(
    "app-avisos.html",
    "app-pontos.html",
    "app-meus-pontos.html",
    "app-cupons.html",
    "app-shop-cupons.html",
    "app-inicio.html",
    "cupons.html",
    "pontos.html"
)

foreach ($fileName in $problematicFiles) {
    $filePath = Join-Path $publicPath $fileName
    
    if (-not (Test-Path $filePath)) {
        Write-Host "SKIP: $fileName (nao encontrado)" -ForegroundColor Yellow
        continue
    }
    
    Write-Host "Processando: $fileName..." -NoNewline
    
    $content = Get-Content -Path $filePath -Raw -Encoding UTF8
    $originalContent = $content
    
    # 1. Remover backgrounds gradientes laranja/amarelo (avisos)
    $content = $content -replace 'background:\s*linear-gradient\([^)]*#[fe][0-9a-f]{5}[^)]*\);?', ''
    
    # 2. Remover backgrounds gradientes cinza
    $content = $content -replace 'background:\s*linear-gradient\([^)]*#[abcdef][0-9a-f]{5}[^)]*\);?', ''
    
    # 3. Remover backgrounds sólidos cinza claro (#f0-#ff)
    $content = $content -replace 'background:\s*#[fF][0-9a-fA-F]{5};?', ''
    
    # 4. Remover backgrounds sólidos cinza médio (#e0-#ef)
    $content = $content -replace 'background:\s*#[eE][0-9a-fA-F]{5};?', ''
    
    # 5. Remover backgrounds sólidos cinza escuro (#d0-#df)
    $content = $content -replace 'background:\s*#[dD][0-9a-fA-F]{5};?', ''
    
    # 6. Remover backgrounds rgb cinza
    $content = $content -replace 'background:\s*rgb\(2[0-5][0-9],\s*2[0-5][0-9],\s*2[0-5][0-9]\);?', ''
    $content = $content -replace 'background:\s*rgba\(2[0-5][0-9],\s*2[0-5][0-9],\s*2[0-5][0-9],[^)]+\);?', ''
    
    # 7. Remover backgrounds #ccc, #ddd, #eee, #f5f5f5, etc
    $content = $content -replace 'background:\s*#[cC]{3,6};?', ''
    $content = $content -replace 'background:\s*#[dD]{3,6};?', ''
    $content = $content -replace 'background:\s*#[eE]{3,6};?', ''
    $content = $content -replace 'background:\s*#[fF]5[fF]5[fF]5;?', ''
    
    # 8. Remover atributos style vazios que sobraram
    $content = $content -replace '\s+style="\s*"', ''
    $content = $content -replace '\s+style=""', ''
    
    # 9. Substituir divs sem classe por divs com .card
    $content = $content -replace '<div\s+style="[^"]*padding[^"]*">', '<div class="card">'
    
    # 10. Limpar espaços múltiplos
    $content = $content -replace '\s{3,}', '  '
    
    # 11. Corrigir títulos roxos em header roxo (mudar para cinza escuro)
    $content = $content -replace 'color:\s*#[67][0-9a-fA-F]{5}\s*!important', 'color: #1D1D1F !important'
    
    # Verificar se houve mudanças
    if ($content -ne $originalContent) {
        $content | Set-Content -Path $filePath -Encoding UTF8 -NoNewline
        Write-Host " OK (corrigido)" -ForegroundColor Green
        $changesCount++
    } else {
        Write-Host " SEM MUDANCAS" -ForegroundColor Gray
    }
    
    $processedCount++
}

# Processar TODAS as outras páginas também
Write-Host ""
Write-Host "Checando TODAS as outras páginas..." -ForegroundColor Yellow

$allFiles = Get-ChildItem -Path $publicPath -Filter "*.html" -File | 
    Where-Object { $problematicFiles -notcontains $_.Name }

foreach ($file in $allFiles) {
    $content = Get-Content -Path $file.FullName -Raw -Encoding UTF8
    $originalContent = $content
    
    # Aplicar mesmas correções
    $content = $content -replace 'background:\s*linear-gradient\([^)]*#[fe][0-9a-f]{5}[^)]*\);?', ''
    $content = $content -replace 'background:\s*linear-gradient\([^)]*#[abcdef][0-9a-f]{5}[^)]*\);?', ''
    $content = $content -replace 'background:\s*#[fFeEdDcC][0-9a-fA-F]{5};?', ''
    $content = $content -replace 'background:\s*rgb\(2[0-5][0-9],\s*2[0-5][0-9],\s*2[0-5][0-9]\);?', ''
    $content = $content -replace '\s+style="\s*"', ''
    $content = $content -replace 'color:\s*#[67][0-9a-fA-F]{5}\s*!important', 'color: #1D1D1F !important'
    
    if ($content -ne $originalContent) {
        $content | Set-Content -Path $file.FullName -Encoding UTF8 -NoNewline
        Write-Host "  Corrigido: $($file.Name)" -ForegroundColor Green
        $changesCount++
    }
}

Write-Host ""
Write-Host "======================================" -ForegroundColor Cyan
Write-Host "CORRECAO FINAL CONCLUIDA!" -ForegroundColor Green
Write-Host "Arquivos corrigidos: $changesCount" -ForegroundColor Green
Write-Host "======================================" -ForegroundColor Cyan
