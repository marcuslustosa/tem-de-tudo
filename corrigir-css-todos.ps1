# Script para corrigir referências de CSS em todos os arquivos HTML
# Substitui CSSs inexistentes pelo vivo-styles.css

$basePath = "c:/Users/marcu/OneDrive/Desktop/TDD/backend/public"

# Lista de padrões a corrigir e seus substitutos
$corrections = @(
    @{
        pattern = '<link rel="stylesheet" href="/style.css">'
        replace = ''
    },
    @{
        pattern = '<link rel="stylesheet" href="/css/modern-theme.css">'
        replace = ''
    },
    @{
        pattern = '<link rel="stylesheet" href="/css/modern-theme.css" />'
        replace = ''
    },
    @{
        pattern = '<link rel="stylesheet" href="/css/temdetudo-theme.css">'
        replace = ''
    },
    @{
        pattern = '<link rel="stylesheet" href="/css/mobile-native.css">'
        replace = ''
    },
    @{
        pattern = '<link rel="stylesheet" href="/css/app-unified.css">'
        replace = ''
    }
)

# Arquivos que precisam de correção
$files = @(
    "$basePath/app.html",
    "$basePath/estabelecimento/cupons.html",
    "$basePath/estabelecimento/pontos.html",
    "$basePath/estabelecimento/perfil.html",
    "$basePath/estabelecimento/historico.html",
    "$basePath/cliente/historico.html",
    "$basePath/cliente/perfil.html",
    "$basePath/cliente/pontos.html",
    "$basePath/cliente/cupons.html",
    "$basePath/admin/usuarios.html",
    "$basePath/admin/empresas.html"
)

$fixed = 0

foreach ($file in $files) {
    if (Test-Path $file) {
        $content = Get-Content $file -Raw -Encoding UTF8
        $original = $content
        
        foreach ($corr in $corrections) {
            $content = $content -replace [regex]::Escape($corr.pattern), $corr.replace
        }
        
        # Verificar se já tem vivo-styles.css
        if ($content -notmatch 'vivo-styles\.css') {
            # Adicionar após Font Awesome ou no lugar do primeiro link
            $content = $content -replace '(<link rel="stylesheet" href="https://cdnjs\.cloudflare\.com/ajax/libs/font-awesome[^"]*css/all\.min\.css">)', "`$1`n    <link rel=""stylesheet"" href=""css/vivo-styles.css"">"
        }
        
        if ($content -ne $original) {
            Set-Content -Path $file -Value $content -Encoding UTF8
            Write-Host "Corrigido: $file" -ForegroundColor Green
            $fixed++
        }
    } else {
        Write-Host "Arquivo não encontrado: $file" -ForegroundColor Yellow
    }
}

Write-Host "`nTotal de arquivos corrigidos: $fixed" -ForegroundColor Cyan
