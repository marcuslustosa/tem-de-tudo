# Script para padronizar TODAS as paginas com design claro Vivo
$publicPath = "c:\Users\marcu\OneDrive\Desktop\TDD\tem-de-tudo\backend\public"

Write-Host "PADRONIZANDO DESIGN VIVO CLARO EM TODAS AS PAGINAS..." -ForegroundColor Cyan

$htmlFiles = Get-ChildItem -Path $publicPath -Filter "*.html" -Recurse

$contador = 0

foreach ($file in $htmlFiles) {
    $content = Get-Content $file.FullName -Raw -Encoding UTF8
    $originalContent = $content
    
    # 1. Background escuro para branco
    $content = $content -replace "background:\s*linear-gradient\(135deg,\s*#0a0a0f\s+0%,\s*#1a1a2e\s+100%\)", "background: #FFFFFF"
    $content = $content -replace "background:\s*#0a0a0f", "background: #FFFFFF"
    $content = $content -replace "background:\s*#1a1a2e", "background: #F5F5F7"
    
    # 2. Cor de texto branca para escura
    $content = $content -replace "color:\s*#fff;", "color: #1D1D1F;"
    $content = $content -replace "color:\s*white;", "color: #1D1D1F;"
    
    # 3. Cores amarelas antigas para roxo Vivo
    $content = $content -replace "#f1c40f", "#6F1AB6"
    $content = $content -replace "#e67e22", "#9333EA"
    
    # 4. Theme-color meta tag
    $content = $content -replace 'content="#667eea"', 'content="#6F1AB6"'
    $content = $content -replace 'content="#f1c40f"', 'content="#6F1AB6"'
    
    # Salvar apenas se houve mudancas
    if ($content -ne $originalContent) {
        $content | Set-Content $file.FullName -Encoding UTF8 -NoNewline
        $contador++
        Write-Host "OK: $($file.Name)" -ForegroundColor Green
    }
}

Write-Host "CONCLUIDO! $contador arquivos padronizados" -ForegroundColor Green
