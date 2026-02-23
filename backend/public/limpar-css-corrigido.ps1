# Script CORRIGIDO para limpar CSS corretamente
# Remove CSS inline E conteúdo CSS solto no HTML

$publicDir = "."
$htmlFiles = Get-ChildItem -Path $publicDir -Filter "*.html" -Recurse

$standardHeader = @'
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="css/vivo-styles.css">
<script src="js/config.js"></script>
<script src="js/auth-guard.js"></script>
</head>
'@

foreach ($file in $htmlFiles) {
    Write-Host "Limpando: $($file.Name)"
    
    $content = Get-Content -Path $file.FullName -Raw -Encoding UTF8
    
    # Remove CSS inline com regex mais específico
    $content = $content -replace '(?s)<style[^>]*>.*?</style>', ''
    
    # Remove CSS órfão que ficou solto (entre </head> e <body>)
    $content = $content -replace '(?s)</head>\s*\/\*.*?\*/[^<]*', '</head>'
    $content = $content -replace '(?s)</head>\s*[^<]*{\s*[^}]*}[^<]*', '</head>'
    
    # Remove linhas de CSS soltas (que começam com . # : etc)
    $lines = $content -split "`n"
    $cleanLines = @()
    
    $insideHtml = $false
    foreach ($line in $lines) {
        # Detecta se estamos dentro do HTML propriamente dito
        if ($line -match '<body|<div|<html') {
            $insideHtml = $true
        }
        
        # Se não estamos no HTML e a linha parece CSS, pula
        if (-not $insideHtml -and ($line -match '^\s*[\.#:*]|^\s*--[a-z]|^\s*@media|^\s*}|^\s*{')) {
            continue
        }
        
        $cleanLines += $line
    }
    
    $content = $cleanLines -join "`n"
    
    # Remove referências CSS quebradas
    $content = $content -replace '<link rel="stylesheet" href="global-styles.css">', ''
    $content = $content -replace '<script src="global-auth.js"></script>', ''
    $content = $content -replace '<script src="global-navbar.js"></script>', ''
    
    # Garante que o header padrão está presente
    if ($content -notmatch 'css/vivo-styles.css') {
        $content = $content -replace '</head>', $standardHeader
    }
    
    # Remove duplicatas
    $content = $content -replace '(<link.*?css/vivo-styles\.css.*?>).*?\1', '$1'
    
    # Salva arquivo limpo
    Set-Content -Path $file.FullName -Value $content -Encoding UTF8
}

Write-Host "✅ LIMPEZA CORRIGIDA! Todas as páginas realmente limpas agora."