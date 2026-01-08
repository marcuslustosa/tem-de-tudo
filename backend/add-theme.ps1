# Script para adicionar temdetudo-theme.css em todas as páginas HTML

$htmlFiles = Get-ChildItem -Path "public" -Filter "*.html" -Recurse

$linkTag = '    <link rel="stylesheet" href="/css/temdetudo-theme.css">'

foreach ($file in $htmlFiles) {
    $content = Get-Content $file.FullName -Raw
    
    # Verificar se ja tem o link
    if ($content -notmatch 'temdetudo-theme\.css') {
        # Adicionar antes do </head>
        if ($content -match '</head>') {
            $content = $content -replace '</head>', "$linkTag`n</head>"
            Set-Content -Path $file.FullName -Value $content -NoNewline
            Write-Host "Atualizado: $($file.Name)" -ForegroundColor Green
        }
    } else {
        Write-Host "Ja atualizado: $($file.Name)" -ForegroundColor Yellow
    }
}

Write-Host ""
Write-Host "Processo concluido!" -ForegroundColor Cyan
Write-Host "Total de arquivos processados: $($htmlFiles.Count)" -ForegroundColor Cyan
