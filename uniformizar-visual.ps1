# ================================================
# SCRIPT DE UNIFORMIZACAO VISUAL - TEM DE TUDO
# Remove estilos inline e aplica CSS unificado
# ================================================

$publicPath = "C:\Users\marcu\OneDrive\Desktop\TDD\tem-de-tudo\backend\public"
$cssLink = '<link rel="stylesheet" href="/css/style-unificado.css">'
$fontAwesome = '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">'

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "UNIFORMIZACAO VISUAL - TODAS AS PAGINAS" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$processedCount = 0
$errorCount = 0

# Obter todos arquivos HTML (exceto admin por enquanto)
$htmlFiles = Get-ChildItem -Path $publicPath -Filter "*.html" -File | Where-Object { 
    $_.Name -notlike "admin-*" -and 
    $_.Name -ne "admin.html"
}

$totalFiles = $htmlFiles.Count
Write-Host "Total de arquivos para processar: $totalFiles" -ForegroundColor Yellow
Write-Host ""

foreach ($file in $htmlFiles) {
    try {
        $filePath = $file.FullName
        $fileName = $file.Name
        
        Write-Host "Processando: $fileName..." -NoNewline
        
        # Ler conteudo do arquivo
        $content = Get-Content -Path $filePath -Raw -Encoding UTF8
        
        if (-not $content) {
            Write-Host " SKIP (vazio)" -ForegroundColor Yellow
            continue
        }
        
        $modified = $false
        
        # 1. Remover blocos <style>...</style> (incluindo multiline)
        if ($content -match '<style[\s\S]*?</style>') {
            $content = $content -replace '<style[\s\S]*?</style>', ''
            $modified = $true
        }
        
        # 2. Adicionar CSS unificado se nao existir
        if ($content -notmatch 'style-unificado\.css') {
            # Adicionar antes do </head>
            $content = $content -replace '</head>', "$cssLink`n</head>"
            $modified = $true
        }
        
        # 3. Adicionar FontAwesome se nao existir
        if ($content -notmatch 'font-awesome') {
            $content = $content -replace '</head>', "$fontAwesome`n</head>"
            $modified = $true
        }
        
        # 4. Substituir cores cinza inline por classes
        # Background cinza -> usar classe .card
        $content = $content -replace 'style="[^"]*background:\s*#f[0-9a-f]{5}[^"]*"', 'class="card"'
        $content = $content -replace 'style="[^"]*background:\s*#e[0-9a-f]{5}[^"]*"', 'class="card"'
        $content = $content -replace 'style="[^"]*background:\s*#d[0-9a-f]{5}[^"]*"', 'class="card"'
        $content = $content -replace 'style="[^"]*background:\s*#c[0-9a-f]{5}[^"]*"', 'class="card"'
        $content = $content -replace 'style="[^"]*background:\s*rgb\(24[0-9],[^)]+\)[^"]*"', 'class="card"'
        
        # 5. Substituir backgrounds gradientes incorretos
        $content = $content -replace 'style="[^"]*background:\s*linear-gradient[^"]*"', ''
        
        # 6. Remover atributos style vazios
        $content = $content -replace '\s+style=""', ''
        $content = $content -replace '\s+style="\s*"', ''
        
        # 7. Limpar multiplas classes vazias
        $content = $content -replace 'class="\s+"', ''
        
        # 8. Limpar espacos excessivos
        $content = $content -replace '>\s+<', ">`n<"
        
        # Salvar arquivo modificado
        if ($modified) {
            $content | Set-Content -Path $filePath -Encoding UTF8 -NoNewline
            Write-Host " OK" -ForegroundColor Green
            $processedCount++
        } else {
            Write-Host " SEM MUDANCAS" -ForegroundColor Gray
        }
        
    } catch {
        Write-Host " ERRO: $($_.Exception.Message)" -ForegroundColor Red
        $errorCount++
    }
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "UNIFORMIZACAO CONCLUIDA!" -ForegroundColor Green
Write-Host "Arquivos processados: $processedCount/$totalFiles" -ForegroundColor Green
Write-Host "Erros: $errorCount" -ForegroundColor $(if ($errorCount -eq 0) { "Green" } else { "Red" })
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Proximo passo: Corrigir problemas especificos nas paginas individuais" -ForegroundColor Yellow
