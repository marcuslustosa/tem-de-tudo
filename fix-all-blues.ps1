# Script para substituir TODAS as cores azuis por roxo premium
$files = Get-ChildItem "c:\Users\X472795\Desktop\Projetos\tem-de-tudo\backend\public" -Filter "*.html"

$replacements = @{
    '#3b82f6' = '#667eea'
    '#2563eb' = '#667eea'
    '#1e40af' = '#764ba2'
    '#60a5fa' = '#8b9cf5'
}

$totalChanges = 0

foreach ($file in $files) {
    $content = Get-Content $file.FullName -Raw -Encoding UTF8
    $originalContent = $content
    
    foreach ($old in $replacements.Keys) {
        $new = $replacements[$old]
        $content = $content -replace [regex]::Escape($old), $new
    }
    
    if ($content -ne $originalContent) {
        $content | Set-Content $file.FullName -Encoding UTF8 -NoNewline
        $totalChanges++
        Write-Host "Atualizado: $($file.Name)" -ForegroundColor Green
    }
}

Write-Host "Total de arquivos atualizados: $totalChanges" -ForegroundColor Cyan
