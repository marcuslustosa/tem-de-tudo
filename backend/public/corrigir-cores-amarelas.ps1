# Script para corrigir TODAS as cores amarelas para Vivo roxo
# Remove #f1c40f, #e67e22, rgba(241, 196, 15) e substitui por #6F1AB6, #9333EA

Write-Host "=== Correção de Cores Amarelas para Vivo Roxo ===" -ForegroundColor Magenta
Write-Host ""

$coresAmarelas = @(
    'rgba\(241, 196, 15',  # rgba(241, 196, 15, 0.X)
    '#f1c40f',              # Amarelo
    '#e67e22',              # Laranja
    'rgba\(230, 126, 34'   # Laranja rgba
)

$coresVivo = @{
    'rgba\(241, 196, 15' = 'rgba(111, 26, 182'    # Roxo Vivo
    '#f1c40f' = '#6F1AB6'                          # Roxo Vivo
    '#e67e22' = '#9333EA'                          # Roxo Vivo secundário
    'rgba\(230, 126, 34' = 'rgba(147, 51, 234'    # Roxo Vivo secundário
}

$arquivos = Get-ChildItem -Path "." -Filter "*.html" -Recurse | Where-Object { $_.DirectoryName -notlike "*\admin\*" -and $_.DirectoryName -notlike "*\node_modules\*" }

$totalArquivos = 0
$totalSubstituicoes = 0

foreach ($arquivo in $arquivos) {
    $conteudo = Get-Content -Path $arquivo.FullName -Raw -Encoding UTF8
    $conteudoOriginal = $conteudo
    $mudancas = 0
    
    foreach ($corAmarela in $coresAmarelas) {
        $corVivo = $coresVivo[$corAmarela]
        
        if ($conteudo -match $corAmarela) {
            $antes = ([regex]::Matches($conteudo, $corAmarela)).Count
            $conteudo = $conteudo -replace $corAmarela, $corVivo
            $mudancas += $antes
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
Write-Host "🎉 CONCLUÍDO!" -ForegroundColor Cyan
Write-Host "📊 $totalArquivos arquivos corrigidos" -ForegroundColor Yellow
Write-Host "🔄 $totalSubstituicoes substituições de cores" -ForegroundColor Yellow
Write-Host ""
Write-Host "Cores Vivo aplicadas:" -ForegroundColor White
Write-Host "  • #6F1AB6 (Roxo primário)" -ForegroundColor Magenta
Write-Host "  • #9333EA (Roxo secundário)" -ForegroundColor Magenta
Write-Host ""
