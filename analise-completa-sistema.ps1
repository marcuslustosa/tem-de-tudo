# Análise Completa do Sistema - Engenharia de Software Sênior

$base = "c:/Users/marcu/OneDrive/Desktop/TDD/backend/public"

Write-Host "=== ANALISE COMPLETA - ENGENHARIA DE SOFTWARE ===" -ForegroundColor Cyan
Write-Host ""

# 1. VERIFICAR CSS EM TODAS AS PAGINAS
Write-Host "1. VERIFICANDO CSS EM TODAS AS PAGINAS..." -ForegroundColor Yellow
$cssRefs = 0
$noCss = @()
Get-ChildItem "$base/*.html" | ForEach-Object {
    $content = Get-Content $_.FullName -Raw
    if ($content -match "vivo-styles\.css") {
        $cssRefs++
    } else {
        $noCss += $_.Name
    }
}
Write-Host "   Total com vivo-styles.css: $cssRefs de 124" -ForegroundColor Green
if ($noCss.Count -gt 0) {
    Write-Host "   SEM CSS: $($noCss -join ', ')" -ForegroundColor Red
}

# 2. VERIFICAR ENCODING
Write-Host ""
Write-Host "2. VERIFICANDO ENCODING (caracteres especiais)..." -ForegroundColor Yellow
$encodingIssues = @()
Get-ChildItem "$base/*.html" | ForEach-Object {
    $content = Get-Content $_.FullName -Raw
    if ($content -match "\?|Ã|Â|ï¿½") {
        $encodingIssues += $_.Name
    }
}
Write-Host "   Arquivos com problemas de encoding: $($encodingIssues.Count)" -ForegroundColor $(if($encodingIssues.Count -eq 0){"Green"}else{"Red"})
if ($encodingIssues.Count -gt 0 -and $encodingIssues.Count -le 10) {
    $encodingIssues | ForEach-Object { Write-Host "      - $_" -ForegroundColor Red }
} elseif ($encodingIssues.Count -gt 10) {
    Write-Host "      Primeiros 10: $($encodingIssues[0..9])" -ForegroundColor Red
}

# 3. VERIFICAR DUPLICIDADES
Write-Host ""
Write-Host "3. VERIFICANDO ARQUIVOS DUPLICADOS..." -ForegroundColor Yellow
$duplicates = Get-ChildItem "$base/*.html" | Group-Object Name | Where-Object Count -gt 1
Write-Host "   Duplicados: $($duplicates.Count)" -ForegroundColor $(if($duplicates.Count -eq 0){"Green"}else{"Red"})

# 4. VERIFICAR LINKS (href) PARA PAGINAS QUE EXISTEM
Write-Host ""
Write-Host "4. VERIFICANDO LINKS (href)..." -ForegroundColor Yellow
$brokenLinks = @()
$allFiles = Get-ChildItem "$base/*.html" | Select-Object -ExpandProperty Name

Get-ChildItem "$base/*.html" | ForEach-Object {
    $content = Get-Content $_.FullName -Raw
    $matches = [regex]::Matches($content, 'href="([^"]+\.html)"')
    foreach ($match in $matches) {
        $link = $match.Groups[1].Value
        if ($link -notmatch "^http" -and $link -notmatch "^#" -and $link -notmatch "^javascript" -and $link -notmatch "^tel:" -and $link -notmatch "^mailto:") {
            $fullPath = "$base/$link"
            if (-not (Test-Path $fullPath)) {
                $brokenLinks += "$($_.Name) -> $link"
            }
        }
    }
}
Write-Host "   Links quebrados: $($brokenLinks.Count)" -ForegroundColor $(if($brokenLinks.Count -eq 0){"Green"}else{"Red"})
if ($brokenLinks.Count -gt 0 -and $brokenLinks.Count -le 10) {
    $brokenLinks | ForEach-Object { Write-Host "      - $_" -ForegroundColor Red }
}

# 5. VERIFICAR ARQUIVOS JS
Write-Host ""
Write-Host "5. VERIFICANDO ARQUIVOS JS..." -ForegroundColor Yellow
$jsFiles = (Get-ChildItem "$base/js/*.js").Count
Write-Host "   Arquivos JS: $jsFiles" -ForegroundColor Green

# 6. VERIFICAR PWA
Write-Host ""
Write-Host "6. VERIFICANDO PWA..." -ForegroundColor Yellow
$manifest = Test-Path "$base/manifest.json"
$sw = Test-Path "$base/service-worker.js"
Write-Host "   manifest.json: $manifest" -ForegroundColor $(if($manifest){"Green"}else{"Red"})
Write-Host "   service-worker.js: $sw" -ForegroundColor $(if($sw){"Green"}else{"Red"})

# 7. VERIFICAR CORES VIPUS
Write-Host ""
Write-Host "7. VERIFICANDO CORES VIPUS (#9b59b6)..." -ForegroundColor Yellow
$cssContent = Get-Content "$base/css/vivo-styles.css" -Raw
if ($cssContent -match "#9b59b6") {
    Write-Host "   CSS com cores Vipus: OK" -ForegroundColor Green
} else {
    Write-Host "   CSS com cores Vipus: FALTA" -ForegroundColor Red
}

# 8. VERIFICAR theme-color
Write-Host ""
Write-Host "8. VERIFICANDO theme-color..." -ForegroundColor Yellow
$wrongThemeColor = @()
Get-ChildItem "$base/*.html" | ForEach-Object {
    $content = Get-Content $_.FullName -Raw
    if ($content -match 'theme-color.*content="#6[A-Fa-f0-9]"') {
        $wrongThemeColor += $_.Name
    }
}
Write-Host "   theme-color errado (Vivo): $($wrongThemeColor.Count)" -ForegroundColor $(if($wrongThemeColor.Count -eq 0){"Green"}else{"Red"})

Write-Host ""
Write-Host "=== FIM DA ANALISE ===" -ForegroundColor Cyan
