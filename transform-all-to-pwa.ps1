# SCRIPT COMPLETO - TRANSFORMAR TODAS AS PÁGINAS EM PWA VIVO
Write-Host "🚀 INICIANDO TRANSFORMAÇÃO COMPLETA PARA PWA VIVO..." -ForegroundColor Green

$publicDir = "c:\Users\X472795\Desktop\Projetos\tem-de-tudo\backend\public"

# Template PWA base
$pwaHead = @'
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{TITLE}} - Tem de Tudo</title>
    <meta name="description" content="{{DESCRIPTION}}">
    <meta name="theme-color" content="#6F1AB6">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/css/vivo-app-oficial.css">
</head>
'@

# Correções de caracteres
$correcoes = @{
    'Administra??o' = 'Administração'
    'administra??o' = 'administração'
    'Usu?rio' = 'Usuário'
    'usu?rio' = 'usuário'
    'usu?rios' = 'usuários'
    'Usu?rios' = 'Usuários'
    'configura??es' = 'configurações'
    'Configura??es' = 'Configurações'
    'informa??es' = 'informações'
    'Informa??es' = 'Informações'
    'promo??o' = 'promoção'
    'Promo??o' = 'Promoção'
    'promo??es' = 'promoções'
    'Promo??es' = 'Promoções'
    'relat?rio' = 'relatório'
    'Relat?rio' = 'Relatório'
    'relat?rios' = 'relatórios'
    'Relat?rios' = 'Relatórios'
    'hist?rico' = 'histórico'
    'Hist?rico' = 'Histórico'
    'c?digo' = 'código'
    'C?digo' = 'Código'
    'pol?tica' = 'política'
    'Pol?tica' = 'Política'
    'n?o' = 'não'
    'N?o' = 'Não'
    'dispon?vel' = 'disponível'
    'Dispon?vel' = 'Disponível'
    'dispon?veis' = 'disponíveis'
    'Dispon?veis' = 'Disponíveis'
    'gest?o' = 'gestão'
    'Gest?o' = 'Gestão'
    'voc?' = 'você'
    'Voc?' = 'Você'
    'categorias' = 'categorias'
    'categoria' = 'categoria'
    '?ltimo' = 'último'
    '?ltima' = 'última'
    'notifica??o' = 'notificação'
    'Notifica??o' = 'Notificação'
    'notifica??es' = 'notificações'
    'Notifica??es' = 'Notificações'
    'op??o' = 'opção'
    'Op??o' = 'Opção'
    'op??es' = 'opções'
    'Op??es' = 'Opções'
    'avan?ada' = 'avançada'
    'Avan?ada' = 'Avançada'
    'avan?ado' = 'avançado'
    'Avan?ado' = 'Avançado'
    'posi??o' = 'posição'
    'Posi??o' = 'Posição'
    'integra??o' = 'integração'
    'Integra??o' = 'Integração'
    'sele??o' = 'seleção'
    'Sele??o' = 'Seleção'
    'cria??o' = 'criação'
    'Cria??o' = 'Criação'
    'vers??o' = 'versão'
    'Vers??o' = 'Versão'
    'edi??o' = 'edição'
    'Edi??o' = 'Edição'
    'exclus??o' = 'exclusão'
    'Exclus??o' = 'Exclusão'
    'adi??o' = 'adição'
    'Adi??o' = 'Adição'
    'user-scalable=n?o' = 'user-scalable=no'
    'user-scalable=nÃ£o' = 'user-scalable=no'
    'tecn?ologia' = 'tecnologia'
    'Tecn?ologia' = 'Tecnologia'
    'anima??o' = 'animação'
    'Anima??o' = 'Animação'
    'atua??o' = 'atuação'
    'Atua??o' = 'Atuação'
    '??' = 'ê'
    'ã' = 'ã'
    'ç' = 'ç'
    'é' = 'é'
    'í' = 'í'
    'ó' = 'ó'
    'ú' = 'ú'
    'â' = 'â'
    'ô' = 'ô'
    'à' = 'à'
    '>`n' = '>'
    '`n<' = '<'
    '>`n    <link' = '>
    <link'
}

# Bottom Navigation PWA
$bottomNav = @'
    <!-- PWA Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="app.html" class="nav-item {{APP_ACTIVE}}">
            <i class="fas fa-home"></i>
            <span>Início</span>
        </a>
        <a href="app-pontos.html" class="nav-item {{PONTOS_ACTIVE}}">
            <i class="fas fa-gem"></i>
            <span>Pontos</span>
        </a>
        <a href="app-qrcode.html" class="nav-item {{QRCODE_ACTIVE}}">
            <i class="fas fa-qrcode"></i>
            <span>QR Code</span>
        </a>
        <a href="app-promocoes.html" class="nav-item {{PROMOCOES_ACTIVE}}">
            <i class="fas fa-gift"></i>
            <span>Ofertas</span>
        </a>
        <a href="app-perfil.html" class="nav-item {{PERFIL_ACTIVE}}">
            <i class="fas fa-user"></i>
            <span>Perfil</span>
        </a>
    </nav>
'@

# Get all HTML files
$htmlFiles = Get-ChildItem -Path $publicDir -Filter "*.html" | Where-Object { 
    $_.Name -notlike "*-novo.html" 
}

Write-Host "📊 Processando $($htmlFiles.Count) arquivos HTML..." -ForegroundColor Cyan

$processedCount = 0
$errorCount = 0

foreach ($file in $htmlFiles) {
    try {
        Write-Host "🔧 Processando: $($file.Name)" -ForegroundColor Yellow
        
        # Read content
        $content = Get-Content $file.FullName -Raw -Encoding UTF8
        
        # 1. CORREÇÃO DE CARACTERES
        foreach ($key in $correcoes.Keys) {
            $content = $content -replace [regex]::Escape($key), $correcoes[$key]
        }
        
        # 2. NORMALIZAR HEAD
        # Remove head existente malformado
        $content = $content -replace '(?s)<head[^>]*>.*?</head>', '{{HEAD_PLACEHOLDER}}'
        
        # 3. DETECTAR TIPO DE PÁGINA PARA TITLE E DESCRIPTION
        $pageTitle = "Sistema"
        $pageDescription = "Sistema de fidelidade Tem de Tudo"
        $needsBottomNav = $false
        
        if ($file.Name -like "app-*") {
            $pageTitle = "App"
            $pageDescription = "Aplicativo de fidelidade"
            $needsBottomNav = $true
        } elseif ($file.Name -like "admin-*") {
            $pageTitle = "Admin"
            $pageDescription = "Painel administrativo"
        } elseif ($file.Name -like "empresa-*") {
            $pageTitle = "Empresa"
            $pageDescription = "Portal empresarial"
        } elseif ($file.Name -eq "index.html") {
            $pageTitle = "Início"
            $pageDescription = "Sistema de fidelidade Tem de Tudo"
        } elseif ($file.Name -eq "entrar.html") {
            $pageTitle = "Entrar"
            $pageDescription = "Faça login em sua conta"
        }
        
        # 4. CRIAR HEAD PWA
        $newHead = $pwaHead -replace '{{TITLE}}', $pageTitle
        $newHead = $newHead -replace '{{DESCRIPTION}}', $pageDescription
        
        # 5. SUBSTITUIR HEAD
        $content = $content -replace '{{HEAD_PLACEHOLDER}}', $newHead
        
        # 6. ADICIONAR BOTTOM NAV SE FOR APP
        if ($needsBottomNav -and $content -notmatch 'bottom-nav') {
            # Determinar página ativa
            $navActive = $bottomNav -replace '{{APP_ACTIVE}}', ''
            $navActive = $navActive -replace '{{PONTOS_ACTIVE}}', ''
            $navActive = $navActive -replace '{{QRCODE_ACTIVE}}', ''
            $navActive = $navActive -replace '{{PROMOCOES_ACTIVE}}', ''
            $navActive = $navActive -replace '{{PERFIL_ACTIVE}}', ''
            
            if ($file.Name -eq "app.html") {
                $navActive = $navActive -replace 'app\.html" class="nav-item "', 'app.html" class="nav-item active"'
            } elseif ($file.Name -eq "app-pontos.html") {
                $navActive = $navActive -replace 'app-pontos\.html" class="nav-item "', 'app-pontos.html" class="nav-item active"'
            } elseif ($file.Name -eq "app-qrcode.html") {
                $navActive = $navActive -replace 'app-qrcode\.html" class="nav-item "', 'app-qrcode.html" class="nav-item active"'
            } elseif ($file.Name -eq "app-promocoes.html") {
                $navActive = $navActive -replace 'app-promocoes\.html" class="nav-item "', 'app-promocoes.html" class="nav-item active"'
            } elseif ($file.Name -eq "app-perfil.html") {
                $navActive = $navActive -replace 'app-perfil\.html" class="nav-item "', 'app-perfil.html" class="nav-item active"'
            }
            
            # Adicionar antes de </body>
            $content = $content -replace '</body>', "$navActive`n</body>"
        }
        
        # 7. LIMPAR CSS DUPLICADOS E MALFORMADOS
        # Remove links CSS duplicados
        $content = $content -replace '(?i)(<link[^>]+vivo-app-oficial\.css[^>]*>\s*)+', '<link rel="stylesheet" href="/css/vivo-app-oficial.css">'
        
        # Remove CSS inválidos
        $content = $content -replace '<link[^>]*href="[^"]*inexistente[^"]*"[^>]*>', ''
        $content = $content -replace '<link[^>]*href="[^"]*modern-theme[^"]*"[^>]*>', ''
        $content = $content -replace '<link[^>]*href="[^"]*mobile-native[^"]*"[^>]*>', ''
        $content = $content -replace '<link[^>]*href="[^"]*temdetudo-theme[^"]*"[^>]*>', ''
        
        # 8. CORRIGIR ESTRUTURA HTML SE NECESSÁRIO
        if ($content -notmatch '<html[^>]*lang=') {
            $content = $content -replace '<html[^>]*>', '<html lang="pt-BR">'
        }
        
        # 9. REMOVER REDIRECIONAMENTOS PROBLEMÁTICOS
        $content = $content -replace 'window\.location\.href\s*=\s*[''"]/?entrar\.html[''"];?', '// Redirecionamento removido'
        $content = $content -replace '<meta\s+http-equiv=[''"]refresh[''"][^>]*url=/?entrar\.html[^>]*>', ''
        $content = $content -replace '<meta\s+http-equiv=[''"]refresh[''"][^>]*url=admin-painel\.html[^>]*>', ''
        
        # Write corrected content
        Set-Content -Path $file.FullName -Value $content -Encoding UTF8
        
        $processedCount++
        Write-Host "✅ Processado: $($file.Name)" -ForegroundColor Green
        
    }
    catch {
        Write-Host "❌ Erro em $($file.Name): $($_.Exception.Message)" -ForegroundColor Red
        $errorCount++
    }
}

Write-Host "`n🎉 TRANSFORMAÇÃO PWA CONCLUÍDA!" -ForegroundColor Green
Write-Host "📊 Estatísticas:" -ForegroundColor Cyan
Write-Host "   ✅ Processados: $processedCount arquivos" -ForegroundColor Green
Write-Host "   ❌ Erros: $errorCount arquivos" -ForegroundColor Red

Write-Host "`n🔍 CORREÇÕES APLICADAS:" -ForegroundColor Yellow
Write-Host "   📱 Estrutura PWA completa em todas as páginas" -ForegroundColor White
Write-Host "   🔤 Correção de caracteres UTF-8" -ForegroundColor White
Write-Host "   🎨 CSS vivo-app-oficial.css unificado" -ForegroundColor White
Write-Host "   🚫 Redirecionamentos problemáticos removidos" -ForegroundColor White
Write-Host "   📱 Bottom navigation em páginas de app" -ForegroundColor White
Write-Host "   🏷️  Meta tags PWA apropriadas" -ForegroundColor White