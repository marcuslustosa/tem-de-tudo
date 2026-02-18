# Script para corrigir TODOS os links errados nas páginas
$publicPath = "c:\Users\marcu\OneDrive\Desktop\TDD\tem-de-tudo\backend\public"

Write-Host "CORRIGINDO LINKS ERRADOS EM TODAS AS PAGINAS..." -ForegroundColor Cyan

$htmlFiles = Get-ChildItem -Path $publicPath -Filter "*.html" -Recurse

$contador = 0

foreach ($file in $htmlFiles) {
    $content = Get-Content $file.FullName -Raw -Encoding UTF8
    $originalContent = $content
    
    # Corrigir links do painel admin
    $content = $content -replace 'href="/admin/empresas"', 'href="admin-empresas.html"'
    $content = $content -replace 'href="/admin/usuarios"', 'href="admin-usuarios.html"'
    $content = $content -replace 'href="/admin/clientes"', 'href="admin-usuarios.html"'
    $content = $content -replace 'href="/admin/relatorios"', 'href="admin-relatorios.html"'
    $content = $content -replace 'href="/admin/configuracoes"', 'href="admin-configuracoes.html"'
    $content = $content -replace 'href="/admin/criar-usuario"', 'href="admin-criar-usuario.html"'
    $content = $content -replace 'href="/admin/promocoes"', 'href="admin-promocoes.html"'
    
    # Corrigir links de autenticação
    $content = $content -replace "window\.location\.href\s*=\s*'/admin-login\.html'", "window.location.href = 'admin-login.html'"
    $content = $content -replace "window\.location\.href\s*=\s*'/admin-painel\.html'", "window.location.href = 'admin-painel.html'"
    $content = $content -replace "window\.location\.href\s*=\s*'/entrar\.html'", "window.location.href = 'entrar.html'"
    
    # Corrigir redirects em logout
    $content = $content -replace "window\.location\.href\s*=\s*'/admin-login'", "window.location.href = 'admin-login.html'"
    $content = $content -replace "window\.location\.href\s*=\s*'admin-login'", "window.location.href = 'admin-login.html'"
    
    if ($content -ne $originalContent) {
        $content | Set-Content $file.FullName -Encoding UTF8 -NoNewline
        $contador++
        Write-Host "OK: $($file.Name)" -ForegroundColor Green
    }
}

Write-Host "CONCLUIDO! $contador arquivos corrigidos" -ForegroundColor Green
