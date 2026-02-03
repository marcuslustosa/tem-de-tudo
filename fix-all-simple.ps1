# CORREÇÃO MASSIVA - TEM DE TUDO
# Versão Simplificada

$baseDir = "C:\Users\X472795\Desktop\Projetos\tem-de-tudo\backend\public"
$correcoes = 0

# Imports padrão
$importsCliente = @'
    <script src="/js/config.js"></script>
    <script src="/js/auth-manager.js" defer></script>
    <script src="/js/api-client.js" defer></script>
    <script src="/js/validators.js" defer></script>
    <script src="/js/ui-helpers.js" defer></script>
    <script src="/js/auth-guard.js" data-require-auth="cliente"></script>
'@

$importsEmpresa = @'
    <script src="/js/config.js"></script>
    <script src="/js/auth-manager.js" defer></script>
    <script src="/js/api-client.js" defer></script>
    <script src="/js/validators.js" defer></script>
    <script src="/js/ui-helpers.js" defer></script>
    <script src="/js/auth-guard.js" data-require-auth="empresa"></script>
'@

$importsAdmin = @'
    <script src="/js/config.js"></script>
    <script src="/js/auth-manager.js" defer></script>
    <script src="/js/api-client.js" defer></script>
    <script src="/js/validators.js" defer></script>
    <script src="/js/ui-helpers.js" defer></script>
    <script src="/js/auth-guard.js" data-require-admin></script>
'@

# Logout padrão
$logoutPadrao = 'function logout() { authManager.logout(); }'

Write-Host "INICIANDO CORREÇÃO MASSIVA..." -ForegroundColor Cyan

# Arquivos a processar (excluindo os já corrigidos)
$skipFiles = @("entrar.html", "cadastro.html", "cadastro-empresa.html", "admin-login.html")
$allFiles = Get-ChildItem -Path $baseDir -Filter "*.html" -Recurse | Where-Object { $_.Name -notin $skipFiles }

foreach ($file in $allFiles) {
    try {
        $content = Get-Content $file.FullName -Raw -Encoding UTF8
        $modified = $false
        
        # 1. Adicionar imports
        if ($content -notmatch "auth-manager\.js") {
            if ($file.Name -like "admin-*") {
                $content = $content -replace "</head>", "$importsAdmin`n</head>"
            } elseif ($file.Name -like "empresa-*" -or $file.Name -eq "dashboard-empresa.html") {
                $content = $content -replace "</head>", "$importsEmpresa`n</head>"
            } else {
                $content = $content -replace "</head>", "$importsCliente`n</head>"
            }
            $modified = $true
        }
        
        # 2. Corrigir /login.html -> /entrar.html
        if ($content -match "/login\.html") {
            $content = $content -replace "/login\.html", "/entrar.html"
            $modified = $true
        }
        
        # 3. Corrigir 'index.html' -> '/entrar.html' em logout
        if ($content -match "window\.location\.href\s*=\s*'index\.html'") {
            $content = $content -replace "window\.location\.href\s*=\s*'index\.html'", "window.location.href = '/entrar.html'"
            $modified = $true
        }
        
        # Salvar se houve modificação
        if ($modified) {
            Set-Content $file.FullName -Value $content -Encoding UTF8 -NoNewline
            Write-Host "OK: $($file.Name)" -ForegroundColor Green
            $correcoes++
        }
    } catch {
        Write-Host "ERRO: $($file.Name) - $_" -ForegroundColor Red
    }
}

Write-Host "`nCONCLUÍDO! Arquivos corrigidos: $correcoes" -ForegroundColor Cyan
