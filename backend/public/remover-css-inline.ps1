# Script para remover TODO CSS inline das páginas HTML
$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot

Write-Host "`n🔧 REMOVENDO CSS INLINE DE TODAS AS PÁGINAS..." -ForegroundColor Cyan
Write-Host "═══════════════════════════════════════════════════`n" -ForegroundColor Cyan

$paginasComInline = @(
    'admin-configuracoes.html',
    'admin-create-user.html',
    'admin-entrar.html',
    'admin-login.html',
    'admin-relatorios.html',
    'aplicar-desconto.html',
    'app-premium.html',
    'app.html',
    'cadastro.html',
    'configurar-descontos.html',
    'dashboard-cliente.html',
    'dashboard-empresa.html',
    'dashboard-estabelecimento.html',
    'debug-register.html',
    'empresa-clientes.html',
    'empresa-nova-promocao.html',
    'empresa-qrcode.html',
    'empresa-scanner.html',
    'empresa.html',
    'entrar.html',
    'faq.html',
    'index-premium.html',
    'index.html',
    'meus-descontos.html',
    'preview-glass.html',
    'profile-company.html',
    'register-admin.html',
    'register-company.html',
    'system-status.html'
)

$corrigidas = 0
$erros = 0

foreach($pagina in $paginasComInline) {
    if(-not (Test-Path $pagina)) {
        Write-Host "⚠️  $pagina - não encontrado" -ForegroundColor Yellow
        continue
    }
    
    try {
        $conteudo = Get-Content $pagina -Raw -Encoding UTF8
        
        # Remove blocos <style>...</style> (incluindo multilinhas)
        $conteudoNovo = $conteudo -replace '(?s)\s*<style>.*?</style>\s*', ''
        
        # Remove espaços em branco duplicados resultantes
        $conteudoNovo = $conteudoNovo -replace '\n{3,}', "`n`n"
        
        # Salva o arquivo
        $conteudoNovo | Out-File -FilePath $pagina -Encoding UTF8 -NoNewline
        
        Write-Host "✅ $pagina" -ForegroundColor Green
        $corrigidas++
    }
    catch {
        Write-Host "❌ $pagina - ERRO: $_" -ForegroundColor Red
        $erros++
    }
}

Write-Host "`n═══════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "✅ Corrigidas: $corrigidas páginas" -ForegroundColor Green
if($erros -gt 0) {
    Write-Host "❌ Erros: $erros páginas" -ForegroundColor Red
}
Write-Host "═══════════════════════════════════════════════════`n" -ForegroundColor Cyan

# Agora adiciona CSS externo nas páginas que não têm
Write-Host "🔧 ADICIONANDO CSS EXTERNO NAS PÁGINAS SEM LINK..." -ForegroundColor Cyan
Write-Host "═══════════════════════════════════════════════════`n" -ForegroundColor Cyan

$paginasSemCSS = @(
    'register.html',
    'login.html',
    'estabelecimentos-fixed.html'
)

foreach($pagina in $paginasSemCSS) {
    if(-not (Test-Path $pagina)) {
        Write-Host "⚠️  $pagina - não encontrado (ok, pode não existir)" -ForegroundColor Yellow
        continue
    }
    
    try {
        $conteudo = Get-Content $pagina -Raw -Encoding UTF8
        
        if($conteudo -notmatch '/css/temdetudo-theme\.css') {
            # Adiciona antes de </head>
            $conteudoNovo = $conteudo -replace '(</head>)', "    <link rel=`"stylesheet`" href=`"/css/temdetudo-theme.css`">`n`$1"
            $conteudoNovo | Out-File -FilePath $pagina -Encoding UTF8 -NoNewline
            Write-Host "✅ $pagina - CSS adicionado" -ForegroundColor Green
        }
        else {
            Write-Host "✅ $pagina - já tem CSS" -ForegroundColor Green
        }
    }
    catch {
        Write-Host "❌ $pagina - ERRO: $_" -ForegroundColor Red
    }
}

Write-Host "`n🎉 CONCLUÍDO!" -ForegroundColor Green
Write-Host ""
