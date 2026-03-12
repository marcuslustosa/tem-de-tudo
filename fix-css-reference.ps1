# Script para adicionar referencia ao CSS unificado em paginas que NAO tem
# Adiciona o link para css/vivo-styles.css

$publicDir = "c:/Users/marcu/OneDrive/Desktop/TDD/backend/public"
$filesToFix = @(
    "admin-criar-usuario-novo.html",
    "admin-dashboard-funcional.html",
    "admin-dashboard.html",
    "admin.html",
    "cadastro-empresa.html",
    "dashboard-cliente-novo.html",
    "dashboard-empresa.html",
    "empresa-dashboard.html",
    "home.html",
    "login.html",
    "painel-empresa.html",
    "sucesso-cadastro-empresa.html"
)

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "ADICIONANDO REFERENCIA AO CSS UNIFICADO" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$cssLink = '<link rel="stylesheet" href="css/vivo-styles.css">'

foreach ($fileName in $filesToFix) {
    $filePath = Join-Path $publicDir $fileName
    
    if (Test-Path $filePath) {
        Write-Host "Processando: $fileName" -ForegroundColor Yellow
        
        $content = Get-Content $filePath -Raw
        
        # Verifica se ja tem a referencia ao vivo-styles
        if ($content -match 'href="css/vivo-styles.css"') {
            Write-Host "  [=] Ja tem referencia ao CSS unificado" -ForegroundColor Gray
        } else {
            # Adiciona a referencia antes de </head>
            if ($content -match '(</head>)') {
                $content = $content -replace '(</head>)', "`n$cssLink`n$1"
                Set-Content -Path $filePath -Value $content -NoNewline
                Write-Host "  [OK] Referencia ao CSS adicionada" -ForegroundColor Green
            } else {
                Write-Host "  [X] Nao encontrou </head>" -ForegroundColor Red
            }
        }
    } else {
        Write-Host "  [X] Arquivo nao encontrado: $fileName" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "VERIFICACAO FINAL" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Conta quantos arquivos tem a referencia
$totalFiles = (Get-ChildItem -Path $publicDir -Filter "*.html").Count
$withCss = 0
Get-ChildItem -Path $publicDir -Filter "*.html" | ForEach-Object { 
    $hasCss = Select-String -Path $_.FullName -Pattern "css/vivo-styles.css" -Quiet
    if ($hasCss) { $withCss++ }
}

Write-Host "Total de arquivos HTML: $totalFiles" -ForegroundColor White
Write-Host "Com referencia ao CSS unificado: $withCss" -ForegroundColor Green
Write-Host "Sem referencia ao CSS: $($totalFiles - $withCss)" -ForegroundColor $(if ($totalFiles - $withCss -eq 0) { "Green" } else { "Red" })

Write-Host ""
Write-Host "Concluido!" -ForegroundColor Green
