# Script para adicionar link CSS externo em TODAS as páginas HTML
$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot

$pages = Get-ChildItem *.html | Where-Object {$_.Name -notlike '*old*' -and $_.Name -notlike '*test*' -and $_.Name -notlike '*fixed*'}

$fixed = 0
$alreadyGood = 0

foreach($page in $pages) {
    $content = Get-Content $page.FullName -Raw -Encoding UTF8
    
    # Se já tem o link CSS, pula
    if($content -match '/css/temdetudo-theme\.css') {
        $alreadyGood++
        continue
    }
    
    # Adiciona o link CSS depois do Font Awesome
    $content = $content -replace `
        '(<link rel="stylesheet" href="https://cdnjs\.cloudflare\.com/ajax/libs/font-awesome/6\.5\.1/css/all\.min\.css">)', `
        "`$1`n    <link rel=`"stylesheet`" href=`"/css/temdetudo-theme.css`">"
    
    # Remove tags <style> inline se existirem
    $content = $content -replace '(?s)<style>.*?</style>\s*', ''
    
    $content | Out-File -FilePath $page.FullName -Encoding UTF8 -NoNewline
    Write-Host "✅ $($page.Name)" -ForegroundColor Green
    $fixed++
}

Write-Host "`n🎉 Concluído!" -ForegroundColor Cyan
Write-Host "   Corrigidas: $fixed páginas" -ForegroundColor Green
Write-Host "   Já corretas: $alreadyGood páginas" -ForegroundColor Yellow
Write-Host "   Total: $($fixed + $alreadyGood) páginas" -ForegroundColor White
