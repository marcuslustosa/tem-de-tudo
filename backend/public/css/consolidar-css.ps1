#!/bin/bash
# Script PowerShell para consolidar TODOS os arquivos CSS em um único arquivo

# Fazendo backup do arquivo atual
Copy-Item vivo-styles.css "vivo-styles-backup-final-$(Get-Date -Format 'yyyyMMdd-HHmmss').css"

Write-Host "=== CONSOLIDAÇÃO COMPLETA DE CSS ==="
Write-Host "Combinando todos os arquivos CSS em vivo-styles.css"

# Criando cabeçalho do arquivo unificado
@"
/*
 * VIVO DESIGN SYSTEM - TEM DE TUDO
 * ARQUIVO CSS UNIFICADO COMPLETO
 * Consolidado em: $(Get-Date -Format 'dd/MM/yyyy HH:mm:ss')
 * 
 * Este arquivo combina:
 * - Design System base
 * - Global styles 
 * - Classes adicionais
 * - Classes específicas de componentes
 */

"@ | Out-File -FilePath "vivo-styles-mega-unified.css" -Encoding UTF8

# Arquivos para consolidar em ordem de prioridade
$arquivos = @(
    "..\global-styles.css",
    "vivo-global.css", 
    "vivo-styles-completo-final.css",
    "vivo-adicional.css",
    "vivo-classes-faltantes.css"
)

# Processando cada arquivo
foreach ($arquivo in $arquivos) {
    if (Test-Path $arquivo) {
        Write-Host "Adicionando: $arquivo"
        
        # Adicionando separador
        "`n`n/* =========================================" | Add-Content "vivo-styles-mega-unified.css" -Encoding UTF8
        " * CONTEÚDO DE: $arquivo" | Add-Content "vivo-styles-mega-unified.css" -Encoding UTF8  
        " * ========================================= */" | Add-Content "vivo-styles-mega-unified.css" -Encoding UTF8
        "`n" | Add-Content "vivo-styles-mega-unified.css" -Encoding UTF8
        
        # Adicionando conteúdo do arquivo
        Get-Content $arquivo -Encoding UTF8 | Add-Content "vivo-styles-mega-unified.css" -Encoding UTF8
    } else {
        Write-Host "AVISO: Arquivo não encontrado: $arquivo" -ForegroundColor Yellow
    }
}

Write-Host "`n=== FINALIZANDO CONSOLIDAÇÃO ==="

# Estatísticas do arquivo final
$finalFile = "vivo-styles-mega-unified.css"
if (Test-Path $finalFile) {
    $size = [math]::round((Get-Item $finalFile).Length/1KB,2)
    $lines = (Get-Content $finalFile | Measure-Object -Line).Lines
    
    Write-Host "Arquivo consolidado criado: $finalFile"
    Write-Host "Tamanho: $size KB"
    Write-Host "Linhas: $lines"
    
    # Substituindo arquivo principal
    Remove-Item "vivo-styles.css" -Force
    Move-Item $finalFile "vivo-styles.css"
    
    Write-Host "✅ CSS COMPLETAMENTE UNIFICADO!" -ForegroundColor Green
} else {
    Write-Host "❌ ERRO: Falha na consolidação" -ForegroundColor Red
}