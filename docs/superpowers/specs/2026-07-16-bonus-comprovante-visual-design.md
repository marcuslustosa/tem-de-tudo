# Spec — Celebração do bônus, comprovante pós-resgate, guia de instalação, limpeza de QR e refino visual

Data: 2026-07-16
Decisões tomadas com o dono do produto (feedback do Gabriel).

## Contexto

O app funciona hoje com o fluxo **cliente lê o QR da empresa** em tudo (vínculo,
bônus, fidelidade, promoções). Esse fluxo foi confirmado e **permanece**. Os
problemas relatados são de feedback visual e de pós-ação, mais a estética geral
("cara de IA").

## Decisões

1. **Fluxo de QR mantido**: cliente lê a empresa, em tudo. Remover código morto
   do fluxo antigo em que a empresa escaneava o cliente.
2. **Celebração do bônus de adesão**: ao se cadastrar/vincular via QR da empresa,
   o cliente DEVE ver a celebração "Você ganhou o bônus!".
3. **Pós-resgate**: ao resgatar o bônus, o cliente **sai da página da empresa**
   e vai para uma **tela de comprovante** em tela cheia (celebração + detalhes,
   pronta para mostrar no balcão), com botão de voltar ao início.
4. **Instalação do app**: manter o botão; melhorar o guia do iPhone com passos
   mais visuais. Android continua com o prompt nativo.
5. **Visual**: refino geral para "cara de app" (tipografia, hierarquia, estados
   de toque, microinterações), sem depender do designer externo.

## Causas-raiz encontradas (item 2)

- Após o cadastro via QR: `criar_conta` → `vincular_empresa` → página do
  parceiro com `linked=1`. Nessa página, o **modal de push notification**
  (motivo `register`, `promptPages` inclui `detalhe_do_parceiro`) abre em cima
  e engole a celebração do bônus.
- A preferência "Não ver mais" (`tdt_bonus_adesao_hide_<empresaId>`) suprime o
  modal para sempre, inclusive num vínculo recém-criado.

## Mudanças

### A. Celebração do bônus no cadastro/vínculo (stitch-app.js)

- Quando a página do parceiro carrega com `linked=1` e o bônus está
  `available`: mostrar a celebração **sempre** (ignorar o hide pref) e
  **adiar o prompt de push** (não consumir o motivo nesta carga; ele aparece
  na próxima página elegível ou após fechar o modal).
- Modal ganha tom de celebração ("Você acabou de ganhar!") com a imagem do
  bônus em destaque.

### B. Tela de comprovante pós-resgate

- Nova página `bonus_resgatado.html` (tela cheia, sem dock): celebração +
  imagem do bônus + nome da empresa + título/descrição + data/hora do resgate,
  linguagem "mostre esta tela no balcão", botão "Voltar ao início".
- Query: `?empresa=<id>&bonus=<id>&tipo=adesao`. A página confirma os dados na
  API (`/cliente/bonus-adesao/disponivel/{empresa}` → status `redeemed`,
  `redeemed_at`), sem confiar só na URL.
- O botão "OK, resgatar" do modal passa a navegar para essa página em caso de
  sucesso (em vez de `window.location.reload()`).

### C. Guia de instalação (iPhone)

- Bottom sheet com passos ilustrados (mock visual da barra do Safari com o
  ícone de Compartilhar em destaque, depois o item "Adicionar à Tela de
  Início"), mantendo os 3 passos numerados.

### D. Limpeza do scanner da empresa

- `validar_resgate.html` + handler `validarResgate`: remover os caminhos
  mortos em que a empresa escaneava o cliente (camera/validação para perfil
  empresa já escondidos por gambiarra; remover de vez). Para a empresa a tela
  vira só "Meu QR da loja". O scanner permanece para o cliente.
- Não mexer nas rotas de backend (mantêm compatibilidade); apenas frontend.

### E. Refino visual (páginas do fluxo principal)

Prioridade: entrar, criar_conta, meus_pontos, detalhe_do_parceiro,
validar_resgate, recompensas, meu_perfil, dashboard_parceiro + tokens globais
em `i9plus-phase8.css`.

- Identidade atual do app (paleta i9: magenta #b01774 + azul #133f8c, token
  `--i9-gradient`; o doc VIPUS de abril está desatualizado) aplicada com
  intenção: gradiente só em momentos-chave (hero, CTA primário, celebração);
  o resto em neutros limpos.
- Tipografia: títulos Plus Jakarta Sans com peso/tracking de app; corpo Be
  Vietnam Pro; escala consistente.
- Componentes: cards com bordas hairline + sombra suave em vez de drop shadows
  genéricos; estados de toque (active:scale); raios consistentes; dock refinado
  com item central de QR em destaque; skeletons no lugar de "Carregando...".
- Sem emoji como ícone; Material Symbols com fill consistente.

## Fora de escopo

- Redesign completo página a página pelo designer (primo) — este refino não
  bloqueia esse trabalho.
- Mudanças de backend/rotas.

## Critérios de aceite

1. Cadastro novo via QR da empresa termina com a celebração do bônus visível.
2. Resgatar o bônus leva à tela de comprovante (não permanece na página da
   empresa) e o comprovante mostra dados reais da API.
3. Empresa não vê mais nenhum resquício de scanner; cliente continua lendo QR.
4. Guia de instalação do iPhone com passos ilustrados.
5. `node --check` limpo e bundle regenerado; páginas principais visivelmente
   mais refinadas em mobile (375px) sem quebrar desktop.
