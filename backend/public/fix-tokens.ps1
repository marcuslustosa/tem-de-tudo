Write-Host "Padronizando tokens..." -ForegroundColor Cyan

$substituicoes = @{
    "localStorage.getItem\('tem_de_tudo_token'\)" = "localStorage.getItem('token')"
    "localStorage.setItem\('tem_de_tudo_token'" = "localStorage.setItem('token'"
    "localStorage.removeItem\('tem_de_tudo_token'\)" = "localStorage.removeItem('token')"
    "localStorage.getItem\('tem_de_tudo_user'\)" = "localStorage.getItem('user')"
    "localStorage.setItem\('tem_de_tudo_user'" = "localStorage.setItem('user'"
    "localStorage.removeItem\('tem_de_tudo_user'\)" = "localStorage.removeItem('user')"
    "sessionStorage.getItem\('tem_de_tudo_token'\)" = "localStorage.getItem('token')"
    "localStorage.getItem\('auth_token'\)" = "localStorage.getItem('token')"
    "localStorage.setItem\('auth_token'" = "localStorage.setItem('token'"
}

$arquivos = Get-ChildItem -Path "." -Include "*.html","*.js" -Recurse | Where-Object { $_.FullName -notlike "*node_modules*" }

$total = 0

foreach ($arquivo in $arquivos) {
    $conteudo = Get-Content -Path $arquivo.FullName -Raw -Encoding UTF8
    $original = $conteudo
    $mudancas = 0
    
    foreach ($antigo in $substituicoes.Keys) {
        $novo = $substituicoes[$antigo]
        if ($conteudo -match $antigo) {
            $qtd = ([regex]::Matches($conteudo, $antigo)).Count
            $conteudo = $conteudo -replace $antigo, $novo
            $mudancas += $qtd
        }
    }
    
    if ($conteudo -ne $original) {
        Set-Content -Path $arquivo.FullName -Value $conteudo -Encoding UTF8 -NoNewline
        Write-Host "OK: $($arquivo.Name) - $mudancas mudancas" -ForegroundColor Green
        $total += $mudancas
    }
}

Write-Host ""
Write-Host "CONCLUIDO! Total: $total substituicoes" -ForegroundColor Yellow
