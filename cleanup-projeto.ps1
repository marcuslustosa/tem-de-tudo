# Script de Limpeza do Projeto - Remover Arquivos Desnecessários
# Mantém apenas arquivos essenciais para produção

Write-Host "🧹 INICIANDO LIMPEZA DO PROJETO..." -ForegroundColor Green

# Arquivos para remover (documentação temporária)
$arquivosRemover = @(
    # Relatórios temporários
    "STATUS_*.md",
    "RELATORIO_*.md", 
    "CORRECAO*.md",
    "ANALISE_*.md",
    "VERIFICACAO*.md",
    "AUDITORIA*.md",
    "SUCESSO_*.md",
    "IMPLEMENTACAO_*.md",
    "CHECKLIST_*.md",
    "CONFIGURACAO_*.md",
    "RECUPERACAO_*.md",
    
    # Documentação redundante
    "SISTEMA_*.md",
    "SPA_*.md", 
    "BACKEND_*.md",
    "AUTH_*.md",
    "MUDANCAS_*.md",
    "COMMIT_*.md",
    "ENTREGA_*.md",
    "USUARIOS_*.md",
    "CREDENCIAIS_CLIENTE.md",
    "CREDENCIAIS_TESTE.md", 
    "CREDENCIAIS_TODOS_PERFIS.md",
    "ACESSOS_*.md",
    "TESTE_*.md",
    "TODAS_*.md",
    "O_QUE_FALTA.md",
    "PLANO_*.md",
    "RESUMO_*.md",
    "TRANSFORMAR_*.md",
    "NOTIFICACOES_README.md",
    "GUIA_EMAILJS_GRATIS.md",
    "GUIA_TESTES.md",
    "GUIA_USO.md",
    "GUIA_COMPLETO_USO.md",
    "TEMPLATE_PADRAO.txt",
    "DIAGNOSTICO_*.md",
    "DEMONSTRACAO_*.md",
    "SETUP_PRODUCAO.md",
    "DESIGN_SYSTEM_TDT.md",
    "LISTA_*.md",
    "CONFIGURAR_*.md",
    
    # Scripts de desenvolvimento (já usados)
    "*.ps1",
    "*.py", 
    "*.bat",
    "test-*.sh",
    "fix*.sh",
    "cleanup*.sh",
    "entrypoint.sh"
)

$contadorRemovidos = 0

foreach ($padrao in $arquivosRemover) {
    $arquivos = Get-ChildItem -Path . -Name $padrao -ErrorAction SilentlyContinue
    foreach ($arquivo in $arquivos) {
        if (Test-Path $arquivo) {
            Write-Host "❌ Removendo: $arquivo" -ForegroundColor Yellow
            Remove-Item $arquivo -Force
            $contadorRemovidos++
        }
    }
}

Write-Host "`n✅ LIMPEZA CONCLUÍDA!" -ForegroundColor Green
Write-Host "📊 Arquivos removidos: $contadorRemovidos" -ForegroundColor Cyan

Write-Host "`n📋 ARQUIVOS MANTIDOS (essenciais):" -ForegroundColor Blue
Write-Host "✅ README.md - Documentação principal"
Write-Host "✅ GUIA_DEMONSTRACAO_COMPLETO.md - Guia oficial"  
Write-Host "✅ API_REFERENCE.md - Referência da API"
Write-Host "✅ REGRAS_NEGOCIO_COMPLETO.md - Regras de negócio"
Write-Host "✅ CREDENCIAIS_ACESSO.md - Credenciais oficiais"
Write-Host "✅ TODO.md - Lista de tarefas"
Write-Host "✅ deploy.* - Scripts de deploy"
Write-Host "✅ build.sh - Script de build"
Write-Host "✅ Procfile - Configuração Render"
Write-Host "✅ render.yaml - Deploy automático"
Write-Host "✅ Dockerfile - Container"
Write-Host "✅ /backend/ - Código da aplicação"

Write-Host "`n🚀 Projeto otimizado para produção!" -ForegroundColor Green