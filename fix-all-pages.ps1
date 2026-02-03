# ========================================
# SCRIPT DE CORREÇÃO MASSIVA - TEM DE TUDO
# Corrige TODAS as 97 páginas HTML do projeto
# ========================================

Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "CORREÇÃO MASSIVA - TEM DE TUDO" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan

$baseDir = "C:\Users\X472795\Desktop\Projetos\tem-de-tudo\backend\public"
$correcoes = 0
$erros = 0

# Obter TODOS os arquivos HTML (exceto os já corrigidos: entrar, cadastro, cadastro-empresa, admin-login)
$allHtmlFiles = Get-ChildItem -Path $baseDir -Filter "*.html" -Recurse | Where-Object {
    $_.Name -notin @("entrar.html", "cadastro.html", "cadastro-empresa.html", "admin-login.html")
}

Write-Host "`nTotal de arquivos HTML para corrigir: $($allHtmlFiles.Count)" -ForegroundColor Yellow
Write-Host "Iniciando correções..." -ForegroundColor Yellow

foreach ($file in $allHtmlFiles) {
    Write-Host "`nProcessando: $($file.Name)" -ForegroundColor Cyan
    
    try {
        $content = Get-Content $file.FullName -Raw -Encoding UTF8
        $modificado = $false
        
        # ========================================
        # 1. ADICIONAR IMPORTS SE NÃO EXISTIR
        # ========================================
        if ($content -notmatch "auth-manager\.js") {
            Write-Host "  → Adicionando imports globais..." -ForegroundColor Gray
            
            # Determinar tipo de autenticação
            $imports = @"
    <script src="/js/config.js"></script>
    <script src="/js/auth-manager.js" defer></script>
    <script src="/js/api-client.js" defer></script>
    <script src="/js/validators.js" defer></script>
    <script src="/js/ui-helpers.js" defer></script>
"@
            
            # Adicionar auth-guard baseado no tipo de página
            if ($file.Name -match "^admin-") {
                $imports += "`n    <script src=`"/js/auth-guard.js`" data-require-admin></script>"
            } elseif ($file.Name -match "^empresa-" -or $file.Name -eq "dashboard-empresa.html") {
                $imports += "`n    <script src=`"/js/auth-guard.js`" data-require-auth=`"empresa`"></script>"
            } elseif ($file.Name -match "^app-" -or $file.Name -eq "dashboard-cliente.html" -or $file.DirectoryName -match "cliente") {
                $imports += "`n    <script src=`"/js/auth-guard.js`" data-require-auth=`"cliente`"></script>"
            }
            
            $content = $content -replace "</head>", "$imports`n</head>"
            $modificado = $true
        }
        
        # ========================================
        # 2. CORRIGIR FUNÇÃO LOGOUT
        # ========================================
        if ($content -match 'function logout\s*\(') {
            Write-Host "  → Padronizando função logout..." -ForegroundColor Gray
            
            $logoutPadrao = @"
function logout() {
    authManager.logout();
}
"@
            
            # Remover toda a função logout antiga (até a chave de fechamento)
            $content = $content -replace 'function logout\s*\([^)]*\)\s*\{[^}]*localStorage[^}]*\}', $logoutPadrao
            $content = $content -replace 'function logout\s*\([^)]*\)\s*\{[^}]*window\.location[^}]*\}', $logoutPadrao
            $modificado = $true
        }
        
        # ========================================
        # 3. CORRIGIR REDIRECIONAMENTOS
        # ========================================
        
        # Corrigir /entrar sem .html
        if ($content -match "window\.location\.href\s*=\s*['"  + '"]/entrar[' + "'" + '"]\s*;') {
            Write-Host "  → Corrigindo redirect /entrar → /entrar.html..." -ForegroundColor Gray
            $content = $content -replace "window\.location\.href\s*=\s*'/entrar'\s*;", "window.location.href = '/entrar.html';"
            $content = $content -replace 'window\.location\.href\s*=\s*"/entrar"\s*;', "window.location.href = '/entrar.html';"
            $modificado = $true
        }
        
        # Corrigir /login.html → /entrar.html (CRÍTICO em cliente/)
        if ($content -match '/login\.html') {
            Write-Host "  → CRÍTICO: Corrigindo /login.html → /entrar.html..." -ForegroundColor Red
            $content = $content -replace "window\.location\.href\s*=\s*'/login\.html'", "window.location.href = '/entrar.html'"
            $content = $content -replace 'window\.location\.href\s*=\s*"/login\.html"', "window.location.href = '/entrar.html'"
            $content = $content -replace "window\.location\.href\s*=\s*'login\.html'", "window.location.href = '/entrar.html'"
            $content = $content -replace 'window\.location\.href\s*=\s*"login\.html"', "window.location.href = '/entrar.html'"
            $modificado = $true
        }
        
        # Corrigir index.html → entrar.html em alguns casos
        if ($file.Name -match "perfil|pontos|historico|cupons" -and $content -match "window\.location\.href\s*=\s*['" + '"']index\.html') {
            Write-Host "  → Corrigindo redirect index.html → /entrar.html..." -ForegroundColor Gray
            $content = $content -replace "window\.location\.href\s*=\s*'index\.html'", "window.location.href = '/entrar.html'"
            $content = $content -replace 'window\.location\.href\s*=\s*"index\.html"', "window.location.href = '/entrar.html'"
            $modificado = $true
        }
        
        # ========================================
        # 4. SUBSTITUIR ALERT() POR SHOWTOAST()
        # ========================================
        # Nota: Removido porque regex complexo causa erros de parsing
        
        # ========================================
        # SALVAR SE HOUVE MODIFICAÇÕES
        # ========================================
        if ($modificado) {
            Set-Content $file.FullName -Value $content -Encoding UTF8 -NoNewline
            Write-Host "  ✓ Corrigido!" -ForegroundColor Green
            $correcoes++
        } else {
            Write-Host "  - Nenhuma correção necessária" -ForegroundColor DarkGray
        }
        
    } catch {
        Write-Host "  ✗ ERRO: $_" -ForegroundColor Red
        $erros++
    }
}

# ========================================
# RELATÓRIO FINAL
# ========================================
Write-Host "`n=====================================" -ForegroundColor Cyan
Write-Host "RELATÓRIO DE CORREÇÃO MASSIVA" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "Arquivos processados: $($allHtmlFiles.Count)" -ForegroundColor White
Write-Host "✓ Arquivos corrigidos: $correcoes" -ForegroundColor Green
Write-Host "✗ Erros encontrados: $erros" -ForegroundColor $(if ($erros -gt 0) { "Red" } else { "Green" })
Write-Host "- Sem correção necessária: $($allHtmlFiles.Count - $correcoes - $erros)" -ForegroundColor DarkGray
Write-Host "=====================================" -ForegroundColor Cyan

Write-Host "`nCORREÇÕES APLICADAS:" -ForegroundColor Yellow
Write-Host "  ✓ Imports globais adicionados em todas as páginas" -ForegroundColor White
Write-Host "  ✓ Auth-guard ativado (cliente/empresa/admin)" -ForegroundColor White
Write-Host "  ✓ Funções logout() padronizadas" -ForegroundColor White
Write-Host "  ✓ Redirecionamentos críticos corrigidos" -ForegroundColor White
Write-Host "    - /login.html → /entrar.html" -ForegroundColor White
Write-Host "    - /entrar → /entrar.html" -ForegroundColor White
Write-Host "    - index.html → /entrar.html (perfil, pontos, etc)" -ForegroundColor White

Write-Host "`n=====================================" -ForegroundColor Cyan
Write-Host "CORREÇÃO MASSIVA CONCLUÍDA!" -ForegroundColor Green
Write-Host "=====================================" -ForegroundColor Cyan
