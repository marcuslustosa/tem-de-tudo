# 🔥 Configuração Firebase Cloud Messaging

## Passo 1: Criar Projeto no Firebase

1. Acesse: https://console.firebase.google.com
2. Clique em "Adicionar projeto"
3. Nome do projeto: `temdetudo-prod`
4. Desabilite Google Analytics (opcional)
5. Clique em "Criar projeto"

## Passo 2: Ativar Cloud Messaging

1. No painel do projeto, vá em **Configurações do projeto** (ícone de engrenagem)
2. Vá na aba **Cloud Messaging**
3. Anote o **Sender ID** (algo como: 123456789012)

## Passo 3: Gerar Server Key (Legacy)

1. Na mesma tela de Cloud Messaging
2. Role até **Cloud Messaging API (Legacy)**
3. Se não estiver ativada, clique em "Ativar"
4. Copie a **Chave do servidor** (Server Key)
   - Formato: `AAAA....:APA91b...` (muito longo)

## Passo 4: Obter credenciais Web

1. No painel do projeto, vá em **Configurações do projeto**
2. Role até **Seus aplicativos**
3. Clique no ícone **Web** (</>)
4. Nome do app: `TemDeTudo Web`
5. Marque "Também configurar o Firebase Hosting"
6. Clique em "Registrar app"
7. Copie as credenciais:
   ```javascript
   apiKey: "AIzaSy..."
   projectId: "temdetudo-prod"
   messagingSenderId: "123456789012"
   appId: "1:123456789012:web:..."
   ```

## Passo 5: Atualizar .env.render

Edite o arquivo `.env.render` e atualize:

```env
# Firebase Push Notifications
FIREBASE_SERVER_KEY=AAAA1234567890:APA91b[COLE_AQUI_A_SERVER_KEY_COMPLETA]
FIREBASE_SENDER_ID=123456789012
FIREBASE_API_KEY=AIzaSy[COLE_AQUI_A_API_KEY]
FIREBASE_PROJECT_ID=temdetudo-prod
```

## Passo 6: Atualizar no Render

1. Acesse o painel do Render: https://dashboard.render.com
2. Selecione o serviço `tem-de-tudo`
3. Vá em **Environment**
4. Adicione/atualize as variáveis:
   - `FIREBASE_SERVER_KEY`
   - `FIREBASE_SENDER_ID`
   - `FIREBASE_API_KEY`
   - `FIREBASE_PROJECT_ID`
5. Salve (vai fazer redeploy automático)

## Passo 7: Testar

Execute via Postman ou curl:

```bash
POST https://tem-de-tudo.onrender.com/api/admin/notifications/test
Authorization: Bearer [seu_token_jwt]
Content-Type: application/json

{
  "user_id": 1,
  "title": "Teste",
  "message": "Testando notificação push"
}
```

## ✅ Pronto!

Agora as notificações push funcionarão de verdade!

---

## 🔧 Troubleshooting

### Erro: "Unauthorized"
- Verifique se a Server Key está correta
- Certifique-se que Cloud Messaging API está ativada

### Erro: "Invalid registration token"
- O usuário precisa primeiro registrar o FCM token
- Use: `POST /api/notifications/fcm-token`

### Notificações não chegam
- Verifique se o app web tem permissão de notificações
- Teste no browser console: `Notification.permission`
