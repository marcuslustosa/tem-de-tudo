# 🔥 Configuração Firebase Cloud Messaging

## Passo 1: Criar Projeto no Firebase

1. Acesse: https://console.firebase.google.com
2. Clique em "Adicionar projeto"
3. Nome do projeto: `temdetudo-prod`
4. Desabilite Google Analytics (opcional)
5. Clique em "Criar projeto"

## Passo 2: Ativar Firebase Cloud Messaging API (v1)

1. No painel do projeto, vá em **Configurações do projeto** (ícone de engrenagem)
2. Vá na aba **Cloud Messaging**
3. Clique em **Gerenciar APIs no Console do Google Cloud**
4. Procure por "Firebase Cloud Messaging API"
5. Clique em **Ativar** (se ainda não estiver ativada)
6. Anote o **Sender ID** na tela de Cloud Messaging (algo como: 123456789012)

## Passo 3: Gerar Credenciais (Service Account)

**MÉTODO NOVO (Recomendado):**

1. No painel do projeto Firebase, vá em **Configurações do projeto**
2. Vá na aba **Contas de serviço**
3. Clique em **Gerar nova chave privada**
4. Confirme e baixe o arquivo JSON
5. **IMPORTANTE:** Guarde esse arquivo com segurança (contém credenciais sensíveis)
6. Nomeie como `firebase-credentials.json`

**OU use Certificados Web (para notificações browser):**

1. Na aba **Cloud Messaging**
2. Role até **Configuração da Web**
3. Em **Certificados push da Web**, clique em **Gerar par de chaves**
4. Copie a **Chave pública** gerada (chamada de VAPID key)

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

## Passo 5: Configurar Credenciais no Projeto

**OPÇÃO A: Usar arquivo JSON (Mais seguro):**

1. Coloque o arquivo `firebase-credentials.json` no diretório `backend/storage/`
2. Adicione ao `.gitignore`: `storage/firebase-credentials.json`
3. No `.env.render`:

```env
# Firebase Push Notifications (API v1)
FIREBASE_CREDENTIALS=/var/www/html/storage/firebase-credentials.json
FIREBASE_PROJECT_ID=temdetudo-prod
```

4. No Render, faça upload do arquivo como **Secret File**

**OPÇÃO B: Usar variáveis de ambiente (Mais fácil para teste):**

Copie o conteúdo do arquivo JSON e adicione ao `.env.render`:

```env
# Firebase Push Notifications
FIREBASE_PROJECT_ID=temdetudo-prod
FIREBASE_API_KEY=AIzaSy[COLE_AQUI_A_API_KEY]
FIREBASE_SENDER_ID=123456789012
FIREBASE_WEB_VAPID_KEY=[COLE_AQUI_A_VAPID_KEY_PUBLICA]

# Para autenticação server-side
FIREBASE_CLIENT_EMAIL=firebase-adminsdk-xxxxx@temdetudo-prod.iam.gserviceaccount.com
FIREBASE_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\nXXXXXXXX\n-----END PRIVATE KEY-----\n"
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

## Passo 8: Atualizar o Código (Nova API v1)

A aplicação já está preparada para usar Firebase, mas você pode precisar ajustar:

**Instalar biblioteca Google Cloud Messaging:**

```bash
composer require kreait/firebase-php
```

**Atualizar FirebaseNotificationService.php:**

```php
<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;

class FirebaseNotificationService
{
    private $messaging;

    public function __construct()
    {
        $credentialsPath = env('FIREBASE_CREDENTIALS');
        
        if ($credentialsPath && file_exists($credentialsPath)) {
            $firebase = (new Factory)->withServiceAccount($credentialsPath);
        } else {
            // Fallback para variáveis de ambiente
            $firebase = (new Factory)
                ->withProjectId(env('FIREBASE_PROJECT_ID'))
                ->withServiceAccount([
                    'type' => 'service_account',
                    'project_id' => env('FIREBASE_PROJECT_ID'),
                    'private_key' => str_replace('\\n', "\n", env('FIREBASE_PRIVATE_KEY')),
                    'client_email' => env('FIREBASE_CLIENT_EMAIL'),
                ]);
        }
        
        $this->messaging = $firebase->createMessaging();
    }

    public function sendNotification($deviceToken, $title, $body, $data = [])
    {
        $message = CloudMessage::withTarget('token', $deviceToken)
            ->withNotification([
                'title' => $title,
                'body' => $body,
            ])
            ->withData($data);

        try {
            $this->messaging->send($message);
            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
```

## ✅ Pronto!

Agora as notificações push funcionarão com a API v1 (não será descontinuada)!

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

### Erro: "401 Unauthorized" com nova API
- Verifique se o arquivo JSON está correto
- Certifique-se que a Firebase Cloud Messaging API está ativada no Google Cloud
- Verifique se o service account tem permissões

### Migração da API Legacy
Se você tinha a API legada configurada:
1. A API legada foi descontinuada em 20/06/2024
2. Você DEVE usar a API v1
3. Não é possível usar a Server Key antiga (formato AAAA...:APA91b...)
4. Precisa usar Service Account (arquivo JSON)

---

## 📱 Notificações Web (Browser)

Para receber notificações no navegador, adicione no seu JavaScript:

```javascript
// public/js/firebase-config.js
import { initializeApp } from 'firebase/app';
import { getMessaging, getToken } from 'firebase/messaging';

const firebaseConfig = {
  apiKey: "AIzaSy...",
  projectId: "temdetudo-prod",
  messagingSenderId: "123456789012",
  appId: "1:123456789012:web:..."
};

const app = initializeApp(firebaseConfig);
const messaging = getMessaging(app);

// Solicitar permissão e obter token
async function requestNotificationPermission() {
  const permission = await Notification.requestPermission();
  
  if (permission === 'granted') {
    const token = await getToken(messaging, {
      vapidKey: 'SUA_VAPID_KEY_PUBLICA_AQUI'
    });
    
    // Enviar token para backend
    await fetch('/api/notifications/fcm-token', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + userToken
      },
      body: JSON.stringify({ fcm_token: token })
    });
  }
}
```

Crie também `public/firebase-messaging-sw.js` (Service Worker):

```javascript
importScripts('https://www.gstatic.com/firebasejs/10.0.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.0.0/firebase-messaging-compat.js');

firebase.initializeApp({
  apiKey: "AIzaSy...",
  projectId: "temdetudo-prod",
  messagingSenderId: "123456789012",
  appId: "1:123456789012:web:..."
});

const messaging = firebase.messaging();

messaging.onBackgroundMessage((payload) => {
  const notificationTitle = payload.notification.title;
  const notificationOptions = {
    body: payload.notification.body,
    icon: '/icon-192x192.png'
  };

  self.registration.showNotification(notificationTitle, notificationOptions);
});
```
