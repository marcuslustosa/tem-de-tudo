# Script para corrigir caminhos de CSS em arquivos de subpastas
# Subpasta precisa usar ../css/ ou /css/

$basePath = "c:/Users/marcu/OneDrive/Desktop/TDD/backend/public"

# Arquivos em subpastas que precisam de correção de caminho
$subfolders = @("cliente", "estabelecimento", "admin")

foreach ($folder in $subfolders) {
    $folderPath = Join-Path $basePath $folder
    if (Test-Path $folderPath) {
        $files = Get-ChildItem -Path $folderPath -Filter "*.html"
        
        foreach ($file in $files) {
            $content = Get-Content $file.FullName -Raw -Encoding UTF8
            
            # Verificar se tem referência ao vivo-styles.css com caminho relativo errado
            if ($content -match 'href="css/vivo-styles\.css"' -and $content -notmatch 'href="/css/vivo-styles\.css"') {
                # Substituir css/vivo-styles.css por /css/vivo-styles.css
                $content = $content -replace 'href="css/vivo-styles\.css"', 'href="/css/vivo-styles.css"'
                
                Set-Content -Path $file.FullName -Value $content -Encoding UTF8
                Write-Host "Corrigido caminho em: $($file.Name)" -ForegroundColor Green
            }
        }
    }
}

Write-Host "`nCorreção concluída!" -ForegroundColor Cyan
