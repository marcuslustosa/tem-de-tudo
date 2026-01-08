# Script para trocar cores AZUL para ROXO i9plus em todos os arquivos
# Mantendo tema escuro do Tem de Tudo

$mapeamentoCores = @{
    '#4a90e2' = '#667eea'  # Azul primário → Roxo primário
    '#357abd' = '#764ba2'  # Azul escuro → Roxo escuro
    '#5b9eea' = '#7e8bf0'  # Azul médio → Roxo médio
    '#3b82f6' = '#667eea'  # Azul Tailwind → Roxo i9plus
    'rgba(74, 144, 226' = 'rgba(102, 126, 234'  # Azul com alpha → Roxo com alpha
    'rgba(53, 122, 189' = 'rgba(118, 75, 162'   # Azul escuro alpha → Roxo escuro alpha
}

# Buscar todos os arquivos HTML, CSS e JS
$arquivos = Get-ChildItem -Path "backend\public" -Include "*.html","*.css","*.js" -Recurse

$totalArquivos = 0
$totalSubstituicoes = 0

foreach ($arquivo in $arquivos) {
    $conteudo = Get-Content $arquivo.FullName -Raw -ErrorAction SilentlyContinue
    
    if ($conteudo) {
        $modificado = $false
        $conteudoNovo = $conteudo
        
        foreach ($corAntiga in $mapeamentoCores.Keys) {
            $corNova = $mapeamentoCores[$corAntiga]
            
            if ($conteudoNovo -match [regex]::Escape($corAntiga)) {
                $conteudoNovo = $conteudoNovo -replace [regex]::Escape($corAntiga), $corNova
                $modificado = $true
                $totalSubstituicoes++
            }
        }
        
        if ($modificado) {
            Set-Content -Path $arquivo.FullName -Value $conteudoNovo -NoNewline
            Write-Host "✓ $($arquivo.Name)" -ForegroundColor Green
            $totalArquivos++
        }
    }
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "CONVERSAO AZUL PARA ROXO I9PLUS CONCLUIDA" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Arquivos modificados: $totalArquivos" -ForegroundColor Yellow
Write-Host "Total de substituicoes: $totalSubstituicoes" -ForegroundColor Yellow
Write-Host ""
Write-Host "Paleta i9plus aplicada com sucesso!" -ForegroundColor Green
