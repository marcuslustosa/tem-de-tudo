# Script de build de assets para produção (Windows PowerShell)
# Uso: .\build-assets.ps1

Write-Host "🔨 Iniciando build de assets..." -ForegroundColor Cyan

# Criar diretório dist
New-Item -ItemType Directory -Force -Path "public\dist" | Out-Null

# Verificar se terser está instalado
$terserInstalled = Get-Command terser -ErrorAction SilentlyContinue

if (-not $terserInstalled) {
    Write-Host "⚠️  terser não encontrado. Instalando..." -ForegroundColor Yellow
    npm install -g terser
    Write-Host "✅ terser instalado. Execute o script novamente." -ForegroundColor Green
    exit 0
}

# Minificar stitch-app.js
Write-Host "📦 Minificando stitch-app.js..." -ForegroundColor Cyan
terser public\js\stitch-app.js `
    --compress `
    --mangle `
    --output public\dist\stitch-app.min.js `
    --comments false

# Calcular tamanhos
$originalSize = (Get-Item "public\js\stitch-app.js").Length
$minifiedSize = (Get-Item "public\dist\stitch-app.min.js").Length
$reduction = [math]::Round((1 - $minifiedSize / $originalSize) * 100, 2)

Write-Host "✅ Minificado: $originalSize bytes → $minifiedSize bytes ($reduction% redução)" -ForegroundColor Green
Write-Host "`n✅ Build concluído! Use /dist/stitch-app.min.js em produção" -ForegroundColor Green
