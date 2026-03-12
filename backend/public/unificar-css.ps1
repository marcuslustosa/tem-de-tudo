# Script para unificar os arquivos CSS em um único arquivo

Write-Host "Unificando arquivos CSS..." -ForegroundColor Yellow

$css1 = "backend/public/css/vivo-styles.css"
$css2 = "backend/public/css/classes-faltantes.css"
$output = "backend/public/css/vivo-styles.css"

# Verificar se os arquivos existem
if (-not (Test-Path $css1)) {
    Write-Host "ERRO: $css1 nao encontrado" -ForegroundColor Red
    exit 1
}

if (-not (Test-Path $css2)) {
    Write-Host "ERRO: $css2 nao encontrado" -ForegroundColor Red
    exit 1
}

# Ler o conteúdo dos dois arquivos
Write-Host "Lendo arquivos CSS..." -ForegroundColor Yellow
$conteudo1 = Get-Content $css1 -Raw -Encoding UTF8
$conteudo2 = Get-Content $css2 -Raw -Encoding UTF8

# Unir os arquivos (classes faltantes no final)
$conteudoUnificado = $conteudo1 + "`n`n" + $conteudo2

# Salvar o arquivo unificado
Set-Content -Path $output -Value $conteudoUnificado -Encoding UTF8

Write-Host "CSS unificado salvo em: $output" -ForegroundColor Green
Write-Host "Tamanho total: $([math]::Round($conteudoUnificado.Length / 1KB, 2)) KB" -ForegroundColor Cyan

# Remover a referência ao arquivo classes-faltantes de todos os HTMLs
Write-Host "" 
Write-Host "Removendo ссылка para classes-faltantes.css dos HTMLs..." -ForegroundColor Yellow

$htmlFiles = Get-ChildItem -Path "backend/public" -Filter "*.html" -Recurse
$count = 0

foreach ($file in $htmlFiles) {
    $content = Get-Content $file.FullName -Raw
    
    # Remover a linha do classes-faltantes.css se existir
    if ($content -match 'css/classes-faltantes\.css') {
        $content = $content -replace '<link rel="stylesheet" href="css/classes-faltantes\.css">', ''
        $content = $content -replace "`n`n`n", "`n`n"  # Limpar linhas vazias extras
        Set-Content -Path $file.FullName -Value $content -Encoding UTF8
        $count++
    }
}

Write-Host "Arquivos atualizados: $count" -ForegroundColor Green
Write-Host ""
Write-Host "CONCLUIDO! CSS unico criado e todos os HTMLs atualizados." -ForegroundColor Green
