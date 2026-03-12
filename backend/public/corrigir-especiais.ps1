# Script para corrigir caracteres especiais nos arquivos HTML
$htmlDir = "backend/public"

Write-Host "Corrigindo caracteres especiais..." -ForegroundColor Yellow

$htmlFiles = Get-ChildItem -Path $htmlDir -Filter "*.html" -Recurse
$count = 0

foreach ($file in $htmlFiles) {
    $content = Get-Content $file.FullName -Raw
    $original = $content
    
    $content = $content -replace "OlÂ£", "Olá"
    $content = $content -replace "Ol¢", "Olá"  
    $content = $content -replace "OlÂ", "Olá"
    $content = $content -replace "AÂ§", "Ações"
    $content = $content -replace "A§", "Ações"
    $content = $content -replace "Promoes", "Promoções"
    $content = $content -replace "disponÂ", "disponíveis"
    $content = $content -replace "nÂ", "nível"
    $content = $content -replace "VocÂ", "Você"
    $content = $content -replace "sucÃ©ss", "sucesso"
    $content = $content -replace "Ã©", "é"
    $content = $content -replace "Ã£", "ã"
    $content = $content -replace "Ã³", "ó"
    $content = $content -replace "Ãº", "ú"
    $content = $content -replace "Ã‰", "É"
    
    if ($content -ne $original) {
        Set-Content -Path $file.FullName -Value $content -Encoding UTF8
        $count++
    }
}

Write-Host "Arquivos corrigidos: $count" -ForegroundColor Green
Write-Host "Concluido!" -ForegroundColor Cyan
