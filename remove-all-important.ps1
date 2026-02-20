# Limpeza agressiva de !important - mantém APENAS em utility classes
$cssPath = "backend/public/css/style-unificado.css"
$content = Get-Content $cssPath -Raw -Encoding UTF8

Write-Host "=== LIMPEZA AGRESSIVA DE !IMPORTANT ===" -ForegroundColor Cyan

# Conta antes
$beforeCount = ([regex]::Matches($content, "!important")).Count
Write-Host "Antes: $beforeCount !important" -ForegroundColor Yellow

# Divide o arquivo em seções
$lines = $content -split "`n"
$utilityStart = -1
$resultLines = @()

for ($i = 0; $i -lt $lines.Count; $i++) {
    $line = $lines[$i]
    
    # Encontra onde começam as utility classes
    if ($line -match "^/\* === UTILITY CLASSES ===") {
        $utilityStart = $i
        Write-Host "Utility classes começam na linha $i" -ForegroundColor Green
    }
    
    # Se ainda não chegou nas utility classes, remove !important
    if ($utilityStart -lt 0 -or $i -lt $utilityStart) {
        $line = $line -replace '\s*!important(?=\s*;)', ''
    }
    
    $resultLines += $line
}

# Junta tudo de volta
$newContent = $resultLines -join "`n"

# Salva
$newContent | Out-File $cssPath -Encoding UTF8 -NoNewline

# Conta depois
$afterCount = ([regex]::Matches($newContent, "!important")).Count
$removed = $beforeCount - $afterCount

Write-Host "Depois: $afterCount !important" -ForegroundColor Green
Write-Host "Removidos: $removed !important" -ForegroundColor Magenta
Write-Host "[OK] CSS limpo!" -ForegroundColor Green
