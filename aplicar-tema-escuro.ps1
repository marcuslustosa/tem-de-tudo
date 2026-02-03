# Script para atualizar todas as páginas HTML com tema escuro
# Tem de Tudo - Aplicação de tema unificado

$publicPath = "C:\Users\X472795\Desktop\Projetos\tem-de-tudo\backend\public"
$cssLink = '<link rel="stylesheet" href="/css/theme-escuro.css">'
$charset = '<meta charset="UTF-8">'

Write-Host "=== ATUALIZANDO PÁGINAS COM TEMA ESCURO ===" -ForegroundColor Cyan

# Lista de páginas principais que já foram atualizadas manualmente
$skipPages = @(
    "entrar.html",
    "entrar-novo.html",
    "entrar-backup.html",
    "cadastro.html",
    "cadastro-novo.html",
    "cadastro-backup.html",
    "admin-login.html",
    "admin-login-novo.html",
    "admin-login-backup.html",
    "admin-dashboard.html",
    "admin-dashboard-novo.html",
    "admin-dashboard-backup.html",
    "dashboard-cliente.html",
    "dashboard-cliente-novo.html",
    "dashboard-cliente-backup.html",
    "dashboard-empresa.html",
    "dashboard-empresa-novo.html",
    "dashboard-empresa-backup.html",
    "perfil-backup.html",
    "index-backup.html"
)

Get-ChildItem -Path $publicPath -Filter "*.html" | Where-Object {
    $skipPages -notcontains $_.Name
} | ForEach-Object {
    $file = $_.FullName
    $fileName = $_.Name
    
    try {
        $content = Get-Content $file -Raw -Encoding UTF8
        
        # Verificar se já tem o tema escuro
        if ($content -match 'theme-escuro\.css') {
            Write-Host "  ✓ $fileName já tem tema escuro" -ForegroundColor Green
            return
        }
        
        # Adicionar charset UTF-8 se não tiver
        if ($content -notmatch '<meta charset') {
            $content = $content -replace '(<head[^>]*>)', "`$1`n    $charset"
        }
        
        # Adicionar link do CSS tema escuro depois do charset
        if ($content -match '</head>') {
            $content = $content -replace '(</head>)', "    $cssLink`n`$1"
        }
        
        # Salvar com encoding UTF-8
        $content | Out-File -FilePath $file -Encoding UTF8 -NoNewline
        
        Write-Host "  ✓ $fileName atualizado" -ForegroundColor Green
        
    } catch {
        Write-Host "  ✗ Erro ao atualizar $fileName : $_" -ForegroundColor Red
    }
}

Write-Host "`n=== CONCLUÍDO ===" -ForegroundColor Cyan
Write-Host "Todas as páginas foram atualizadas com o tema escuro!" -ForegroundColor Green
