# Script para aplicar CSS unificado em TODAS as páginas HTML
# Execução: .\apply-unified-css.ps1

$publicPath = "c:\Users\marcu\OneDrive\Desktop\TDD\tem-de-tudo\backend\public"
$cssUnified = "/css/app-unified.css"

# CSS antigos para remover/substituir
$oldCSS = @(
    "/css/mobile-native.css",
    "/css/temdetudo-theme.css"
)

Write-Host "🔧 Aplicando CSS unificado em todas as páginas..." -ForegroundColor Cyan

$htmlFiles = Get-ChildItem -Path $publicPath -Filter "*.html" -File
$totalFiles = $htmlFiles.Count
$updated = 0
$skipped = 0

foreach ($file in $htmlFiles) {
    $filePath = $file.FullName
    $content = Get-Content -Path $filePath -Raw -Encoding UTF8
    $originalContent = $content
    $modified = $false
    
    # Verificar se já tem o CSS unificado
    if ($content -match [regex]::Escape($cssUnified)) {
        Write-Host "  ⏭️  $($file.Name) - Já tem CSS unificado" -ForegroundColor Yellow
        $skipped++
        continue
    }
    
    # Substituir CSS antigos pelo unificado
    foreach ($oldCss in $oldCSS) {
        if ($content -match [regex]::Escape($oldCss)) {
            $content = $content -replace [regex]::Escape($oldCss), $cssUnified
            $modified = $true
            Write-Host "  ✅ $($file.Name) - Substituído $oldCss" -ForegroundColor Green
        }
    }
    
    # Se não tinha nenhum CSS, adicionar após o último <link> ou antes do </head>
    if (-not $modified) {
        # Procurar por último <link> de stylesheet
        if ($content -match '(?s)(.*<link[^>]*rel="stylesheet"[^>]*>)') {
            $lastLink = $matches[1]
            $content = $content -replace [regex]::Escape($lastLink), "$lastLink`n    <link rel=`"stylesheet`" href=`"$cssUnified`">"
            $modified = $true
            Write-Host "  ➕ $($file.Name) - Adicionado CSS unificado" -ForegroundColor Green
        }
        # Ou adicionar antes do </head>
        elseif ($content -match '</head>') {
            $content = $content -replace '</head>', "    <link rel=`"stylesheet`" href=`"$cssUnified`">`n</head>"
            $modified = $true
            Write-Host "  ➕ $($file.Name) - Adicionado CSS unificado antes do </head>" -ForegroundColor Green
        }
    }
    
    # Salvar se modificado
    if ($modified) {
        Set-Content -Path $filePath -Value $content -Encoding UTF8 -NoNewline
        $updated++
    } else {
        Write-Host "  ⚠️  $($file.Name) - Não modificado" -ForegroundColor DarkYellow
        $skipped++
    }
}

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "✅ CONCLUÍDO!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "📊 Total de arquivos: $totalFiles"
Write-Host "✅ Atualizados: $updated" -ForegroundColor Green
Write-Host "⏭️  Ignorados: $skipped" -ForegroundColor Yellow
Write-Host "========================================`n" -ForegroundColor Cyan
