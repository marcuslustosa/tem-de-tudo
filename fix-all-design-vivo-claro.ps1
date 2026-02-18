# Script para padronizar TODAS as páginas com design claro Vivo
# Substitui design escuro por design claro profissional

$publicPath = "c:\Users\marcu\OneDrive\Desktop\TDD\tem-de-tudo\backend\public"

Write-Host "🎨 PADRONIZANDO DESIGN VIVO CLARO EM TODAS AS PÁGINAS..." -ForegroundColor Cyan

$htmlFiles = Get-ChildItem -Path $publicPath -Filter "*.html" -Recurse

$contador = 0

foreach ($file in $htmlFiles) {
    $content = Get-Content $file.FullName -Raw -Encoding UTF8
    $originalContent = $content
    
    # 1. Background escuro → branco
    $content = $content -replace "background:\s*linear-gradient\(135deg,\s*#0a0a0f\s+0%,\s*#1a1a2e\s+100%\)", "background: #FFFFFF"
    $content = $content -replace "background:\s*#0a0a0f", "background: #FFFFFF"
    $content = $content -replace "background:\s*#1a1a2e", "background: #F5F5F7"
    
    # 2. Cor de texto branca → escura
    $content = $content -replace "color:\s*#fff;", "color: #1D1D1F;"
    $content = $content -replace "color:\s*white;", "color: #1D1D1F;"
    $content = $content -replace "color:\s*rgba\(255,\s*255,\s*255,", "color: rgba(29, 29, 31,"
    
    # 3. Cores amarelas antigas → roxo Vivo
    $content = $content -replace "#f1c40f", "#6F1AB6"
    $content = $content -replace "#e67e22", "#9333EA"
    $content = $content -replace "rgba\(241,\s*196,\s*15,", "rgba(111, 26, 182,"
    
    # 4. Cards/containers escuros → brancos com sombra
    $content = $content -replace "background:\s*linear-gradient\(135deg,\s*rgba\(26,\s*26,\s*46,\s*[0-9.]+\),\s*rgba\(26,\s*26,\s*46,\s*[0-9.]+\)\)", "background: #FFFFFF; box-shadow: 0 2px 8px rgba(0,0,0,0.06)"
    $content = $content -replace "backdrop-filter:\s*blur\([0-9]+px\);", ""
    
    # 5. Bordas transparentes roxas → sólidas
    $content = $content -replace "border:\s*1px\s+solid\s+rgba\(111,\s*26,\s*182,\s*0\.[0-9]+\)", "border: 1px solid #E5E5E7"
    $content = $content -replace "border:\s*2px\s+solid\s+rgba\(111,\s*26,\s*182,\s*0\.[0-9]+\)", "border: 2px solid #6F1AB6"
    
    # 6. Textos em rgba branco → cinza escuro
    $content = $content -replace "color:\s*rgba\(255,\s*255,\s*255,\s*0\.9\)", "color: #1D1D1F"
    $content = $content -replace "color:\s*rgba\(255,\s*255,\s*255,\s*0\.8\)", "color: #1D1D1F"
    $content = $content -replace "color:\s*rgba\(255,\s*255,\s*255,\s*0\.7\)", "color: #86868B"
    $content = $content -replace "color:\s*rgba\(255,\s*255,\s*255,\s*0\.6\)", "color: #86868B"
    $content = $content -replace "color:\s*rgba\(255,\s*255,\s*255,\s*0\.5\)", "color: #86868B"
    $content = $content -replace "color:\s*rgba\(255,\s*255,\s*255,\s*0\.[0-9]+\)", "color: #86868B"
    
    # 7. Gradientes escuros em backgrounds secundários
    $content = $content -replace "background:\s*linear-gradient\(145deg,\s*#0a0a0f,\s*#1a1a2e\)", "background: #F5F5F7"
    
    # 8. Theme-color meta tag
    $content = $content -replace '<meta\s+name="theme-color"\s+content="#[0-9a-fA-F]+"', '<meta name="theme-color" content="#6F1AB6"'
    
    # Salvar apenas se houve mudanças
    if ($content -ne $originalContent) {
        $content | Set-Content $file.FullName -Encoding UTF8 -NoNewline
        $contador++
        Write-Host "✅ $($file.Name)" -ForegroundColor Green
    }
}

Write-Host "CONCLUIDO! $contador arquivos padronizados com design Vivo claro" -ForegroundColor Green
Write-Host "Design profissional limpo e moderno aplicado" -ForegroundColor Cyan
