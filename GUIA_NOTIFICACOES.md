# 🔔 Como Usar o Sistema de Notificações

## 📚 Documentação Completa

Este guia mostra **como usar notificações** em qualquer página do sistema.

---

## 🚀 Início Rápido - 3 Tipos de Notificação

### 1️⃣ Push Notifications (Navegador)
### 2️⃣ Email Notifications (EmailJS)
### 3️⃣ In-App Notifications (Dentro do App)

---

## 📦 Incluir nas Páginas

### Adicione no `<head>`:

```html
<!-- EmailJS SDK (GRÁTIS - 300 emails/mês) -->
<script src="https://cdn.jsdelivr.net/npm/@emailjs/browser@3/dist/email.min.js"></script>

<!-- Sistema de Notificações -->
<script src="/js/notification-system-simple.js"></script>
```

---

## 1️⃣ PUSH NOTIFICATIONS (Navegador)

### ✅ Pedir Permissão

```javascript
// Pede permissão para notificações push
await NotificationSystem.enablePush();
```

### ✅ Enviar Notificação Push

```javascript
// Simples
NotificationSystem.testPush();

// OU manual
if (Notification.permission === 'granted') {
    new Notification('Título da Notificação', {
        body: 'Mensagem aqui',
        icon: '/images/logo.png',
        badge: '/images/badge.png',
        vibrate: [200, 100, 200]
    });
}
```

### 📱 Exemplo Prático - Quando Cliente Ganha Bônus

```javascript
function notificarBonus(pontos) {
    if (Notification.permission === 'granted') {
        const notification = new Notification('🎁 Novo Bônus!', {
            body: `Você ganhou ${pontos} pontos!`,
            icon: '/images/logo.png',
            vibrate: [200, 100, 200],
            tag: 'bonus-notification'
        });
        
        notification.onclick = () => {
            window.location.href = '/app-bonus.html';
        };
    }
    
    // Também adiciona no histórico in-app
    NotificationSystem.add({
        title: '🎁 Novo Bônus!',
        message: `Você ganhou ${pontos} pontos!`,
        icon: 'fa-gift',
        type: 'success',
        url: '/app-bonus.html'
    });
}
```

---

## 2️⃣ EMAIL NOTIFICATIONS (EmailJS)

### ⚙️ Configuração (apenas 1 vez)

Veja: [`GUIA_EMAILJS_GRATIS.md`](GUIA_EMAILJS_GRATIS.md)

### ✅ Enviar Email

```javascript
// Usando a função do sistema
await NotificationSystem.sendEmail();

// OU manual com EmailJS
const templateParams = {
    to_email: 'cliente@email.com',
    to_name: 'João Silva',
    subject: 'Nova Promoção Disponível!',
    message: 'Você tem uma promoção exclusiva te esperando!',
    promo_title: 'Desconto de 30%',
    bonus_count: '5',
    app_url: window.location.origin
};

await emailjs.send(
    'service_abc123',      // Seu Service ID
    'template_xyz789',     // Seu Template ID
    templateParams
);
```

### 📧 Exemplo Prático - Enviar Email de Promoção

```javascript
async function enviarEmailPromocao(cliente, promocao) {
    const templateParams = {
        to_email: cliente.email,
        to_name: cliente.nome,
        subject: `🎉 ${promocao.titulo}`,
        message: promocao.descricao,
        promo_title: promocao.titulo,
        bonus_count: cliente.bonusCount,
        app_url: window.location.origin + '/app-promocoes.html'
    };
    
    try {
        await emailjs.send('service_abc123', 'template_xyz789', templateParams);
        console.log('✅ Email enviado!');
        
        // Também adiciona no histórico
        NotificationSystem.add({
            title: 'Email Enviado',
            message: `Email de promoção enviado para ${cliente.email}`,
            icon: 'fa-envelope',
            type: 'success'
        });
    } catch (error) {
        console.error('❌ Erro ao enviar email:', error);
    }
}
```

---

## 3️⃣ IN-APP NOTIFICATIONS (Dentro do App)

### ✅ Adicionar Notificação

```javascript
NotificationSystem.add({
    title: 'Título da Notificação',
    message: 'Descrição detalhada aqui',
    icon: 'fa-bell',              // Ícone FontAwesome
    type: 'info',                  // info, success, warning, error
    url: '/pagina-destino.html'    // Opcional
});
```

### 📱 Tipos de Notificação

```javascript
// INFO (azul)
NotificationSystem.add({
    title: 'Nova Atualização',
    message: 'O app foi atualizado com novos recursos',
    icon: 'fa-info-circle',
    type: 'info'
});

// SUCCESS (verde)
NotificationSystem.add({
    title: 'Check-in Realizado!',
    message: 'Você ganhou 10 pontos',
    icon: 'fa-check-circle',
    type: 'success'
});

// WARNING (amarelo)
NotificationSystem.add({
    title: 'Bônus Expirando',
    message: 'Seus pontos expiram em 7 dias',
    icon: 'fa-exclamation-triangle',
    type: 'warning'
});

// ERROR (vermelho)
NotificationSystem.add({
    title: 'Erro no Pagamento',
    message: 'Não foi possível processar o pagamento',
    icon: 'fa-times-circle',
    type: 'error'
});
```

### 🎨 Ícones Disponíveis (FontAwesome)

```javascript
'fa-bell'               // Sino
'fa-gift'               // Presente
'fa-star'               // Estrela
'fa-heart'              // Coração
'fa-trophy'             // Troféu
'fa-fire'               // Fogo
'fa-rocket'             // Foguete
'fa-envelope'           // Email
'fa-check-circle'       // Check
'fa-exclamation-triangle' // Aviso
'fa-times-circle'       // Erro
'fa-info-circle'        // Info
'fa-shopping-cart'      // Carrinho
'fa-percent'            // Desconto
'fa-tag'                // Tag
'fa-tags'               // Tags
'fa-calendar'           // Calendário
'fa-clock'              // Relógio
```

---

## 🎯 Casos de Uso Práticos

### 1. Cliente fez Check-in

```javascript
async function notificarCheckIn(empresa, pontos) {
    // Push notification
    if (Notification.permission === 'granted') {
        new Notification('✅ Check-in Realizado!', {
            body: `Você ganhou ${pontos} pontos na ${empresa}`,
            icon: '/images/logo.png'
        });
    }
    
    // In-app notification
    NotificationSystem.add({
        title: '✅ Check-in Realizado!',
        message: `Você ganhou ${pontos} pontos na ${empresa}`,
        icon: 'fa-check-circle',
        type: 'success',
        url: '/app-bonus.html'
    });
    
    // Email (opcional)
    // await enviarEmailCheckIn(cliente.email, empresa, pontos);
}
```

### 2. Nova Promoção Disponível

```javascript
async function notificarNovaPromocao(promocao) {
    // Push notification
    if (Notification.permission === 'granted') {
        new Notification('🎉 Nova Promoção!', {
            body: promocao.titulo,
            icon: promocao.imagem
        });
    }
    
    // In-app notification
    NotificationSystem.add({
        title: '🎉 Nova Promoção!',
        message: promocao.titulo,
        icon: 'fa-tags',
        type: 'info',
        url: '/app-promocoes.html'
    });
}
```

### 3. Bônus Prestes a Expirar

```javascript
function notificarBonusExpirando(dias, pontos) {
    NotificationSystem.add({
        title: '⚠️ Bônus Expirando',
        message: `Seus ${pontos} pontos expiram em ${dias} dias!`,
        icon: 'fa-exclamation-triangle',
        type: 'warning',
        url: '/app-bonus.html'
    });
}
```

### 4. Aniversário do Cliente

```javascript
async function notificarAniversario(cliente) {
    // Push notification
    if (Notification.permission === 'granted') {
        new Notification('🎂 Feliz Aniversário!', {
            body: `Ganhe um presente especial hoje!`,
            icon: '/images/birthday.png'
        });
    }
    
    // In-app notification
    NotificationSystem.add({
        title: '🎂 Feliz Aniversário!',
        message: 'Você ganhou um bônus especial de aniversário!',
        icon: 'fa-birthday-cake',
        type: 'success',
        url: '/app-bonus-aniversario.html'
    });
    
    // Email
    await emailjs.send('service_abc123', 'template_birthday', {
        to_email: cliente.email,
        to_name: cliente.nome,
        subject: '🎂 Feliz Aniversário!',
        message: 'Ganhe um presente especial hoje!'
    });
}
```

---

## 🔧 Funções Úteis

### Pegar Todas Notificações

```javascript
const notificacoes = NotificationSystem.getAll();
console.log(notificacoes);
```

### Marcar como Lida

```javascript
NotificationSystem.markRead(notificationId);
```

### Deletar Notificação

```javascript
NotificationSystem.delete(notificationId);
```

### Limpar Todas

```javascript
NotificationSystem.clear();
```

### Atualizar Badge (contador)

```javascript
NotificationSystem.updateBadge();
```

---

## 🎨 Adicionar Badge no Menu

### HTML

```html
<a href="/app-notificacoes-config.html" class="nav-item">
    <i class="fas fa-bell"></i>
    <span>Notificações</span>
    <span class="notification-badge">3</span>
</a>
```

### CSS

```css
.notification-badge {
    position: absolute;
    top: 8px;
    right: 8px;
    background: #ef4444;
    color: white;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 10px;
    min-width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
}
```

### JavaScript

```javascript
// Atualiza badge automaticamente
NotificationSystem.updateBadge();
```

---

## ⚡ Performance

### Evitar Spam de Notificações

```javascript
// Limita 1 notificação a cada 5 segundos
let lastNotification = 0;

function notificarComDelay(data) {
    const now = Date.now();
    if (now - lastNotification > 5000) {
        NotificationSystem.add(data);
        lastNotification = now;
    }
}
```

### Agrupar Notificações

```javascript
// Em vez de enviar 10 notificações separadas
// Agrupe em 1 notificação resumida

const novasPromocoes = 5;
NotificationSystem.add({
    title: '🎉 Novas Promoções!',
    message: `Você tem ${novasPromocoes} promoções disponíveis`,
    icon: 'fa-tags',
    type: 'info'
});
```

---

## 📊 Analytics (opcional)

```javascript
function trackNotification(type, action) {
    // Google Analytics, Mixpanel, etc
    if (window.gtag) {
        gtag('event', 'notification', {
            'event_category': type,
            'event_label': action
        });
    }
}

// Uso:
NotificationSystem.add({
    title: 'Teste',
    message: 'Mensagem'
});
trackNotification('in-app', 'created');
```

---

## 🔒 Privacidade

### Pedir Permissão com Contexto

❌ **Ruim:**
```javascript
// Logo ao carregar a página
NotificationSystem.enablePush();
```

✅ **Bom:**
```javascript
// Depois de uma ação do usuário
<button onclick="habilitarNotificacoes()">
    Receber Notificações
</button>

function habilitarNotificacoes() {
    alert('Ative as notificações para receber promoções exclusivas!');
    NotificationSystem.enablePush();
}
```

---

## ✅ Checklist de Implementação

- [ ] Incluir `notification-system-simple.js` na página
- [ ] Incluir EmailJS SDK (se usar emails)
- [ ] Configurar credenciais EmailJS (opcional)
- [ ] Adicionar badge no menu de navegação
- [ ] Testar push notifications
- [ ] Testar email (ou ver simulação)
- [ ] Testar notificações in-app
- [ ] Adicionar notificações nos eventos importantes
- [ ] Configurar Service Worker para push
- [ ] Testar em mobile

---

## 🎬 Demonstração Completa

Acesse: `/app-notificacoes-config.html`

Lá você pode testar:
- ✅ Push Notifications
- ✅ Email Notifications (simulado ou real)
- ✅ In-App Notifications
- ✅ Histórico de notificações
- ✅ Badge contador

---

## 📞 Suporte

Dúvidas? Veja:
- [`GUIA_EMAILJS_GRATIS.md`](GUIA_EMAILJS_GRATIS.md) - Como configurar emails
- `notification-system-simple.js` - Código-fonte comentado
- `app-notificacoes-config.html` - Exemplo funcional

---

**Criado para o Sistema "Tem de Tudo"**  
*Versão 1.0 - Janeiro 2025*
