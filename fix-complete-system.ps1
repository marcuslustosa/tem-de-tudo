# CORREÇÃO COMPLETA DO SISTEMA TEM DE TUDO
Write-Host "🚀 INICIANDO CORREÇÃO COMPLETA..." -ForegroundColor Green

$publicDir = "c:\Users\X472795\Desktop\Projetos\tem-de-tudo\backend\public"
$backupDir = "$publicDir\backup-$(Get-Date -Format 'yyyyMMdd-HHmmss')"

# Create backup directory
if (!(Test-Path $backupDir)) {
    New-Item -ItemType Directory -Path $backupDir
    Write-Host "📁 Backup criado em: $backupDir" -ForegroundColor Cyan
}

# Get all HTML files
$htmlFiles = Get-ChildItem -Path $publicDir -Filter "*.html"
Write-Host "📊 Encontrados $($htmlFiles.Count) arquivos HTML" -ForegroundColor Cyan

$correctedCount = 0
$errorCount = 0

foreach ($file in $htmlFiles) {
    try {
        Write-Host "🔧 Processando: $($file.Name)" -ForegroundColor Yellow
        
        # Backup original
        $backupPath = Join-Path $backupDir $file.Name
        Copy-Item $file.FullName $backupPath -Force
        
        # Read file content with UTF-8
        $content = Get-Content $file.FullName -Raw -Encoding UTF8
        
        # 1. CORREÇÃO DE ENCODING - Caracteres quebrados
        $content = $content -replace 'Administra??o', 'Administração'
        $content = $content -replace 'Usu?rio', 'Usuário'
        $content = $content -replace 'usu?rio', 'usuário'
        $content = $content -replace 'configura??es', 'configurações'
        $content = $content -replace 'Configura??es', 'Configurações'
        $content = $content -replace 'informa??es', 'informações'
        $content = $content -replace 'Informa??es', 'Informações'
        $content = $content -replace 'promo??es', 'promoções'
        $content = $content -replace 'Promo??es', 'Promoções'
        $content = $content -replace 'promo??o', 'promoção'
        $content = $content -replace 'Promo??o', 'Promoção'
        $content = $content -replace 'relat?rios', 'relatórios'
        $content = $content -replace 'Relat?rios', 'Relatórios'
        $content = $content -replace 'hist?rico', 'histórico'
        $content = $content -replace 'Hist?rico', 'Histórico'
        $content = $content -replace 'c?digo', 'código'
        $content = $content -replace 'C?digo', 'Código'
        $content = $content -replace 'pol?tica', 'política'
        $content = $content -replace 'Pol?tica', 'Política'
        $content = $content -replace 'privacidade', 'privacidade'
        $content = $content -replace 'n?o', 'não'
        $content = $content -replace 'N?o', 'Não'
        $content = $content -replace '??', 'ê'
        $content = $content -replace 'dispon?vel', 'disponível'
        $content = $content -replace 'Dispon?vel', 'Disponível'
        $content = $content -replace 'dispon?veis', 'disponíveis'
        $content = $content -replace 'Dispon?veis', 'Disponíveis'
        $content = $content -replace 'usu?rios', 'usuários'
        $content = $content -replace 'Usu?rios', 'Usuários'
        $content = $content -replace 'exclus?o', 'exclusão'
        $content = $content -replace 'Exclus?o', 'Exclusão'
        $content = $content -replace 'adi??o', 'adição'
        $content = $content -replace 'Adi??o', 'Adição'
        $content = $content -replace 'cria??o', 'criação'
        $content = $content -replace 'Cria??o', 'Criação'
        $content = $content -replace 'edi??o', 'edição'
        $content = $content -replace 'Edi??o', 'Edição'
        $content = $content -replace 'vers?o', 'versão'
        $content = $content -replace 'Vers?o', 'Versão'
        $content = $content -replace 'gest?o', 'gestão'
        $content = $content -replace 'Gest?o', 'Gestão'
        $content = $content -replace 'categorias', 'categorias'
        $content = $content -replace 'categoria', 'categoria'
        $content = $content -replace '?ltimo', 'último'
        $content = $content -replace '?ltima', 'última'
        $content = $content -replace 'voc?', 'você'
        $content = $content -replace 'Voc?', 'Você'
        $content = $content -replace 'atua??o', 'atuação'
        $content = $content -replace 'Atua??o', 'Atuação'
        $content = $content -replace 'tecn?ologia', 'tecnologia'
        $content = $content -replace 'Tecn?ologia', 'Tecnologia'
        $content = $content -replace 'notifica??o', 'notificação'
        $content = $content -replace 'Notifica??o', 'Notificação'
        $content = $content -replace 'notifica??es', 'notificações'
        $content = $content -replace 'Notifica??es', 'Notificações'
        $content = $content -replace 'op??o', 'opção'
        $content = $content -replace 'Op??o', 'Opção'
        $content = $content -replace 'op??es', 'opções'
        $content = $content -replace 'Op??es', 'Opções'
        $content = $content -replace 'avan?ada', 'avançada'
        $content = $content -replace 'Avan?ada', 'Avançada'
        $content = $content -replace 'avan?ado', 'avançado'
        $content = $content -replace 'Avan?ado', 'Avançado'
        $content = $content -replace 'posi??o', 'posição'
        $content = $content -replace 'Posi??o', 'Posição'
        $content = $content -replace 'anima??o', 'animação'
        $content = $content -replace 'Anima??o', 'Animação'
        $content = $content -replace 'integra??o', 'integração'
        $content = $content -replace 'Integra??o', 'Integração'
        $content = $content -replace 'sele??o', 'seleção'
        $content = $content -replace 'Sele??o', 'Seleção'
        $content = $content -replace 'cupon', 'cupom'
        $content = $content -replace 'Cupon', 'Cupom'
        $content = $content -replace 'cupons', 'cupons'
        $content = $content -replace 'Cupons', 'Cupons'
        
        # 2. REMOVER REDIRECIONAMENTOS DE LOOP
        # Remove verificações que redirecionam para entrar.html
        $content = $content -replace 'if \(!token\) \{\s*window\.location\.href = [''"]/?entrar\.html[''"];?\s*return?;?\s*\}', ''
        $content = $content -replace 'if\(!token\)\s*\{\s*window\.location\.href\s*=\s*[''"]/?entrar\.html[''"];?\s*return?;?\s*\}', ''
        
        # Remove outras formas de redirecionamento
        $content = $content -replace 'window\.location\.href\s*=\s*[''"]/?entrar\.html[''"];?', '// Redirecionamento removido'
        $content = $content -replace 'location\.href\s*=\s*[''"]/?entrar\.html[''"];?', '// Redirecionamento removido'
        
        # Remove meta refresh para entrar.html
        $content = $content -replace '<meta\s+http-equiv=[''"]refresh[''"][^>]*url=/?entrar\.html[^>]*>', ''
        
        # 3. CORRIGIR CSS MAL FORMATADO
        # Remove `n estranho das fontes
        $content = $content -replace '>\`n\s*<link rel=[''"]stylesheet[''"]', '>`n    <link rel="stylesheet"'
        $content = $content -replace 'display=swap[''"]>\`n\s*<link rel=[''"]stylesheet[''"]', 'display=swap">`n    <link rel="stylesheet"'
        
        # 4. GARANTIR CSS CORRETO
        # Se não tem vivo-app-oficial.css, adicionar
        if ($content -notmatch 'vivo-app-oficial\.css') {
            if ($content -match '</head>') {
                $cssInsert = '    <link rel="stylesheet" href="/css/vivo-app-oficial.css">`n</head>'
                $content = $content -replace '</head>', $cssInsert
            }
        }
        
        # Remove CSS duplicados
        $content = $content -replace '(<link[^>]+vivo-app-oficial\.css[^>]*>\s*)\1+', '$1'
        
        # 5. CORRIGIR REDIRECIONAMENTOS ADMIN-DASHBOARD
        if ($file.Name -eq "admin-dashboard.html") {
            # Remove redirecionamento para admin-painel.html
            $content = $content -replace '<meta\s+http-equiv=[''"]refresh[''"][^>]*url=admin-painel\.html[^>]*>', ''
            $content = $content -replace 'window\.location\.href\s*=\s*[''"]admin-painel\.html[''"];?', '// Redirecionamento removido'
        }
        
        # 6. ADICIONAR LOGO TEM DE TUDO se não existir
        if ($content -notmatch 'tem-de-tudo-logo') {
            # Adicionar após <body> se não existir
            if ($content -match '<body[^>]*>') {
                $logoHtml = @'
<div class="tem-de-tudo-logo">Tem de<br>Tudo</div>
'@
                # Só adiciona se realmente não tem nenhuma referência ao logo
                if ($content -notmatch 'Tem de.*Tudo') {
                    $content = $content -replace '(<body[^>]*>)', '$1`n' + $logoHtml
                }
            }
        }
        
        # Write corrected content back
        Set-Content -Path $file.FullName -Value $content -Encoding UTF8
        
        $correctedCount++
        Write-Host "✅ Corrigido: $($file.Name)" -ForegroundColor Green
        
    }
    catch {
        Write-Host "❌ Erro em $($file.Name): $($_.Exception.Message)" -ForegroundColor Red
        $errorCount++
    }
}

Write-Host "`n🎉 CORREÇÃO CONCLUÍDA!" -ForegroundColor Green
Write-Host "📊 Estatísticas:" -ForegroundColor Cyan
Write-Host "   ✅ Corrigidos: $correctedCount arquivos" -ForegroundColor Green
Write-Host "   ❌ Erros: $errorCount arquivos" -ForegroundColor Red
Write-Host "   📁 Backup: $backupDir" -ForegroundColor Cyan

Write-Host "`n🔍 CORREÇÕES APLICADAS:" -ForegroundColor Yellow
Write-Host "   🔤 Encoding UTF-8 corrigido" -ForegroundColor White
Write-Host "   🌀 Loops de redirecionamento removidos" -ForegroundColor White  
Write-Host "   🎨 CSS vivo-app-oficial.css aplicado" -ForegroundColor White
Write-Host "   🏷️  Logo 'Tem de Tudo' adicionado" -ForegroundColor White
Write-Host "   🚫 Redirecionamentos problemáticos removidos" -ForegroundColor White