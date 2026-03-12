# Script para corrigir HTML duplicado em arquivos HTML
# Remove o segundo documento HTML de arquivos que tem conteudo duplicado

$publicDir = "c:/Users/marcu/OneDrive/Desktop/TDD/backend/public"
$filesToFix = @(
    "admin-usuarios.html",
    "dashboard-cliente.html", 
    "dashboard-empresa-funcional.html",
    "empresa-clientes.html",
    "empresa-relatorios.html",
    "empresa-scanner.html"
)

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "CORRECAO DE HTML DUPLICADO" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

foreach ($fileName in $filesToFix) {
    $filePath = Join-Path $publicDir $fileName
    
    if (Test-Path $filePath) {
        Write-Host "Processando: $fileName" -ForegroundColor Yellow
        
        $content = Get-Content $filePath -Raw
        
        # Encontra a posicao do primeiro <body>
        $firstBodyPos = $content.IndexOf("<body>")
        
        # Encontra o segundo <body> (comeco do conteudo duplicado)
        $secondBodyPos = $content.IndexOf("<body>", $firstBodyPos + 1)
        
        if ($secondBodyPos -gt 0) {
            # Remove tudo a partir do segundo <body> (inclusive)
            $correctedContent = $content.Substring(0, $secondBodyPos)
            
            # Adiciona o fechamento adequado
            $correctedContent = $correctedContent.TrimEnd()
            
            # Garante que termina com </body></html>
            if (-not $correctedContent.EndsWith("</body>")) {
                $correctedContent = $correctedContent + "`n</body>"
            }
            if (-not $correctedContent.EndsWith("</html>")) {
                $correctedContent = $correctedContent + "`n</html>"
            }
            
            # Salva o arquivo corrigido
            Set-Content -Path $filePath -Value $correctedContent -NoNewline
            
            $originalLines = ($content -split "`n").Count
            $correctedLines = ($correctedContent -split "`n").Count
            $removedLines = $originalLines - $correctedLines
            
            Write-Host "  [OK] Corrigido! Removidas $removedLines linhas duplicadas" -ForegroundColor Green
            Write-Host "       Linhas: $originalLines -> $correctedLines" -ForegroundColor Gray
        } else {
            Write-Host "  [X] Nao encontrou segundo <body>" -ForegroundColor Red
        }
    } else {
        Write-Host "  [X] Arquivo nao encontrado: $fileName" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "VERIFICACAO POS-CORRECAO" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Verifica se ainda ha duplicados
Get-ChildItem -Path $publicDir -Filter "*.html" | ForEach-Object { 
    $lines = Select-String -Path $_.FullName -Pattern "<body>" | Measure-Object | Select-Object -ExpandProperty Count
    if ($lines -gt 1) { 
        Write-Host "  [X] STILL DUPLICATE: $($_.Name) ($lines occurrences)" -ForegroundColor Red 
    } 
}

$allFixed = $true
Get-ChildItem -Path $publicDir -Filter "*.html" | ForEach-Object { 
    $lines = Select-String -Path $_.FullName -Pattern "<body>" | Measure-Object | Select-Object -ExpandProperty Count
    if ($lines -gt 1) { $allFixed = $false }
}

if ($allFixed) {
    Write-Host ""
    Write-Host "TODOS OS ARQUIVOS FORAM CORRIGIDOS!" -ForegroundColor Green
}

Write-Host ""
Write-Host "Correcao concluida!" -ForegroundColor Green
