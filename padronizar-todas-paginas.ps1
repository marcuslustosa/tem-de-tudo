# Script para padronizar TODAS as páginas HTML
Write-Host "=== PADRONIZANDO TODAS AS PÁGINAS ===" -ForegroundColor Cyan

$publicPath = "C:\Users\X472795\Desktop\Projetos\tem-de-tudo\backend\public"
$htmlFiles = Get-ChildItem -Path $publicPath -Filter "*.html"

Write-Host "Total de arquivos HTML: $($htmlFiles.Count)" -ForegroundColor Yellow

$count = 0
$updated = 0

foreach ($file in $htmlFiles) {
    $count++
    $filePath = $file.FullName
    $content = Get-Content -Path $filePath -Raw -Encoding UTF8
    
    $modified = $false
    
    # Substituir links de CSS antigos pelo novo
    $patterns = @(
        '<link rel="stylesheet" href="/css/mobile-native.css">',
        '<link rel="stylesheet" href="/css/app-unified.css">',
        '<link rel="stylesheet" href="/css/app.css">',
        '<link rel="stylesheet" href="/css/admin.css">',
        '<link rel="stylesheet" href="/css/empresa.css">',
        '<link rel="stylesheet" href="/css/cliente.css">'
    )
    
    foreach ($pattern in $patterns) {
        if ($content -match [regex]::Escape($pattern)) {
            $content = $content -replace [regex]::Escape($pattern), '<link rel="stylesheet" href="/css/sistema-unificado.css">'
            $modified = $true
        }
    }
    
    # Corrigir "GRÁTIS" para "Grátis" ou "Gratuito"
    if ($content -match "GRÁTIS|CRIAR CONTA GRÁTIS") {
        $content = $content -replace "CRIAR CONTA GRÁTIS", "CRIAR CONTA GRATUITA"
        $content = $content -replace "GRÁTIS", "Grátis"
        $modified = $true
    }
    
    # Corrigir placeholders ruins
    if ($content -match 'placeholder="àà') {
        $content = $content -replace 'placeholder="àààààààà"', 'placeholder="Digite sua senha"'
        $modified = $true
    }
    
    if ($content -match 'placeholder="seu@email') {
        $content = $content -replace 'placeholder="seu@email.com.br"', 'placeholder="seu@email.com"'
        $modified = $true
    }
    
    if ($modified) {
        Set-Content -Path $filePath -Value $content -Encoding UTF8 -NoNewline
        $updated++
        Write-Host "  [$count/$($htmlFiles.Count)] $($file.Name) - ATUALIZADO" -ForegroundColor Green
    } else {
        Write-Host "  [$count/$($htmlFiles.Count)] $($file.Name) - OK" -ForegroundColor Gray
    }
}

Write-Host "`n=== CONCLUÍDO ===" -ForegroundColor Cyan
Write-Host "Total processado: $count" -ForegroundColor White
Write-Host "Arquivos atualizados: $updated" -ForegroundColor Green
Write-Host "Sem alterações: $($count - $updated)" -ForegroundColor Gray
