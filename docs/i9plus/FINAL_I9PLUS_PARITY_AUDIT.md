# Final i9Plus Parity Audit

## Fonte obrigatoria

- `docs/i9plus/I9PLUS_REFERENCE_SPEC.md`

## Escopo P0

### 1. `backend/public/meus_pontos.html`

- Referencia esperada:
  - home cliente mobile-first com header em gradiente, logo a esquerda, saudacao do cliente, menu de tres pontos, botoes lado a lado para ler QR e mostrar QR, lista de empresas vinculadas, destaques e bottom nav app-like
- Estado atual:
  - shell mobile, lista de empresas, QR do cliente e destaques ja existiam
- Gaps visuais:
  - topo ainda sem acao visual secundaria clara
  - secao de destaque pouco aderente ao tom de app
  - bottom nav com rotulos menos proximos da referencia
- Gaps funcionais:
  - cards de empresa precisavam comunicar melhor busca/novidades e acesso ao perfil
- Status antes:
  - proximo
- Correcao aplicada:
  - hero da home passou a usar a camada `home-client-hero`
  - inclusao do atalho visual de tres pontos para `/meu_perfil.html`
  - refinamento dos rotulos de destaque e da bottom nav para `Buscar` e `Novidades`
- Status depois:
  - igual

### 2. `backend/public/parceiros_tem_de_tudo.html`

- Referencia esperada:
  - busca de novo comercio com input, botao magenta com lupa, aviso de no minimo 4 caracteres, grid 2 colunas com categorias ilustradas, resultados com logo, nome, categoria, estrelas e CTA de cadastro
- Estado atual:
  - busca e grid ja existiam, mas o fluxo estava mais utilitario que app-like
- Gaps visuais:
  - faltava botao de busca destacado
  - faltava helper explicito de 4 caracteres
  - categorias ainda nao tinham bloco visual mais forte
- Gaps funcionais:
  - CTA do card nao contextualizava cadastro quando o cliente ainda nao estava vinculado
- Status antes:
  - parcial
- Correcao aplicada:
  - inclusao de `parceiroBuscaBtn`
  - inclusao do helper `partnersSearchHint`
  - categorias passaram a usar `parceiro-category-media`
  - o CTA agora mostra `Me cadastrar` quando o viewer e cliente e ainda nao esta vinculado
- Status depois:
  - igual

### 3. `backend/public/detalhe_do_parceiro.html`

- Referencia esperada:
  - topo dark com botao fechar, estrela e nota, nome uppercase, secoes separadas para bonus, fidelidade, promocoes, aniversario, contatos e avaliacoes, fidelidade no padrao i9Plus
- Estado atual:
  - tela ja era boa visualmente, mas ainda distante do bloco de fidelidade e da iconografia de contatos da referencia
- Gaps visuais:
  - CTA superior ainda parecia mais pagina web que app
  - fidelidade sem o bloco azul e sem a hierarquia textual da referencia
  - contatos sem leitura iconografica
- Gaps funcionais:
  - faltava exibir o endereco tambem na coluna de contatos
- Status antes:
  - proximo
- Correcao aplicada:
  - `Voltar` virou `Fechar` no hero
  - nome ficou uppercase e o hero dark ficou mais forte
  - pill de nota e estrelas foi reforcado no topo
  - bonus de adesao ficou mais promocional com destaque visual e instrucao presencial explicita
  - fidelidade ganhou `Com X pontos`, bloco azul de progresso, validade em cinza e recompensa em destaque
  - promocoes e aniversario receberam tratamento visual mais proximo de campanha/app
  - contatos ganharam icones para telefone, WhatsApp, endereco, Instagram e Facebook
  - avaliacoes ganharam leitura de app com destaque de nota, lista de usuarios e avatar inicial
  - JS passou a preencher `partner-full-address`, o formato `0 / 15 pontos` e o estado visual de aniversario/avaliacoes
- Status depois:
  - igual

### 4. `backend/public/validar_resgate.html`

- Referencia esperada:
  - leitor de QR operacional com instrucao clara, area de scanner, entrada manual, painel pos-scan e acoes elegiveis
- Estado atual:
  - scanner, entrada manual e paineis ja existiam
- Gaps visuais:
  - bottom nav ainda era composta por itens estaticos
  - navegacao pouco coerente com a area da empresa
- Gaps funcionais:
  - atalhos da tela nao levavam para dashboard, ferramentas e clientes
- Status antes:
  - proximo
- Correcao aplicada:
  - bottom nav foi convertida para links reais
  - a navegacao agora aponta para dashboard, ferramentas, clientes e perfil
  - o item central `Validar` ganhou destaque pill no CSS
- Status depois:
  - igual

### 5. `backend/public/dashboard_parceiro.html`

- Referencia esperada:
  - dashboard simples da empresa com QR em destaque, botao para ler QR do cliente, ferramentas em cards, resumo, clientes e ultimos resgates
- Estado atual:
  - estrutura principal ja existia, mas a secao de ferramentas podia comunicar melhor seu papel
- Gaps visuais:
  - faltava uma faixa explicita de ferramentas
  - CTA secundario da pagina publica estava pouco descritivo
- Gaps funcionais:
  - nenhum gap estrutural severo no fluxo principal
- Status antes:
  - proximo
- Correcao aplicada:
  - secao `Ferramentas da empresa` adicionada acima dos cards
  - CTA secundario passou para `Ver pagina publica`
  - mantidos QR em destaque, scanner, clientes recentes e ultimos resgates
- Status depois:
  - igual

### 6. `backend/public/gest_o_de_ofertas_parceiro.html`

- Referencia esperada:
  - gestao separada por cards de promocao, bonus de adesao, fidelidade, aniversario e lembrete, com preview claro para o cliente
- Estado atual:
  - as secoes ja existiam, mas a navegacao interna e a leitura superior ainda estavam densas
- Gaps visuais:
  - duplicidade de import de `Material Symbols`
  - faltava leitura inicial de ferramentas
- Gaps funcionais:
  - navegacao entre as secoes exigia rolagem longa
- Status antes:
  - parcial
- Correcao aplicada:
  - remocao do link CSS duplicado da fonte de icones
  - inclusao de tool cards para promocao, bonus adesao, fidelidade, aniversario, lembrete e leitor de QR
  - previews existentes foram preservados
- Status depois:
  - igual

### 7. `backend/public/gest_o_de_estabelecimentos.html`

- Referencia esperada:
  - painel admin com cards de status pending, active e suspended, lista clara de empresas e acoes aprovar, rejeitar e suspender
- Estado atual:
  - listagem e acoes existiam, mas o resumo estava concentrado em texto e a navegacao mobile marcava a aba errada
- Gaps visuais:
  - ausencia de cards de status
  - navecacao mobile com destaque incorreto
- Gaps funcionais:
  - resumo nao expunha claramente pendentes, ativas, suspensas e rejeitadas
- Status antes:
  - parcial
- Correcao aplicada:
  - criados cards de status para `pending`, `active`, `suspended` e `rejected`
  - `stitch-app.js` passou a preencher os badges com o summary do endpoint admin
  - bottom nav mobile agora destaca corretamente `Estabelecimentos`
- Status depois:
  - igual
