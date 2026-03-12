# Script para corrigir links quebrados no projeto

$base = "c:/Users/marcu/OneDrive/Desktop/TDD/backend/public"

# Mapeamento de correções
$corrections = @{
    "test-login.html" = "login.html"
    "admin-suporte.html" = "app-suporte.html"
    "dashboard-estabelecimento.html" = "dashboard-empresa.html"
    "app-inicio-vivo.html" = "app-inicio.html"
    "app-buscar-vivo.html" = "buscar.html"
    "app-scanner-vivo.html" = "app-scanner.html"
    "app-perfil-novo.html" = "app-perfil.html"
    "app-perfil-cliente.html" = "app-perfil.html"
    "app-buscar.html" = "buscar.html"
    "/teste-login.html" = "login.html"
    "empresa-pontos.html" = "app-pontos.html"
    "empresa-perfil.html" = "app-perfil.html"
}

$fixedCount = 0

Get-ChildItem "$base/*.html" | ForEach-Object {
    $content = Get-Content $_.FullName -Raw
    $originalContent = $content
    
    foreach ($brokenLink in $corrections.Keys) {
        $correctLink = $corrections[$brokenLink]
        $content = $content -replace "href=""$brokenLink""", "href=""$correctLink"""
        $content = $content -replace "src=""$brokenLink""", "src=""$correctLink"""
    }
    
    if ($content -ne $originalContent) {
        Set-Content -Path $_.FullName -Value $content -NoNewline
        Write-Host "Corrigido: $($_.Name)" -ForegroundColor Green
        $fixedCount++
    }
}

Write-Host ""
Write-Host "Total arquivos corrigidos: $fixedCount" -ForegroundColor Cyan
