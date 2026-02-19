# Script para PADRONIZAR todos os tokens para apenas 'token' e 'user'
# Remove tem_de_tudo_token, tem_de_tudo_user, admin_token, admin_user

Write-Host "=== Padronização de Tokens - Tem de Tudo ===" -ForegroundColor Magenta
Write-Host ""

$substituicoes = @{
    "localStorage.getItem\('tem_de_tudo_token'\)" = "localStorage.getItem('token')"
    "localStorage.setItem\('tem_de_tudo_token'" = "localStorage.setItem('token'"
    "localStorage.removeItem\('tem_de_tudo_token'\)" = "localStorage.removeItem('token')"
    
    "localStorage.getItem\('tem_de_tudo_user'\)" = "localStorage.getItem('user')"
    "localStorage.setItem\('tem_de_tudo_user'" = "localStorage.setItem('user'"
    "localStorage.removeItem\('tem_de_tudo_user'\)" = "localStorage.removeItem('user')"
    
    "localStorage.getItem\('admin_token'\)" = "localStorage.getItem('token')"
    "localStorage.setItem\('admin_token'" = "localStorage.setItem('token'"
    "localStorage.removeItem\('admin_token'\)" = "localStorage.removeItem('token')"
    
    "localStorage.getItem\('admin_user'\)" = "localStorage.getItem('user')"
    "localStorage.setItem\('admin_user'" = "localStorage.setItem('user'"
    "localStorage.removeItem\('admin_user'\)" = "localStorage.removeItem('user')"
    
    "sessionStorage.getItem\('tem_de_tudo_token'\)" = "localStorage.getItem('token')"
    "localStorage.getItem\('auth_token'\)" = "localStorage.getItem('token')"
}

$arquivos = Get-ChildItem -Path "." -Include "*.html","*.js" -Recurse | 
    Where-Object { 
        $_.FullName -notlike "*\node_modules\*" -and 
        $_.FullName -notlike "*\vendor\*" -and
        $_.Name -notlike "*-backup.*"
    }

$totalArquivos = 0
$totalSubstituicoes = 0

foreach ($arquivo in $arquivos) {
    $conteudo = Get-Content -Path $arquivo.FullName -Raw -Encoding UTF8
    $conteudoOriginal = $conteudo
    $mudancas = 0
    
    foreach ($antigo in $substituicoes.Keys) {
        $novo = $substituicoes[$antigo]
        
        if ($conteudo -match $antigo) {
            $qtd = ([regex]::Matches($conteudo, $antigo)).Count
            $conteudo = $conteudo -replace $antigo, $novo
            $mudancas += $qtd
        }
    }
    
    if ($conteudo -ne $conteudoOriginal) {
        Set-Content -Path $arquivo.FullName -Value $conteudo -Encoding UTF8 -NoNewline
        Write-Host "✅ $($arquivo.Name) - $mudancas substituições" -ForegroundColor Green
        $totalArquivos++
        $totalSubstituicoes += $mudancas
    }
}

Write-Host ""
Write-Host "🎉 PADRONIZAÇÃO CONCLUÍDA!" -ForegroundColor Cyan
Write-Host "📊 $totalArquivos arquivos modificados" -ForegroundColor Yellow
Write-Host "🔄 $totalSubstituicoes substituições realizadas" -ForegroundColor Yellow
Write-Host ""
Write-Host "Tokens padronizados:" -ForegroundColor White
Write-Host "  - token (unico)" -ForegroundColor Green
Write-Host "  - user (unico)" -ForegroundColor Green
Write-Host "  - userType (novo - admin/empresa/cliente)" -ForegroundColor Green
Write-Host ""
