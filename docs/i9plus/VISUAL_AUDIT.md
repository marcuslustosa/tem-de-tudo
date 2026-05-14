# Auditoria visual

## Escopo desta auditoria

Esta auditoria compara a base visual atual com:

- `docs/i9plus/design-tokens.json`
- `docs/i9plus/ui-screens.json`
- `docs/i9plus/i9plus-theme.css`

O objetivo aqui nao e redesenhar a aplicacao agora, e sim identificar o que ja pode ser aproveitado, o que esta parcialmente alinhado e o que precisara de adaptacao visual real.

## Atualizacao Fase 2 - 2026-05-07

### Telas efetivamente adaptadas nesta fase

- `meus_pontos.html`
  - passou a funcionar como `customer_home`
  - foco em:
    - saudacao
    - `Ler QR Code`
    - `Meu QR Code`
    - empresas vinculadas
    - empresas em destaque
- `detalhe_do_parceiro.html`
  - passou a funcionar como `company_public_page`
  - inclui:
    - topo escuro
    - logo
    - nome
    - categoria
    - avaliacao
    - contatos
    - area reservada para proximas fases
- `vincular_empresa.html`
  - nova tela de aterrissagem para o fluxo de QR publico da empresa
- `validar_resgate.html`
  - reaproveitado para leitura de QR da empresa pelo cliente

### Ganhos visuais reais da Fase 2

- mobile do cliente ficou mais proximo do contrato app-like;
- a home passou a ter QR como acao central;
- a pagina publica da empresa ja adota header escuro e cards claros;
- desktop das novas telas usa largura maior, em vez de manter tudo preso ao shell mobile.

### Limites visuais que continuam abertos

- ainda ha uso relevante de Tailwind inline e estilos herdados do visual anterior;
- o produto ainda nao aplica sistematicamente `docs/i9plus/i9plus-theme.css`;
- a navegacao inferior ainda e localizada por pagina, nao um shell visual unico do produto;
- ainda falta alinhar tipografia e cores com o contrato final em todas as telas.

## Atualizacao Fase 3 - 2026-05-07

### Telas efetivamente adaptadas nesta fase

- `detalhe_do_parceiro.html`
  - recebeu card de bonus de adesao;
  - mostra status visual:
    - disponivel
    - ja utilizado
    - expirado
    - indisponivel
    - exige vinculo
  - CTA passou a orientar apresentacao do QR do cliente, sem resgate pelo frontend.
- `gest_o_de_ofertas_parceiro.html`
  - recebeu painel de configuracao de bonus de adesao;
  - formulario simples;
  - preview do card;
  - lista de bonus cadastrados;
  - CTA para leitura do QR do cliente.
- `validar_resgate.html`
  - recebeu modo visual `bonus-adesao`;
  - painel de consulta do cliente;
  - status do bonus;
  - acao de validacao pela empresa.

### Ganhos visuais reais da Fase 3

- a pagina publica da empresa ja comeca a comunicar o beneficio central do produto em vez de deixar apenas placeholder de fases futuras;
- a empresa passou a ter uma area operacional minima coerente com o fluxo de balcÃ£o;
- o scanner da empresa agora distingue melhor o fluxo de bonus do fluxo legado de validacao.

### Limites visuais que continuam abertos

- o painel de bonus ainda vive dentro da tela de ofertas, nao em um dashboard proprio da empresa;
- o scanner continua sendo uma tela compartilhada e isso pesa na clareza visual;
- a identidade continua mista entre o visual anterior do produto e o contrato novo i9Plus;
- ainda nao houve consolidacao ampla do CSS-base `i9plus-theme.css` dentro do produto real.

## Atualizacao Fase 4 - 2026-05-07

### Telas efetivamente adaptadas nesta fase

- `detalhe_do_parceiro.html`
  - recebeu card proprio de cartao fidelidade;
  - mostra:
    - regra de ganho;
    - recompensa;
    - pontos por visita;
    - meta;
    - validade;
    - progresso individual quando o cliente esta vinculado.
- `gest_o_de_ofertas_parceiro.html`
  - recebeu painel de configuracao do cartao fidelidade;
  - formulario;
  - preview;
  - lista de cartoes;
  - CTA para leitura do QR do cliente.
- `validar_resgate.html`
  - recebeu modo `fidelidade`;
  - painel unificado com:
    - status do bonus de adesao;
    - status do cartao fidelidade;
    - pontos atuais;
    - progresso;
    - historico resumido;
    - botoes `Adicionar ponto` e `Resgatar recompensa`.

### Ganhos visuais reais da Fase 4

- a pagina publica da empresa deixou de tratar fidelidade como placeholder e passou a comunicar uma mecanica concreta do produto;
- a area da empresa ficou mais coerente com uma rotina de balcÃ£o, sem precisar abrir tela paralela;
- o scanner passou a suportar melhor o contexto operacional de leitura do QR do cliente para bonus e fidelidade.

### Limites visuais que continuam abertos

- a tela `validar_resgate.html` continua concentrando mais de um fluxo operacional e ainda merece uma etapa de simplificacao futura;
- o formulario de fidelidade foi encaixado pragmaticamente dentro da tela de ofertas, nao em um dashboard proprio de recursos da empresa;
- o visual continua hibrido entre o design legado do produto e o contrato i9Plus;
- o CSS-base `i9plus-theme.css` ainda nao foi internalizado como camada de estilo do produto real.

## Atualizacao Fase 5 - 2026-05-08

### Telas efetivamente adaptadas nesta fase

- `detalhe_do_parceiro.html`
  - recebeu secao de promocoes instantaneas;
  - mostra:
    - imagem;
    - titulo;
    - descricao;
    - validade;
    - estado por cliente;
    - CTA orientando apresentacao do QR no estabelecimento.
- `gest_o_de_ofertas_parceiro.html`
  - recebeu extensao do formulario de promocoes com:
    - validade;
    - titulo da notificacao;
    - corpo da notificacao;
    - status de envio;
    - limite semanal restante.
- `validar_resgate.html`
  - recebeu bloco visual de promocoes elegiveis no painel da empresa;
  - passou a exibir botoes `Validar promocao`.

### Ganhos visuais reais da Fase 5

- a pagina publica da empresa deixou de tratar promocoes como placeholder e passou a comunicar campanhas reais do produto;
- a area da empresa ganhou um fluxo minimo de operacao de campanha + push sem criar painel paralelo;
- o scanner consolidou melhor o conceito de beneficios validados presencialmente pela empresa.

### Limites visuais que continuam abertos

- `gest_o_de_ofertas_parceiro.html` segue acumulando bonus, fidelidade e promocoes em uma mesma pagina operacional;
- `validar_resgate.html` continua multiproposta e ainda merece separacao visual por modo;
- a linguagem visual continua misturando o design legado do projeto com o contrato novo i9Plus;
- o CSS-base `i9plus-theme.css` ainda nao foi incorporado como camada de estilos produtiva.

## Identidade visual esperada

### Contrato visual alvo

- Mobile com aparencia de app/PWA instalado.
- Desktop com aparencia de site/painel web responsivo.
- Paleta principal:
  - navy escuro
  - azul
  - verde agua/turquesa
  - magenta/rosa
- Cards brancos, cantos grandes, sombras suaves.
- Botoes pill arredondados.
- Topo escuro na pagina da empresa.
- QR Code como acao central.
- Navegacao inferior no mobile.
- Desktop sem parecer um celular gigante.

### Situacao atual

- A base atual ja trabalha com UX mobile-first.
- Ha forte uso de cards arredondados, gradientes, listas empilhadas e navegacao inferior.
- A marca visual atual ainda e "Tem de Tudo", com combinacao de rosa, roxo e ciano, e nao a paleta navy/teal/magenta do contrato i9Plus.
- O visual atual e mais focado em pontos/descontos gerais do que em fidelizacao por empresa via QR como acao central.

Conclusao:

- Direcao visual geral: `parcialmente alinhada`.
- Identidade de marca e hierarquia de telas: `precisam ser adaptadas`.

## Tokens visuais

### Aderencia parcial encontrada

- Ha uso recorrente de:
  - superficies brancas;
  - alto arredondamento;
  - sombras suaves;
  - gradientes;
  - icones e navegaÃ§Ã£o inferior em telas mobile.

### Divergencias em relacao ao contrato

- Cores atuais predominantes:
  - rosa `#E10098`
  - roxo `#7A2C8F`
  - ciano `#00BCD4`
  - fundos claros lavender `#f8f5fe`
- Tipografia atual recorrente:
  - `Plus Jakarta Sans`
  - `Be Vietnam Pro`
- Tipografia alvo definida no contrato:
  - `Inter`, `Montserrat`, Arial, sans-serif
- O contrato pede topo escuro de empresa e gradiente navy/teal/magenta, o que nao esta padronizado nas telas atuais.

Conclusao:

- Tokens estruturais de espacamento/radius/sombra: `reaproveitaveis por aproximacao`.
- Tokens de cor e tipografia: `precisam de adaptacao`.

## Mobile esperado

### Contrato esperado

- parecer app/PWA;
- fluxo simples;
- coluna unica;
- botÃµes grandes;
- cards empilhados;
- navegacao inferior;
- QR Code como acao central.

### Base atual

- `meus_pontos.html`, `parceiros_tem_de_tudo.html`, `detalhe_do_parceiro.html`, `recompensas.html`, `meu_perfil.html` e `validar_resgate.html` ja usam shell mobile com bom acabamento.
- A navegacao inferior ja existe em varias telas.
- O scanner em `validar_resgate.html` e um bom ponto de partida para fluxos de leitura de QR.
- As telas atuais, porem, ainda nao constroem a home do cliente com duas acoes centrais claras:
  - "Ler QR Code"
  - "Meu QR Code"

Conclusao:

- Comportamento mobile base: `reaproveitar`.
- Jornada mobile especifica do novo produto: `adaptar`.

## Desktop esperado

### Contrato esperado

- parecer site/painel web;
- usar melhor a largura da tela;
- permitir sidebar ou header;
- permitir tabelas em admin/relatorios;
- nao parecer apenas um celular ampliado.

### Base atual

- `dashboard_admin_master.html`, `gest_o_de_estabelecimentos.html`, `gest_o_de_clientes_master.html`, `relat_rios_gerais_master.html` e `dashboard_parceiro.html` ja caminham nessa direcao.
- Ha uso de sidebar, cards de estatistica e tabelas/listas administrativas em largura maior.
- A parte administrativa esta visualmente mais proxima do contrato desktop do que a parte cliente/publica.

Conclusao:

- Desktop admin/empresa: `reaproveitar/adaptar`.
- Desktop cliente/publico: `adaptar` para nao ficar somente em shell mobile centralizado.

## Componentes obrigatorios

### Componentes atuais reaproveitaveis

- Bottom navigation mobile.
- Cards arredondados com sombra.
- Listas de empresas/parceiros com logo e metadados.
- Botoes arredondados e chamadas CTA.
- Blocos de dashboard de admin e empresa.
- Scanner via camera em `validar_resgate.html`.
- Estrutura de modais/cards promocionais em paginas de oferta/recompensa.
- Formularios de auth, perfil e configuracoes.

### Componentes que existem apenas parcialmente

- Busca com destaque visual:
  - existe busca/filtro;
  - nao esta padronizada no formato do contrato com lupa magenta e grid de categorias com imagem.
- Home cliente:
  - existe dashboard/pagina de pontos;
  - nao existe a home contratada com foco principal em QR e empresas vinculadas.
- Pagina publica da empresa:
  - existe tela de detalhe;
  - nao segue ainda o header escuro e a hierarquia i9Plus.
- Dashboard da empresa:
  - existe;
  - precisa enfatizar QR da empresa, leitura de QR do cliente e campanhas.
- Aprovacao admin de empresa:
  - existe gestao de estabelecimentos;
  - precisa ser validado/ajustado para fila clara de pendentes, aprovadas e suspensas.

### Componentes visuais que precisam ser criados

- Splash/install/QR institucional.
- Grid de categorias com imagem em 2 colunas no mobile.
- Header escuro padronizado da pagina da empresa.
- Home do cliente com acao central de QR.
- Card/modal de bonus aniversario.
- Tela/estado visual de empresa pendente para admin.
- Contrato visual consistente entre cliente, empresa e admin sob a identidade i9Plus.

## Telas atuais existentes

### Mapeamento principal da base atual

| Tela contratada | Base atual mais proxima | Aderencia | Observacao |
| --- | --- | --- | --- |
| `splash_install_qrcode` | `index.html` | baixa | landing existe, mas nao e QR-first nem install-first |
| `customer_home` | `meus_pontos.html` | parcial | tela centrada em pontos; falta home de fidelizacao com QR central |
| `customer_search_categories` | `parceiros_tem_de_tudo.html` | parcial | busca e listagem existem; grid visual de categorias com imagem nao |
| `company_public_page` | `detalhe_do_parceiro.html` | boa/parcial | pagina publica ja exibe bonus aniversario e estados de elegibilidade; ainda falta refinamento visual total do contrato i9Plus |
| `promotion_notification` | `oferta_especial.html` / cards de recompensas | parcial | existe base promocional; modal/notificacao padrao ainda nao |
| `company_reviews` | bloco de avaliacoes em `detalhe_do_parceiro.html` | parcial | falta tela/visao de distribuicao e resumo de notas |
| `birthday_bonus` | bloco em `detalhe_do_parceiro.html` + configuracao em `gest_o_de_ofertas_parceiro.html` | boa/parcial | a fase 6 entregou o card publico, a configuracao da empresa e a leitura no scanner; ainda falta lapidacao visual final |
| `company_dashboard` | `dashboard_parceiro.html` | boa/parcial | painel existe e e reaproveitavel, com ajustes de foco |
| `company_scan_customer_qr` | `validar_resgate.html` | boa | scanner agora consulta bonus de adesao, bonus aniversario, fidelidade e promocoes no mesmo shell canonico |
| `admin_company_approval` | `gest_o_de_estabelecimentos.html` | parcial | base administrativa existe; fluxo de aprovacao pendente nao esta claro na UI atual |

## Telas que precisam ser adaptadas

- `index.html`
- `meus_pontos.html`
- `parceiros_tem_de_tudo.html`
- `detalhe_do_parceiro.html`
- `dashboard_parceiro.html`
- `gest_o_de_estabelecimentos.html`
- `recompensas.html`
- `oferta_especial.html`
- `meu_perfil.html`

Motivo principal:

- todas essas telas tem estrutura reaproveitavel, mas nao entregam o contrato i9Plus completo em identidade, foco de jornada ou hierarquia de informacao.

## Telas que podem ser reaproveitadas

- `entrar.html`
- `forgot_password.html`
- `reset_password.html`
- `criar_conta.html`
- `dashboard_admin_master.html`
- `clientes_fidelizados_loja.html`
- `validar_resgate.html`
- `configuracoes_admin.html`
- `banners_e_categorias_master.html`
- `relat_rios_gerais_master.html`

Observacao:

- "reaproveitar" aqui nao significa manter sem ajustes.
- Significa usar a base existente, preservando fluxo, shell ou estrutura, em vez de redesenhar tudo do zero.

## Telas que precisam ser refeitas ou praticamente recriadas

- `splash_install_qrcode`
- `customer_home`
Motivo:

- `birthday_bonus` deixou de ser item "nao existente" e passou a viver dentro das telas canonicas da empresa, do cliente e do scanner;
- o que segue faltando aqui e refinamento visual final, nao uma recriacao estrutural do fluxo.

## Diferencas entre mobile e desktop

### O que a base atual faz bem

- Admin e parceiro ja usam melhor largura no desktop.
- Cliente/publico ja parte de uma logica mobile-first.

### O que ainda falta

- O contrato novo exige duas leituras bem separadas:
  - mobile do cliente como app;
  - desktop de empresa/admin como painel.
- Parte do frontend cliente ainda e muito orientada a pagina unica de pontos, e nao a um app de fidelizacao orientado por empresas e QR.
- O manifest atual aponta para rotas PWA que nao existem, o que enfraquece a sensacao de app instalado.

## Riscos de fidelidade visual

- Maior risco visual: tentar encaixar a nova identidade so trocando cores em telas atuais que possuem hierarquia de informacao diferente.
- Risco alto de inconsistencia de marca:
  - logo, textos e CTA atuais ainda comunicam "Tem de Tudo";
  - o contrato novo pede um produto de fidelizacao inspirado no i9Plus.
- Risco de mobile ficar confuso se `meus_pontos.html` for apenas renomeada como home sem reorganizar a jornada.
- Risco de desktop cliente continuar com cara de celular ampliado se nao houver layout proprio para contextos administrativos e de analise.
- Risco de instalacao PWA parecer quebrada por causa do manifest desatualizado.

## Pendencias

- Definir quais paginas publicas existentes serao adaptadas in-place e quais merecem nova composicao visual.
- Validar, no inicio da implementacao visual, se o time quer manter a tipografia atual ou migrar para os tokens de contrato.
- Decidir se o shell do cliente continuara em HTML + `stitch-app.js` ou se a camada visual sera consolidada em assets Vite, sem criar segunda aplicacao.
- Produzir inventario de componentes reais dentro de `stitch-app.js` para separar layout reaproveitavel de conteudo que precisara ser reescrito.

## Atualizacao Fase 8 - 2026-05-14

### Telas refinadas nesta fase

- `criar_conta.html`
- `entrar.html`
- `meus_pontos.html`
- `detalhe_do_parceiro.html`
- `validar_resgate.html`
- `gest_o_de_ofertas_parceiro.html`
- `dashboard_parceiro.html`
- `clientes_fidelizados_loja.html`
- `dashboard_admin_master.html`
- `relat_rios_gerais_master.html`
- `gest_o_de_estabelecimentos.html`

### Estrategia visual aplicada

- criacao de uma camada compartilhada `backend/public/css/i9plus-phase8.css`;
- uso incremental dos tokens navy/blue/teal/magenta e de cards brancos com pill buttons;
- preservacao do HTML existente sempre que possivel;
- reforco do contraste app-like no mobile e painel-like no desktop;
- sem reescrever `stitch-app.js` nem redesenhar o produto do zero.

### Ganhos visuais reais da Fase 8

- telas de autenticacao passaram a ter shell mais proximo de PWA/app instalado;
- dashboard da empresa deixou de ficar preso ao shell estreito no desktop;
- scanner operacional ganhou leitura melhor em desktop com bloco principal + coluna lateral;
- gestao de ofertas ficou mais coerente como tela operacional e nao apenas lista mobile;
- dashboards admin e listas admin/empresa ganharam melhor uso de largura, hierarquia e consistencia;
- labels visuais incoerentes com o fluxo real foram corrigidos em navegacao e chips operacionais;
- a acao de QR/validacao da empresa ficou visualmente mais central no mobile quando aplicavel.

### O que foi mantido de proposito

- nenhuma feature nova de negocio;
- nenhum redesenho estrutural completo de cliente, empresa ou admin;
- nenhuma mudanca em service worker, manifest ou fluxos PWA alem do refinamento visual;
- nenhuma reativacao de `QRCodeController`.

### Limites visuais que ainda existem

- a base continua com muito Tailwind inline e HTML legado;
- `stitch-app.js` ainda define boa parte do comportamento e do HTML dinamico;
- nem todas as telas publicas do produto receberam a nova camada visual nesta fase;
- a identidade continua hibrida entre a marca existente "Tem de Tudo" e o contrato visual inspirado no i9Plus;
- `parceiros_tem_de_tudo.html`, `index.html`, `recompensas.html` e outras telas secundarias ainda podem receber lapidacao futura.
- a experiencia de instalacao PWA ainda perde acabamento porque o manifest referencia assets ausentes neste checkout atual:
  - `/img/screenshot-mobile.png`
  - `/img/icon-profile.png`

### Fechamento visual final

- mobile: mais proximo do contrato i9Plus, com cards claros, gradiente azul/verde/magenta e botoes pill;
- desktop: mais proximo de painel/site responsivo, sem depender do shell de celular ampliado;
- status da Fase 8 visual: concluida no escopo incremental definido, com backlog residual apenas de acabamento fino e consolidacao de estilo global.
