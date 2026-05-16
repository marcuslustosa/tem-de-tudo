# Visual Consistency Audit

## Fonte de verdade

- `docs/i9plus/I9PLUS_REFERENCE_SPEC.md`
- `docs/i9plus/FINAL_I9PLUS_PARITY_AUDIT.md`
- `backend/public/css/i9plus-phase8.css`

## Diagnóstico consolidado

- A `index.html` estava mais “landing comercial” com tipografia `Inter`, classes `i9-home-*` próprias e sem o mesmo shell visual das telas internas.
- `entrar.html` e `criar_conta.html` tinham boa base, mas ainda pareciam páginas isoladas, com menos cara de app e pouca continuidade visual com a home e com o pós-login.
- O app interno já tinha uma base i9Plus-like boa, mas ainda misturava:
  - fontes diferentes por tela
  - topbars com estilos parecidos, porém não unificados
  - labels em ASCII sem acento
  - CTAs e cards com variações visuais desnecessárias

## Design system adotado

- Gradiente principal:
  - azul `#133F8C`
  - teal `#00AFA8`
  - magenta `#B01774`
- Tipografia:
  - títulos: `Plus Jakarta Sans`
  - corpo: `Be Vietnam Pro`
- Componentes centralizados em `i9plus-phase8.css`:
  - `.app-shell`
  - `.app-hero`
  - `.app-header-gradient`
  - `.app-card`
  - `.app-pill`
  - `.app-primary-button`
  - `.app-secondary-button`
  - `.app-bottom-nav`
  - `.app-section-title`
  - `.app-company-card`
  - `.app-benefit-card`
  - `.app-campaign-card`
  - `.app-dashboard-card`
  - `.app-admin-card`

## Tabela de consistência

| Tela | Problema de identidade | Ação necessária | Status antes | Status depois |
| --- | --- | --- | --- | --- |
| `index.html` | hero, tipografia e CTAs destoavam do app interno | unificar hero, cards e botões ao design system | parcial | igual |
| `entrar.html` | visual bom, mas isolado do restante do produto | transformar topo em hero do mesmo app e alinhar CTA | próximo | igual |
| `criar_conta.html` | copy e shell ainda genéricos | alinhar hero, card e fluxo cliente/empresa | parcial | igual |
| `meus_pontos.html` | base boa, mas tipografia e microcopy ainda divergiam | preservar layout e alinhar fonte/copy | igual | igual |
| `parceiros_tem_de_tudo.html` | já aderente, mas precisava herdar o mesmo design system | manter estrutura e padronizar asset version/CSS | igual | igual |
| `detalhe_do_parceiro.html` | quase pronto, com labels residuais sem acento | preservar estrutura e limpar copy residual | igual | igual |
| `meu_perfil.html` | hero bom, mas com labels quebrados | manter estrutura e corrigir textos/ações | próximo | igual |
| `dashboard_parceiro.html` | painel bom, com alguns rótulos menos refinados | manter estrutura e padronizar linguagem/CTA | próximo | igual |
| `validar_resgate.html` | shell bom, com textos operacionais sem acento | manter estrutura e limpar copy visível | igual | igual |
| `gest_o_de_ofertas_parceiro.html` | tela funcional, mas precisava herdar o mesmo sistema visual | preservar estrutura e padronizar topo/cards | igual | igual |
| `clientes_fidelizados_loja.html` | painel alinhado, com labels legadas | preservar estrutura e corrigir navegação/copy | próximo | igual |
| `dashboard_admin_master.html` | painel bom, mas com rótulos e CTA residuais | manter estrutura e ajustar linguagem | próximo | igual |
| `gest_o_de_estabelecimentos.html` | painel bom, com textos legados/ASCII | manter estrutura e corrigir labels | próximo | igual |
| `relat_rios_gerais_master.html` | era o admin com mais ruído de acentuação | manter painel e normalizar visual/copy | parcial | igual |

## Resultado

- A home pública, login e cadastro passaram a parecer a entrada do mesmo app que o usuário encontra depois do login.
- Cliente, empresa e admin mantiveram o modelo i9Plus-like já bom, mas com visual, fontes, botões, cards e copy mais coerentes entre si.
- A percepção final deixa de ser “landing antiga + app novo” e passa a ser “um produto único com entrada pública e jornadas internas consistentes”.
