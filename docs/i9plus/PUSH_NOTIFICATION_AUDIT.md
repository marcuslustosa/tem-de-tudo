# Push Notification Audit — Tem de Tudo i9Plus

## Escopo auditado

### Frontend / PWA
- `backend/public/manifest.json`
- `backend/public/sw-push.js`
- `backend/public/js/stitch-app.js`
- `backend/public/dist/stitch-app.min.js`
- `backend/public/meus_pontos.html`
- `backend/public/meu_perfil.html`
- `backend/public/detalhe_do_parceiro.html`
- `backend/public/dashboard_parceiro.html`
- `backend/public/gest_o_de_ofertas_parceiro.html`

### Backend
- `backend/routes/api.php`
- `backend/app/Models/PushSubscription.php`
- `backend/app/Models/NotificacaoPush.php`
- `backend/app/Services/WebPushDeliveryService.php`
- `backend/app/Services/PromocaoInstantaneaService.php`
- `backend/app/Services/BonusAniversarioService.php`
- `backend/app/Services/LembreteRetornoService.php`
- `backend/app/Http/Controllers/PushSubscriptionController.php`
- `backend/app/Http/Controllers/PromocaoController.php`
- `backend/app/Http/Controllers/BonusAniversarioController.php`
- `backend/app/Http/Controllers/LembreteRetornoController.php`
- migrations relacionadas a `push_subscriptions` e `notificacoes_push`

## O que já existia

### PWA / service worker
- `manifest.json` já existia.
- `sw-push.js` já existia.
- o frontend já tinha registro de service worker e fluxo parcial de push.

### Backend
- a dependência `minishlink/web-push` já estava no projeto.
- a tabela/model `push_subscriptions` já existia.
- a tabela/model `notificacoes_push` já existia para histórico operacional.
- já existiam endpoints de:
  - chave pública VAPID
  - subscribe/unsubscribe
  - teste
  - envio de promoção
  - envio de bônus aniversário
  - envio de lembrete de retorno

## O que estava incompleto

### Frontend
- não havia card explícito, confiável e orientado por produto para o cliente ativar notificações.
- a permissão podia ser disparada a partir de ícones genéricos da UI.
- não havia UX clara para:
  - navegador sem suporte
  - permissão negada
  - iPhone fora da Tela de Início
  - VAPID ausente no servidor
- o fluxo não estava focado em “empresas vinculadas”, que é a regra correta do produto.

### Backend
- `PushSubscription` ainda não tratava formalmente:
  - `public_key`
  - `auth_token`
  - `content_encoding`
  - `device_type`
  - `last_seen_at`
  - `revoked_at`
- `WebPushDeliveryService` não consolidava métricas operacionais ricas.
- parte da lógica de envio ainda estava duplicada nos serviços de negócio.
- o comportamento com VAPID ausente ainda precisava fechar no padrão `config_missing`.
- faltava revogação automática de subscriptions inválidas.

## O que foi corrigido

### Frontend / PWA
- `sw-push.js` agora:
  - usa `self.registration.showNotification`
  - trata `notificationclick`
  - abre ou foca a URL correta
  - usa ícones reais do projeto
- `meus_pontos.html` ganhou card explícito para ativar notificações.
- `meu_perfil.html` ganhou card explícito para ativar notificações.
- o request de permissão passou a acontecer apenas por clique do usuário.
- `stitch-app.js` passou a:
  - buscar `VAPID_PUBLIC_KEY` no backend
  - registrar `/sw-push.js`
  - criar `PushSubscription`
  - salvar/remover subscription autenticada
  - orientar corretamente iPhone/Safari para Tela de Início
  - degradar sem quebrar a UI quando o navegador não suporta Push API
  - mostrar estados reais:
    - ativado
    - bloqueado
    - sem suporte
    - iPhone fora da Tela de Início
    - configuração pendente

### Backend
- `PushSubscription` foi estendido para operação real por dispositivo.
- nova migration:
  - `2026_05_20_000001_extend_push_subscriptions_table.php`
- `PushSubscriptionController` agora:
  - expõe `configured` + `vapidPublicKey`
  - salva subscription real do dispositivo
  - revoga o endpoint atual
  - responde `config_missing` ou `no_subscription` sem `500`
- `WebPushDeliveryService` agora:
  - valida VAPID sem derrubar a aplicação
  - retorna métricas reais
  - revoga subscriptions inválidas/expiradas
  - atualiza `last_seen_at` quando há sucesso
- `PromocaoInstantaneaService`, `BonusAniversarioService` e `LembreteRetornoService` agora:
  - enviam somente para clientes vinculados
  - respeitam elegibilidade real
  - não fingem envio quando VAPID está ausente
  - registram resultado em `notificacoes_push`

## Endpoints confirmados

### Cliente autenticado
- `GET /api/push/public-key`
- `POST /api/push/subscribe`
- `DELETE /api/push/unsubscribe`
- `POST /api/push/test`

### Empresa autenticada
- `POST /api/empresa/promocoes/{id}/enviar`
- `POST /api/empresa/bonus-aniversario/{id}/enviar-elegiveis`
- `POST /api/empresa/lembrete-retorno/enviar-elegiveis`

## Tabelas / models relevantes

### `push_subscriptions`
- model: `App\Models\PushSubscription`
- campos relevantes no fluxo:
  - `user_id`
  - `endpoint`
  - `public_key`
  - `auth_token`
  - `content_encoding`
  - `user_agent`
  - `device_type`
  - `last_seen_at`
  - `revoked_at`

### `notificacoes_push`
- model: `App\Models\NotificacaoPush`
- registra:
  - usuário
  - empresa
  - tipo (`promocao`, `aniversario`, `lembrete`)
  - status
  - erro
  - data de envio

## Proteções de vínculo e elegibilidade

- promoção:
  - só clientes vinculados entram como elegíveis
  - cliente não vinculado não entra no envio
  - cliente sem subscription entra em `ignorados_sem_subscription`
- bônus aniversário:
  - exige vínculo + elegibilidade de aniversário
  - cliente fora da janela não recebe
- lembrete:
  - exige vínculo + inatividade elegível
  - respeita anti-reenvio do ciclo

## Métricas disponíveis para o frontend

- `total_elegiveis`
- `total_com_subscription`
- `enviados`
- `falhas`
- `ignorados_sem_subscription`
- `ignorados_sem_vinculo`
- `status`
- `config_missing`

## VAPID

### Variáveis obrigatórias
- `VAPID_PUBLIC_KEY`
- `VAPID_PRIVATE_KEY`
- `VAPID_SUBJECT`

### Comportamento sem VAPID
- o frontend informa configuração pendente
- o backend retorna `config_missing`
- nenhum push real é marcado como entregue sem envio real

### Configuração local segura
- usar arquivo não versionado (`backend/.env`)
- não gravar segredo em docs versionadas
- não usar `backend/.env.local` para segredo, porque ele é rastreado no repositório

### Railway Variables
Preencher no serviço web:
- `VAPID_PUBLIC_KEY`
- `VAPID_PRIVATE_KEY`
- `VAPID_SUBJECT`

## Limitações conhecidas

- Android / Chrome:
  - funciona com HTTPS + permissão + service worker
- iPhone / Safari:
  - exige Safari
  - exige Adicionar à Tela de Início
  - exige abrir pelo ícone instalado
  - exige iOS / iPadOS 16.4+
- se o usuário bloquear a permissão, precisa reativar nas configurações do navegador
- sem subscription ativa não existe entrega real
