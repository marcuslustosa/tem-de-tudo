# Script para incluir o CSS complementar em todos os arquivos HTML
$htmlDir = "backend/public"
$cssFile = "css/classes-faltantes.css"
$cssLink = '<link rel="stylesheet" href="' + $cssFile + '">'

Write-Host "Incluindo CSS complementar em todos os HTMLs..." -ForegroundColor Yellow

$htmlFiles = Get-ChildItem -Path $htmlDir -Filter "*.html" -Recurse -ErrorAction SilentlyContinue

$count = 0
foreach ($file in $htmlFiles) {
    $content = Get-Content $file.FullName -Raw
    
    # Verificar se o CSS já está incluído
    if ($content -notmatch $cssFile) {
        # Encontrar a tag de link do CSS principal
        if ($content -match '(<link rel="stylesheet" href="css/vivo-styles.css">)') {
            $content = $content -replace '(<link rel="stylesheet" href="css/vivo-styles.css">)', '$1' + "`n" + $cssLink
            Set-Content -Path $file.FullName -Value $content -Encoding UTF8
            $count++
        }
    }
}

Write-Host "Arquivos atualizados: $count" -ForegroundColor Green
Write-Host "Concluido!" -ForegroundColor Cyan
