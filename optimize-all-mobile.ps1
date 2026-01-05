# Script para adicionar otimizações mobile em TODOS os arquivos HTML
# Tem de Tudo - Otimização Mobile Universal

$publicPath = "backend\public"

# Buscar todos os arquivos HTML
$htmlFiles = Get-ChildItem -Path $publicPath -Filter "*.html" -Recurse

Write-Host "Encontrados $($htmlFiles.Count) arquivos HTML" -ForegroundColor Cyan
Write-Host ""

$filesModified = 0
$filesSkipped = 0

foreach ($file in $htmlFiles) {
    Write-Host "Processando: $($file.Name)" -ForegroundColor Yellow
    
    $content = Get-Content $file.FullName -Raw -Encoding UTF8
    $modified = $false
    
    # Verificar e adicionar meta tags mobile
    if ($content -notmatch 'mobile-web-app-capable') {
        if ($content -match '(<meta charset[^>]+>)') {
            $metaTags = "`n    <meta name=`"viewport`" content=`"width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no`">`n    <meta name=`"apple-mobile-web-app-capable`" content=`"yes`">`n    <meta name=`"apple-mobile-web-app-status-bar-style`" content=`"black-translucent`">`n    <meta name=`"mobile-web-app-capable`" content=`"yes`">"
            $content = $content -replace '(<meta charset[^>]+>)', "`$1$metaTags"
            $modified = $true
            Write-Host "  + Meta tags adicionadas" -ForegroundColor Green
        }
    }
    
    # Verificar e adicionar CSS mobile
    if ($content -notmatch 'mobile-native\.css') {
        if ($content -match '(font-awesome[^>]+>)') {
            $cssLink = "`n    <link rel=`"stylesheet`" href=`"/css/mobile-native.css`">"
            $content = $content -replace '(font-awesome[^>]+>)', "`$1$cssLink"
            $modified = $true
            Write-Host "  + CSS mobile adicionado" -ForegroundColor Green
        }
    }
    
    # Verificar e adicionar manifest
    if ($content -notmatch 'manifest\.json') {
        if ($content -match '(mobile-native\.css[^>]+>)') {
            $manifestLink = "`n    <link rel=`"manifest`" href=`"/manifest.json`">"
            $content = $content -replace '(mobile-native\.css[^>]+>)', "`$1$manifestLink"
            $modified = $true
            Write-Host "  + Manifest adicionado" -ForegroundColor Green
        }
    }
    
    # Atualizar viewport existente
    if ($content -match '<meta name="viewport" content="width=device-width, initial-scale=1\.0">') {
        $content = $content -replace '<meta name="viewport" content="width=device-width, initial-scale=1\.0">', '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">'
        $modified = $true
        Write-Host "  + Viewport otimizado" -ForegroundColor Green
    }
    
    if ($modified) {
        Set-Content -Path $file.FullName -Value $content -Encoding UTF8 -NoNewline
        $filesModified++
        Write-Host "  >> MODIFICADO <<" -ForegroundColor Cyan
    } else {
        $filesSkipped++
    }
    
    Write-Host ""
}

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Total: $($htmlFiles.Count) | Modificados: $filesModified | Já otimizados: $filesSkipped" -ForegroundColor White
Write-Host "========================================" -ForegroundColor Cyan

