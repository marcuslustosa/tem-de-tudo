# Inventário do frontend (Stitch) — estado atual (commit b9bdf71a)

Regra: Stitch é apenas camada visual; todo dado vem de backend/seeds via `stitch-app.js`.

Legenda de status:  
- **funcional**: handler existe, chama endpoints reais, sem mock.  
- **parcial**: handler existe mas há endpoints faltantes/sem dados suficientes ou retorno só com aviso.  
- **visual**: sem handler/dados; apenas casca.

| Arquivo (backend/public) | Perfil | Objetivo | data-page | Handler em `stitch-app.js` | Mock? | Status atual |
| --- | --- | --- | --- | --- | --- | --- |
| index.html | Público | Landing / entrada | acessar_conta | acessar_conta (login) | Não | funcional (login) |
| entrar.html | Público | Login | acessar_conta | acessar_conta | Não | funcional |
| acessar_conta.html | Público | Login (alias) | acessar_conta | acessar_conta | Não | funcional |
| escolher-tipo.html | Público | Selecionar tipo | escolher-tipo | (usa casca, sem handler) | Não | parcial (linka para entrar/parceiros) |
| forgot_password.html | Público | Esqueci senha | forgot_password | forgot_password | Não | funcional |
| reset_password.html | Público | Redefinir senha | reset_password | reset_password | Não | funcional |
| home_tem_de_tudo.html | Público | Landing estática Stitch | home_tem_de_tudo | handler vazio | Não | visual (não usada) |
| oferta_especial.html | Público | Promoção destaque (landing) | oferta_especial | cliente.detalheParceiro | Dados reais | funcional |
| meus_pontos.html | Cliente | Dashboard pontos | meus_pontos | cliente.dashboard | Dados reais | funcional |
| parceiros_tem_de_tudo.html | Cliente | Lista de parceiros | parceiros_tem_de_tudo | cliente.parceiros | Dados reais | funcional |
| detalhe_do_parceiro.html | Cliente | Detalhe de parceiro/promos | detalhe_do_parceiro | cliente.detalheParceiro | Dados reais | funcional |
| recompensas.html | Cliente | Cupons/recompensas | recompensas | cliente.recompensas | Dados reais | funcional |
| hist_rico_de_uso.html | Cliente | Histórico de pontos | hist_rico_de_uso | cliente.historico | Dados reais | funcional |
| validar_resgate.html | Cliente | Usar cupom | validar_resgate | cliente.validarResgate | Dados reais | funcional |
| meu_perfil.html | Cliente/Empresa/Admin | Perfil + alterar senha | meu_perfil | cliente.perfil | Dados reais | funcional |
| parceiros_tem_de_tudo.html | Cliente | Parceiros | parceiros_tem_de_tudo | cliente.parceiros | Dados reais | funcional |
| dashboard_parceiro.html | Estabelecimento | Dashboard | dashboard_parceiro | empresa.dashboard | Dados reais | funcional |
| clientes_fidelizados_loja.html | Estabelecimento | Clientes | clientes_fidelizados_loja | empresa.clientes | Dados reais | funcional |
| gest_o_de_ofertas_parceiro.html | Estabelecimento | Promoções | gest_o_de_ofertas_parceiro | empresa.promocoes | Dados reais | funcional |
| minhas_campanhas_loja.html | Estabelecimento | Campanhas (mesmo handler promoções) | minhas_campanhas_loja | empresa.promocoes | Dados reais | funcional |
| dashboard_admin_master.html | Admin | Dashboard | dashboard_admin_master | admin.dashboard | Dados reais (dep. perms) | funcional* |
| gest_o_de_estabelecimentos.html | Admin | Gestão de empresas | gest_o_de_estabelecimentos | admin.empresas | Dados reais | funcional* |
| gest_o_de_clientes_master.html | Admin | Gestão de clientes | gest_o_de_clientes_master | admin.clientes | Dados reais | funcional* |
| gest_o_de_usu_rios_master.html | Admin | Gestão de usuários | gest_o_de_usu_rios_master | admin.usuarios | Dados reais | funcional* |
| relat_rios_gerais_master.html | Admin | Relatórios | relat_rios_gerais_master | admin.relatorios | Dados reais | funcional* |
| banners_e_categorias_master.html | Admin | Conteúdo/banners | banners_e_categorias_master | handler placeholder (aviso) | Sem endpoint | parcial |
| oferta_especial.html | Público/Cliente | Oferta | oferta_especial | cliente.detalheParceiro | Dados reais | funcional |
| index.html (landing) | Público | Entrada | acessar_conta | acessar_conta | Não | funcional |

\* Funcional condicionado a permissões/seed de admin (manage_system, manage_users, etc.).

Observações rápidas
- Todos os HTML estão em UTF-8 sem BOM e só carregam Tailwind CDN + /js/stitch-app.js.
- Não há mais cards/textos mockados persistidos nos HTML; renderização é 100% via stitch-app.js.
- Páginas “visuais” ou “parciais” são apenas casca sem handler ou com aviso de falta de endpoint (ex.: banners_e_categorias_master).

Próximos passos (lotes seguintes)
- Ligar criação de conta (cadastro) — hoje não há página pública dedicada; precisa fluxo real.
- Revisar cada handler para loading/empty/error e conectar onde faltar endpoint.
