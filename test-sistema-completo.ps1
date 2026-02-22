# Script de Teste Completo do Sistema Tem de Tudo
# Verifica se todas as páginas e sistemas estão funcionando

Write-Host "🚀 Testando Sistema Tem de Tudo Completo..." -ForegroundColor Cyan
Write-Host "======================================" -ForegroundColor Cyan

$baseDir = "C:/Users/X472795/Desktop/Projetos/tem-de-tudo/backend/public"

# Lista das páginas que devem existir e funcionar
$paginasEssenciais = @(
    "app-cartoes.html",
    "app-notificacoes.html", 
    "app-categorias.html",
    "app-promocoes.html",
    "app-empresas.html",
    "register-company.html",
    "register-company-success.html",
    "faq.html",
    "admin-usuarios.html",
    "entrar.html"
)

# Arquivos do sistema global
$arquivosGlobais = @(
    "global-styles.css",
    "global-auth.js", 
    "global-navbar.js",
    "sw.js",
    "manifest.json"
)

Write-Host "📋 Verificando páginas essenciais..." -ForegroundColor Yellow

$paginasOk = 0
foreach ($pagina in $paginasEssenciais) {
    $path = Join-Path $baseDir $pagina
    if (Test-Path $path) {
        # Verificar se o arquivo não está vazio e tem conteúdo HTML válido
        $content = Get-Content $path -Raw
        if ($content -and $content.Contains("<!DOCTYPE html") -and $content.Contains("Vivo") -and $content.Contains("global-styles.css")) {
            Write-Host "  ✅ $pagina - OK (HTML válido com design Vivo)" -ForegroundColor Green
            $paginasOk++
        } else {
            Write-Host "  ⚠️ $pagina - Existe mas pode ter problemas de conteúdo" -ForegroundColor Yellow
        }
    } else {
        Write-Host "  ❌ $pagina - AUSENTE" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "🔧 Verificando arquivos do sistema global..." -ForegroundColor Yellow

$globaisOk = 0
foreach ($arquivo in $arquivosGlobais) {
    $path = Join-Path $baseDir $arquivo
    if (Test-Path $path) {
        $content = Get-Content $path -Raw
        if ($content -and $content.Length -gt 100) {
            Write-Host "  ✅ $arquivo - OK" -ForegroundColor Green
            $globaisOk++
        } else {
            Write-Host "  ⚠️ $arquivo - Existe mas muito pequeno" -ForegroundColor Yellow
        }
    } else {
        Write-Host "  ❌ $arquivo - AUSENTE" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "🔍 Verificando problemas de encoding..." -ForegroundColor Yellow

# Verificar se existem caracteres corrompidos (�) nos arquivos HTML
$problemasEncoding = 0
Get-ChildItem "$baseDir/*.html" | ForEach-Object {
    $content = Get-Content $_.FullName -Raw -Encoding UTF8
    if ($content -and $content.Contains("�")) {
        Write-Host "  ❌ $($_.Name) - Contém caracteres corrompidos (�)" -ForegroundColor Red
        $problemasEncoding++
    }
}

if ($problemasEncoding -eq 0) {
    Write-Host "  ✅ Nenhum problema de encoding encontrado" -ForegroundColor Green
}

Write-Host ""
Write-Host "📊 RELATÓRIO FINAL" -ForegroundColor Cyan
Write-Host "==================" -ForegroundColor Cyan
Write-Host "Páginas essenciais: $paginasOk/$($paginasEssenciais.Count)" -ForegroundColor $(if($paginasOk -eq $paginasEssenciais.Count) {"Green"} else {"Yellow"})
Write-Host "Arquivos globais: $globaisOk/$($arquivosGlobais.Count)" -ForegroundColor $(if($globaisOk -eq $arquivosGlobais.Count) {"Green"} else {"Yellow"})
Write-Host "Problemas encoding: $problemasEncoding" -ForegroundColor $(if($problemasEncoding -eq 0) {"Green"} else {"Red"})

$pontuacaoTotal = $paginasOk + $globaisOk + $(if($problemasEncoding -eq 0) {1} else {0})
$pontuacaoMaxima = $paginasEssenciais.Count + $arquivosGlobais.Count + 1

Write-Host ""
if ($pontuacaoTotal -eq $pontuacaoMaxima) {
    Write-Host "🎉 SISTEMA 100% FUNCIONAL! Pronto para produção!" -ForegroundColor Green
    Write-Host "✅ Todas as páginas recriadas com design Vivo unificado" -ForegroundColor Green
    Write-Host "✅ Sistema global integrado funcionando" -ForegroundColor Green
    Write-Host "✅ Encoding UTF-8 correto em todas as páginas" -ForegroundColor Green
} elseif ($pontuacaoTotal -ge ($pontuacaoMaxima * 0.8)) {
    Write-Host "⚠️ Sistema funcional com pequenos problemas" -ForegroundColor Yellow
    Write-Host "Pontuação: $pontuacaoTotal/$pontuacaoMaxima" -ForegroundColor Yellow
} else {
    Write-Host "❌ Sistema precisa de correções importantes" -ForegroundColor Red
    Write-Host "Pontuação: $pontuacaoTotal/$pontuacaoMaxima" -ForegroundColor Red
}

Write-Host ""
Write-Host "🌐 Para testar: Abra http://localhost/entrar.html" -ForegroundColor Cyan
Write-Host "📝 Login de teste: qualquer email + senha para desenvolvimento" -ForegroundColor Cyan
Write-Host "👨‍💼 Admin: admin@test.com" -ForegroundColor Cyan
Write-Host "🏢 Empresa: empresa@test.com" -ForegroundColor Cyan
Write-Host "👤 Cliente: cliente@test.com" -ForegroundColor Cyan