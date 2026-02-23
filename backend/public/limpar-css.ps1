# Script para padronizar todas as páginas HTML
# Remove CSS inline e adiciona referência ao CSS unificado

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
    Write-Host "Processando: $($file.Name)"
    
    $content = Get-Content -Path $file.FullName -Raw -Encoding UTF8
    
    # Remove blocos CSS inline
    $content = $content -replace '(?s)<style>.*?</style>', ''
    
    # Remove referências CSS quebradas
    $content = $content -replace '<link rel="stylesheet" href="global-styles.css">', ''
    $content = $content -replace '<script src="global-auth.js"></script>', ''
    $content = $content -replace '<script src="global-navbar.js"></script>', ''
    
    # Adiciona header padrão se não existir
    if ($content -notmatch 'css/vivo-styles.css') {
        # Procura pelo fechamento do head
        $content = $content -replace '</head>', $standardHeader
    }
    
    # Salva arquivo
    Set-Content -Path $file.FullName -Value $content -Encoding UTF8
}

Write-Host "✅ Limpeza concluída! Todas as páginas padronizadas."