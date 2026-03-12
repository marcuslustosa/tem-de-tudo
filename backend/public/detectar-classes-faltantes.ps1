# Script para detectar classes CSS faltantes no projeto
$cssPath = "backend/public/css/vivo-styles.css"
$htmlDir = "backend/public"
$outputPath = "backend/public/css/classes-faltantes.css"

if (-not (Test-Path $cssPath)) {
    Write-Host "ERRO: Arquivo CSS nao encontrado: $cssPath" -ForegroundColor Red
    exit 1
}

Write-Host "Lendo classes do CSS..." -ForegroundColor Yellow
$cssContent = Get-Content $cssPath -Raw -Encoding UTF8
$cssClasses = @()
$cssClasses += [regex]::Matches($cssContent, '\.([a-zA-Z0-9_-]+)\s*\{') | ForEach-Object { $_.Groups[1].Value }
$cssClasses = $cssClasses | Sort-Object -Unique
Write-Host "Classes no CSS: $($cssClasses.Count)" -ForegroundColor Green

Write-Host "Procurando arquivos HTML..." -ForegroundColor Yellow
$htmlFiles = Get-ChildItem -Path $htmlDir -Filter "*.html" -Recurse -ErrorAction SilentlyContinue

Write-Host "Extraindo classes dos HTMLs..." -ForegroundColor Yellow
$htmlClasses = @()
foreach ($file in $htmlFiles) {
    $content = Get-Content $file.FullName -Raw -Encoding UTF8
    $matches = [regex]::Matches($content, 'class="([^"]+)"')
    foreach ($match in $matches) {
        $classes = $match.Groups[1].Value -split '\s+' | Where-Object { $_ -ne '' }
        $htmlClasses += $classes
    }
}
$htmlClasses = $htmlClasses | Sort-Object -Unique
Write-Host "Classes nos HTMLs: $($htmlClasses.Count)" -ForegroundColor Green

Write-Host "Comparando classes..." -ForegroundColor Yellow
$missingClasses = @()
foreach ($class in $htmlClasses) {
    if ($cssClasses -notcontains $class -and $class -notmatch '^(fa[slr]|fas|far|fab|fa-)') {
        $missingClasses += $class
    }
}
$missingClasses = $missingClasses | Sort-Object -Unique

Write-Host ""
Write-Host "RESULTADO:" -ForegroundColor Cyan
Write-Host "Classes faltantes: $($missingClasses.Count)" -ForegroundColor $(if ($missingClasses.Count -gt 0) { "Red" } else { "Green" })

if ($missingClasses.Count -gt 0) {
    Write-Host "Gerando CSS complementar..." -ForegroundColor Yellow
    $cssFaltantes = "/* Classes CSS Faltantes */`n"
    foreach ($class in $missingClasses) {
        $cssFaltantes += ".$class { }`n"
    }
    Set-Content -Path $outputPath -Value $cssFaltantes -Encoding UTF8
    Write-Host "Arquivo gerado: $outputPath" -ForegroundColor Green
}
