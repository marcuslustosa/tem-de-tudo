# Script para substituir placeholders de logo por imagens reais
$publicPath = "c:\Users\X472795\Desktop\Projetos\tem-de-tudo\backend\public"
$htmlFiles = Get-ChildItem -Path $publicPath -Filter "*.html" -Recurse

Write-Host "Substituindo placeholders de logo..." -ForegroundColor Yellow

$substitutions = @{
    # Logo principal nas páginas
    '<div class="logo">??</div>' = '<div class="logo"><img src="tdt-logo.svg" alt="Tem de Tudo" style="height: 40px;"></div>'
    '<div class="offline-icon">??</div>' = '<div class="offline-icon"><img src="tdt-logo.svg" alt="Tem de Tudo" style="height: 60px;"></div>'
    '<div class="hero-icon" id="heroIcon">??</div>' = '<div class="hero-icon" id="heroIcon"><img src="tdt-logo.svg" alt="Tem de Tudo" style="height: 80px;"></div>'
    '<div class="message-avatar">??</div>' = '<div class="message-avatar"><img src="tdt-logo.svg" alt="Assistente" style="height: 30px; border-radius: 50%;"></div>'
    
    # Títulos com emoji de logo
    '<title>?? Scanner QR - Tem de Tudo</title>' = '<title>📱 Scanner QR - Tem de Tudo</title>'
    '<h1>?? Scanner QR Premium</h1>' = '<h1>📱 Scanner QR Premium</h1>'
    '<h1>?? Clientes Premium</h1>' = '<h1>👥 Clientes Premium</h1>'
    '<title>?? Relatórios - Tem de Tudo</title>' = '<title>📊 Relatórios - Tem de Tudo</title>'
    '<h1>?? Relatórios Premium</h1>' = '<h1>📊 Relatórios Premium</h1>'
    
    # Ícones em relatórios
    '<div class="report-icon" style="color: #667eea;">??</div>' = '<div class="report-icon" style="color: #667eea;"><i class="fas fa-chart-line"></i></div>'
    '<div class="report-icon" style="color: #e74c3c;">??</div>' = '<div class="report-icon" style="color: #e74c3c;"><i class="fas fa-users"></i></div>'
    '<div class="report-icon" style="color: #f39c12;">??</div>' = '<div class="report-icon" style="color: #f39c12;"><i class="fas fa-coins"></i></div>'
    '<div class="report-icon" style="color: #27ae60;">??</div>' = '<div class="report-icon" style="color: #27ae60;"><i class="fas fa-trophy"></i></div>'
    
    # Ícones de estabelecimentos
    "'restaurante': '???'," = "'restaurante': '🍽️',"
    "'lanchonete': '??'," = "'lanchonete': '🍔',"
    "'pizzaria': '??'," = "'pizzaria': '🍕',"
    "'padaria': '??'," = "'padaria': '🥖',"
    "'academia': '??'," = "'academia': '💪',"
    "'farmacia': '??'," = "'farmacia': '💊',"
    "'petshop': '??'," = "'petshop': '🐕',"
    
    # Empresas com logos
    "'🚀 Burger Prime'" = "'🍔 Burger Prime'"
    "'🚀 Pizza Mania'" = "'🍕 Pizza Mania'"
    "'🚀 Gym Fitness'" = "'💪 Gym Fitness'"
    "'🚀 Farmácia Saúde+'" = "'💊 Farmácia Saúde+'"
    "'🚀 Beleza Max'" = "'💄 Beleza Max'"
}

$fixedCount = 0

foreach ($file in $htmlFiles) {
    $content = Get-Content $file.FullName -Raw -Encoding UTF8
    $originalContent = $content
    
    foreach ($search in $substitutions.Keys) {
        $replace = $substitutions[$search]
        $content = $content -replace [regex]::Escape($search), $replace
    }
    
    if ($content -ne $originalContent) {
        Set-Content -Path $file.FullName -Value $content -Encoding UTF8 -NoNewline
        Write-Host "✅ Atualizado: $($file.Name)" -ForegroundColor Green
        $fixedCount++
    }
}

Write-Host "`n🎉 Substituição concluída!" -ForegroundColor Green
Write-Host "📄 Arquivos atualizados: $fixedCount" -ForegroundColor Cyan
Write-Host "🖼️ Logos e ícones aplicados com sucesso!" -ForegroundColor White