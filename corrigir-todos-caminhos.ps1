# Script para corrigir TODOS os caminhos de CSS para absoluto
# Isso resolve o problema de abrir arquivos diretamente no navegador

$basePath = "c:/Users/marcu/OneDrive/Desktop/TDD/backend/public"

# Buscar todos os arquivos HTML
$files = Get-ChildItem -Path $basePath -Recurse -Filter "*.html" | Where-Object { 
    $_.FullName -notmatch "components|backup|\.backup" 
}

$fixed = 0

foreach ($file in $files) {
    $content = Get-Content $file.FullName -Raw -Encoding UTF8
    
    # Verificar se tem referência ao vivo-styles.css com caminho relativo
    if ($content -match 'href="css/vivo-styles\.css"' -and $content -notmatch 'href="/css/vivo-styles\.css"') {
        # Substituir css/vivo-styles.css por /css/vivo-styles.css
        $content = $content -replace 'href="css/vivo-styles\.css"', 'href="/css/vivo-styles.css"'
        
        Set-Content -Path $file.FullName -Value $content -Encoding UTF8
        Write-Host "Corrigido: $($file.Name)" -ForegroundColor Green
        $fixed++
    }
}

Write-Host "`nTotal de arquivos corrigidos: $fixed" -ForegroundColor Cyan
