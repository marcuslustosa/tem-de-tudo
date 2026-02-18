# Script para remover auth-guard.js de TODAS as páginas admin e adicionar auth inline
$publicPath = "c:\Users\marcu\OneDrive\Desktop\TDD\tem-de-tudo\backend\public"

Write-Host "REMOVENDO AUTH-GUARD.JS E ADICIONANDO AUTH INLINE..." -ForegroundColor Cyan

$adminFiles = Get-ChildItem -Path $publicPath -Filter "admin*.html"

$authInline = @'
    <script>
        const token = localStorage.getItem('tem_de_tudo_token');
        if (!token) {
            window.location.href = 'admin-login.html';
        }
    </script>
'@

$contador = 0

foreach ($file in $adminFiles) {
    $content = Get-Content $file.FullName -Raw -Encoding UTF8
    $originalContent = $content
    
    # Remover todos os imports de auth-guard.js e dependências
    $content = $content -replace '<script src="/js/auth-manager\.js"[^>]*></script>\s*', ''
    $content = $content -replace '<script src="/js/api-client\.js"[^>]*></script>\s*', ''
    $content = $content -replace '<script src="/js/validators\.js"[^>]*></script>\s*', ''
    $content = $content -replace '<script src="/js/ui-helpers\.js"[^>]*></script>\s*', ''
    $content = $content -replace '<script src="/js/auth-guard\.js"[^>]*></script>\s*', ''
    
    # Adicionar auth inline se não existir
    if ($content -notmatch "localStorage\.getItem\('tem_de_tudo_token'\)") {
        # Procurar onde inserir (depois do config.js ou antes do </head>)
        if ($content -match '(<script src="/js/config\.js"></script>)') {
            $content = $content -replace '(<script src="/js/config\.js"></script>)', "`$1`n$authInline"
        } elseif ($content -match '(</head>)') {
            $content = $content -replace '(</head>)', "$authInline`n`$1"
        }
    }
    
    if ($content -ne $originalContent) {
        $content | Set-Content $file.FullName -Encoding UTF8 -NoNewline
        $contador++
        Write-Host "OK: $($file.Name)" -ForegroundColor Green
    }
}

Write-Host "CONCLUIDO! $contador arquivos corrigidos" -ForegroundColor Green
