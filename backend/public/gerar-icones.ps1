# Script para criar ícones PNG usando .NET System.Drawing
# Gera todos os ícones necessários para o PWA

Write-Host "=== Gerador de Ícones Tem de Tudo ===" -ForegroundColor Magenta
Write-Host ""

# Carregar assemblies .NET para manipulação de imagens
Add-Type -AssemblyName System.Drawing

# Cores Vivo
$color1 = [System.Drawing.Color]::FromArgb(111, 26, 182)  # #6F1AB6
$color2 = [System.Drawing.Color]::FromArgb(147, 51, 234)  # #9333EA

# Função para criar ícone com gradiente e emoji
function Create-Icon {
    param(
        [int]$Size,
        [string]$OutputPath,
        [string]$Text = "💎"
    )
    
    try {
        $bitmap = New-Object System.Drawing.Bitmap($Size, $Size)
        $graphics = [System.Drawing.Graphics]::FromImage($bitmap)
        $graphics.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
        $graphics.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
        
        # Criar gradiente roxo
        $rect = New-Object System.Drawing.Rectangle(0, 0, $Size, $Size)
        $brush = New-Object System.Drawing.Drawing2D.LinearGradientBrush(
            $rect,
            $color1,
            $color2,
            [System.Drawing.Drawing2D.LinearGradientMode]::BackwardDiagonal
        )
        
        # Desenhar retângulo com bordas arredondadas
        $radius = [int]($Size * 0.15)
        $path = New-Object System.Drawing.Drawing2D.GraphicsPath
        $path.AddArc(0, 0, $radius * 2, $radius * 2, 180, 90)
        $path.AddArc($Size - $radius * 2, 0, $radius * 2, $radius * 2, 270, 90)
        $path.AddArc($Size - $radius * 2, $Size - $radius * 2, $radius * 2, $radius * 2, 0, 90)
        $path.AddArc(0, $Size - $radius * 2, $radius * 2, $radius * 2, 90, 90)
        $path.CloseFigure()
        
        $graphics.FillPath($brush, $path)
        
        # Adicionar texto/emoji
        $font = New-Object System.Drawing.Font("Arial", ($Size * 0.4), [System.Drawing.FontStyle]::Bold)
        $textBrush = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::White)
        $format = New-Object System.Drawing.StringFormat
        $format.Alignment = [System.Drawing.StringAlignment]::Center
        $format.LineAlignment = [System.Drawing.StringAlignment]::Center
        
        $graphics.DrawString($Text, $font, $textBrush, ($Size / 2), ($Size / 2), $format)
        
        # Salvar
        $bitmap.Save($OutputPath, [System.Drawing.Imaging.ImageFormat]::Png)
        
        $graphics.Dispose()
        $bitmap.Dispose()
        $brush.Dispose()
        $font.Dispose()
        $textBrush.Dispose()
        $path.Dispose()
        
        Write-Host "✅ Criado: $OutputPath" -ForegroundColor Green
        return $true
    }
    catch {
        Write-Host "❌ Erro ao criar $OutputPath : $_" -ForegroundColor Red
        return $false
    }
}

# Criar diretórios se não existirem
$baseDir = "$PSScriptRoot"
$imgDir = Join-Path $baseDir "img"
$iconsDir = Join-Path $baseDir "icons"

if (!(Test-Path $imgDir)) {
    New-Item -ItemType Directory -Path $imgDir -Force | Out-Null
    Write-Host "📁 Criado: /img/" -ForegroundColor Cyan
}

if (!(Test-Path $iconsDir)) {
    New-Item -ItemType Directory -Path $iconsDir -Force | Out-Null
    Write-Host "📁 Criado: /icons/" -ForegroundColor Cyan
}

Write-Host ""
Write-Host "🎨 Gerando ícones..." -ForegroundColor Yellow
Write-Host ""

# Gerar ícones principais
Create-Icon -Size 16 -OutputPath (Join-Path $baseDir "favicon-16x16.png") -Text "💎"
Create-Icon -Size 32 -OutputPath (Join-Path $baseDir "favicon-32x32.png") -Text "💎"
Create-Icon -Size 96 -OutputPath (Join-Path $imgDir "icon-96.png") -Text "T"
Create-Icon -Size 192 -OutputPath (Join-Path $imgDir "icon-192.png") -Text "T"
Create-Icon -Size 512 -OutputPath (Join-Path $imgDir "icon-512.png") -Text "T"

# Ícones para /icons/
Create-Icon -Size 192 -OutputPath (Join-Path $iconsDir "icon-192x192.png") -Text "T"
Create-Icon -Size 512 -OutputPath (Join-Path $iconsDir "icon-512x512.png") -Text "T"

# Ícones especiais
Create-Icon -Size 96 -OutputPath (Join-Path $imgDir "icon-qr.png") -Text "QR"
Create-Icon -Size 96 -OutputPath (Join-Path $imgDir "icon-scan.png") -Text "📷"

# Screenshot placeholder
Create-Icon -Size 540 -OutputPath (Join-Path $imgDir "screenshot-mobile.png") -Text "APP"

Write-Host ""
Write-Host "✅ CONCLUÍDO! Todos os ícones foram gerados." -ForegroundColor Green
Write-Host ""
Write-Host "📊 Arquivos criados:" -ForegroundColor Cyan
Write-Host "  • favicon-16x16.png, favicon-32x32.png (raiz)" -ForegroundColor White
Write-Host "  • /img/icon-96.png, icon-192.png, icon-512.png" -ForegroundColor White
Write-Host "  • /icons/icon-192x192.png, icon-512x512.png" -ForegroundColor White
Write-Host "  • /img/icon-qr.png, icon-scan.png" -ForegroundColor White
Write-Host ""
