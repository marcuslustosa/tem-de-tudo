# Push Notification Test Guide — Tem de Tudo

## Variáveis obrigatórias no ambiente

- `VAPID_PUBLIC_KEY`
- `VAPID_PRIVATE_KEY`
- `VAPID_SUBJECT`

Sem essas variáveis:
- o card do cliente mostrará configuração pendente
- o backend responderá `config_missing`
- nenhum push real será entregue

## Geração segura de VAPID

Não gravar chave real no código nem em documentação versionada.

### Tentativa principal com a dependência já instalada
No diretório `backend`, executar localmente:

```bash
php -r "require 'vendor/autoload.php'; print_r(\Minishlink\WebPush\VAPID::createVapidKeys());"
```

Se o ambiente local não conseguir gerar a chave EC via OpenSSL, usar um gerador compatível fora do código-fonte e preencher apenas o ambiente local e as variáveis da Railway.

### Onde colocar localmente
- preencher em `backend/.env`
- não usar `backend/.env.local` para segredo
- não commitar `.env`

## Como o cliente ativa notificações

### Android / Chrome
1. Abra `https://tem-de-tudo.up.railway.app`
2. Faça login com `joao@demo.local / password`
3. Vá para:
   - `meus_pontos.html`
   - ou `meu_perfil.html`
4. No card **Receba promoções e benefícios**, clique em **Ativar notificações**
5. Aceite a permissão do navegador
6. Confirme se o card muda para:
   - `Notificações ativadas neste dispositivo`

### iPhone / Safari
1. Abra `https://tem-de-tudo.up.railway.app` no Safari
2. Toque em **Compartilhar**
3. Toque em **Adicionar à Tela de Início**
4. Abra o Tem de Tudo pelo ícone da Tela de Início
5. Faça login
6. Vá ao card **Receba promoções e benefícios**
7. Clique em **Ativar notificações**
8. Aceite a permissão

### iPhone / Safari com cliente de teste dedicado
1. Abra `https://tem-de-tudo.up.railway.app` no Safari
2. Toque em **Compartilhar**
3. Toque em **Adicionar à Tela de Início**
4. Abra o app pelo ícone instalado
5. Faça login com:
   - `cliente.push@demo.local / password`
6. Vá para:
   - `meus_pontos.html`
   - ou `meu_perfil.html`
7. Clique em **Ativar notificações**
8. Aceite a permissão do iPhone
9. Avise o admin que o dispositivo já ativou o push
10. O admin deve entrar com:
   - `admin@demo.local / password`
11. Abrir `gest_o_de_clientes_master.html`
12. Confirmar o cliente `cliente.push@demo.local`
13. Clicar em **Enviar push teste**
14. O iPhone deve receber a notificação com abertura para `/meus_pontos.html`

### Desktop / Chrome ou Edge
1. Abra `https://tem-de-tudo.up.railway.app`
2. Faça login como cliente
3. Vá ao card de notificações
4. Clique em **Ativar notificações**
5. Aceite a permissão do navegador

## Como testar promoção instantânea

1. No dispositivo do cliente, ative notificações com:
   - `joao@demo.local / password`
2. Em outro navegador/dispositivo, entre como empresa:
   - `malagueta@demo.local / password`
3. Abra `gest_o_de_ofertas_parceiro.html`
4. Crie ou selecione uma promoção instantânea
5. Clique em **Enviar promoção para clientes vinculados**
6. Verifique:
   - resumo com elegíveis / com notificações ativas / enviados / falhas
   - notificação no dispositivo do cliente

## Como testar push individual pelo admin

1. No dispositivo do cliente, ative notificações com:
   - `cliente.push@demo.local / password`
2. Em outro navegador/dispositivo, entre como admin:
   - `admin@demo.local / password`
3. Abra `gest_o_de_clientes_master.html`
4. No card **Teste de push**, confirme o email:
   - `cliente.push@demo.local`
5. Clique em **Buscar cliente**
6. Verifique:
   - nome
   - email
   - push ativo: sim/nao
   - quantidade de dispositivos
7. Clique em **Enviar push teste**
8. Verifique:
   - resumo operacional no painel
   - notificação recebida no iPhone

## Como testar bônus aniversário

1. Entre como cliente aniversariante:
   - `maria@demo.local / password`
2. Ative notificações no dispositivo dela
3. Entre como empresa:
   - `malagueta@demo.local / password`
4. Abra `gest_o_de_ofertas_parceiro.html`
5. Configure um bônus aniversário ativo
6. Clique em **Enviar elegíveis**
7. Verifique:
   - resumo do envio no painel
   - notificação com título `FELIZ ANIVERSÁRIO!`
   - abertura da página da empresa ao tocar na notificação

## Como testar lembrete de retorno

1. Ative notificações para um cliente inativo elegível
2. Entre como empresa:
   - `malagueta@demo.local / password`
3. Abra `gest_o_de_ofertas_parceiro.html`
4. Configure um lembrete de retorno ativo
5. Clique em **Enviar elegíveis**
6. Verifique:
   - resumo com elegíveis / enviados / sem subscription
   - notificação no cliente elegível
   - ausência de reenvio no mesmo ciclo, se já houver envio registrado

## O que validar no resultado

### No cliente
- o card de push não quebra em navegadores sem suporte
- a permissão só é pedida após clique
- o status visual cobre:
  - ativado
  - bloqueado
  - sem suporte
  - iPhone fora da Tela de Início
  - configuração pendente

### Na empresa
- o resumo do disparo mostra métricas reais:
  - elegíveis
  - com notificações ativas
  - enviados
  - falhas
  - sem subscription
- cliente não vinculado não entra no envio
- admin não recebe push de campanha da empresa

## Limitações conhecidas

- se o usuário bloquear a permissão, precisa reativar nas configurações do navegador
- se o iPhone não estiver instalado na Tela de Início, push pode não funcionar
- sem `VAPID_*`, não existe envio real
- se o usuário nunca ativou notificações, não existe subscription para enviar
- o card admin nao cria subscription fake; ele apenas consulta o status real salvo pelo navegador do cliente
- o clique na notificação depende do navegador permitir foco/navegação da janela

## Railway Variables

Preencher no serviço web:
- `VAPID_PUBLIC_KEY`
- `VAPID_PRIVATE_KEY`
- `VAPID_SUBJECT`

Exemplo de `VAPID_SUBJECT`:

```text
mailto:no-reply@temdetudo.app
```
