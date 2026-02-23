# Script simples para substituir logos
$publicPath = "c:\Users\X472795\Desktop\Projetos\tem-de-tudo\backend\public"
$htmlFiles = Get-ChildItem -Path $publicPath -Filter "*.html" -Recurse

Write-Host "Substituindo logos e icones..." -ForegroundColor Yellow

$fixedCount = 0

foreach ($file in $htmlFiles) {
    $content = Get-Content $file.FullName -Raw -Encoding UTF8
    $originalContent = $content
    
    # Substitui logos principais
    $content = $content -replace '<div class="logo">\?\?</div>', '<div class="logo"><img src="tdt-logo.svg" alt="Tem de Tudo" style="height: 40px;"></div>'
    $content = $content -replace '<div class="offline-icon">\?\?</div>', '<div class="offline-icon"><img src="tdt-logo.svg" alt="Tem de Tudo" style="height: 60px;"></div>'
    $content = $content -replace '<div class="hero-icon" id="heroIcon">\?\?</div>', '<div class="hero-icon" id="heroIcon"><img src="tdt-logo.svg" alt="Tem de Tudo" style="height: 80px;"></div>'
    $content = $content -replace '<div class="message-avatar">\?\?</div>', '<div class="message-avatar"><i class="fas fa-robot"></i></div>'
    
    # Substitui titulos
    $content = $content -replace '<title>\?\? Scanner QR - Tem de Tudo</title>', '<title>Scanner QR - Tem de Tudo</title>'
    $content = $content -replace '<h1>\?\? Scanner QR Premium</h1>', '<h1><i class="fas fa-qrcode"></i> Scanner QR Premium</h1>'
    $content = $content -replace '<h1>\?\? Clientes Premium</h1>', '<h1><i class="fas fa-users"></i> Clientes Premium</h1>'
    $content = $content -replace '<title>\?\? Relat', '<title>Relat'
    $content = $content -replace '<h1>\?\? Relat', '<h1><i class="fas fa-chart-bar"></i> Relat'
    
    # Substitui icones de relatorio
    $content = $content -replace '<div class="report-icon" style="color: #667eea;">\?\?</div>', '<div class="report-icon" style="color: #667eea;"><i class="fas fa-chart-line"></i></div>'
    $content = $content -replace '<div class="report-icon" style="color: #e74c3c;">\?\?</div>', '<div class="report-icon" style="color: #e74c3c;"><i class="fas fa-users"></i></div>'
    $content = $content -replace '<div class="report-icon" style="color: #f39c12;">\?\?</div>', '<div class="report-icon" style="color: #f39c12;"><i class="fas fa-coins"></i></div>'
    $content = $content -replace '<div class="report-icon" style="color: #27ae60;">\?\?</div>', '<div class="report-icon" style="color: #27ae60;"><i class="fas fa-trophy"></i></div>'
    
    if ($content -ne $originalContent) {
        Set-Content -Path $file.FullName -Value $content -Encoding UTF8 -NoNewline
        Write-Host "Atualizado: $($file.Name)" -ForegroundColor Green
        $fixedCount++
    }
}

Write-Host "Concluido! Arquivos atualizados: $fixedCount" -ForegroundColor Green