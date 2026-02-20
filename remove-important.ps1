# Remove !important excessivo do CSS, mantendo apenas em utility classes
$cssPath = "backend/public/css/style-unificado.css"
$content = Get-Content $cssPath -Raw -Encoding UTF8

# Conta quantos !important existem antes
$beforeCount = ([regex]::Matches($content, "!important")).Count
Write-Host "=== LIMPEZA DE !IMPORTANT ===" -ForegroundColor Cyan
Write-Host "Antes: $beforeCount ocorrências de !important" -ForegroundColor Yellow

# Lista de seções onde !important deve ser removido
$sectionsToClean = @(
    "/* === RESET E BASE ===",
    "/* === HEADER UNIFICADO ===",
    "/* === BOTÕES UNIFICADOS ===",
    "/* === CARDS UNIFICADOS ===",
    "/* === MAIN CONTAINER ===",
    "/* === SECTIONS ===",
    "/* === GRIDS ===",
    "/* === FORM INPUTS ===",
    "/* === BOTTOM NAVIGATION ===",
    "/* === BADGES E STATUS ===",
    "/* === MODAIS ===",
    "/* === TABELAS ===",
    "/* === PROMOÇÕES ===",
    "/* === PEDIDOS ===",
    "/* === NOTIFICAÇÕES ===",
    "/* === PÁGINAS ESPECIAIS"
)

# Para cada seção, remove !important (EXCETO em utility classes)
foreach ($section in $sectionsToClean) {
    if ($content -match [regex]::Escape($section)) {
        # Encontra o início da seção
        $sectionStart = $content.IndexOf($section)
        if ($sectionStart -ge 0) {
            # Encontra o fim da seção (próximo comentário de seção ou fim do arquivo)
            $nextSection = $content.IndexOf("/* ===", $sectionStart + $section.Length)
            if ($nextSection -lt 0) { $nextSection = $content.Length }
            
            # Extrai a seção
            $sectionContent = $content.Substring($sectionStart, $nextSection - $sectionStart)
            
            # Remove !important APENAS de propriedades CSS (não de classes utility)
            $cleaned = $sectionContent -replace '\s*!important(?=\s*;)', ''
            
            # Substitui no conteúdo principal
            $content = $content.Replace($sectionContent, $cleaned)
        }
    }
}

# Salva arquivo limpo
$content | Out-File $cssPath -Encoding UTF8 -NoNewline

# Conta quantos !important restam
$afterCount = ([regex]::Matches($content, "!important")).Count
$removed = $beforeCount - $afterCount

Write-Host "Depois: $afterCount ocorrências de !important" -ForegroundColor Green
Write-Host "Removidos: $removed !important" -ForegroundColor Magenta
Write-Host ""
Write-Host "[OK] CSS limpo e salvo em: $cssPath" -ForegroundColor Green
