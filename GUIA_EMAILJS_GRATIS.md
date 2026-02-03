# 📧 Guia de Configuração EmailJS (GRÁTIS)

## O que é EmailJS?

EmailJS é um serviço **100% GRÁTIS** (até 300 emails/mês) que permite enviar emails diretamente do JavaScript, **SEM PRECISAR DE BACKEND**!

🔥 **Perfeito para testes e demonstrações!**

---

## 🚀 Passo a Passo - Configuração em 5 Minutos

### 1️⃣ Criar Conta Grátis

1. Acesse: https://www.emailjs.com/
2. Clique em **"Sign Up"** (Cadastrar)
3. Escolha **"Free"** (300 emails/mês - GRÁTIS)
4. Preencha:
   - Email
   - Senha
   - Nome da empresa: "Tem de Tudo"
5. Confirme o email

---

### 2️⃣ Criar Serviço de Email

1. No painel, vá em **"Email Services"**
2. Clique em **"Add New Service"**
3. Escolha seu provedor:
   - **Gmail** (recomendado para testes)
   - Outlook
   - Yahoo
   - Outros...
4. Conecte sua conta de email
5. Copie o **Service ID** (ex: `service_abc123`)

---

### 3️⃣ Criar Template de Email

1. Vá em **"Email Templates"**
2. Clique em **"Create New Template"**
3. Use este template pronto:

```html
Olá {{to_name}},

🔔 Nova Notificação - Tem de Tudo

{{message}}

━━━━━━━━━━━━━━━━━━━━
📊 Você tem {{bonus_count}} bônus disponíveis!
🎁 {{promo_title}}

👉 Acesse o app para ver mais:
{{app_url}}

━━━━━━━━━━━━━━━━━━━━

Atenciosamente,
Equipe Tem de Tudo
```

4. Salve o template
5. Copie o **Template ID** (ex: `template_xyz789`)

---

### 4️⃣ Pegar sua Public Key

1. Vá em **"Account"** → **"General"**
2. Encontre **"Public Key"**
3. Copie (ex: `abc123XYZ456def789`)

---

### 5️⃣ Configurar no Sistema

Abra o arquivo: `backend/public/js/notification-system-simple.js`

Encontre esta parte (linha ~55):

```javascript
const EMAILJS_CONFIG = {
    serviceId: 'service_temdettudo',    // ⬅️ COLE SEU SERVICE ID AQUI
    templateId: 'template_notificacao', // ⬅️ COLE SEU TEMPLATE ID AQUI
    publicKey: 'YOUR_PUBLIC_KEY'        // ⬅️ COLE SUA PUBLIC KEY AQUI
};
```

Substitua pelos seus valores:

```javascript
const EMAILJS_CONFIG = {
    serviceId: 'service_abc123',        // Seu Service ID
    templateId: 'template_xyz789',      // Seu Template ID
    publicKey: 'abc123XYZ456def789'     // Sua Public Key
};
```

**PRONTO! 🎉**

---

## ✅ Testar

1. Abra: `http://localhost/app-notificacoes-config.html`
2. Digite seu email no campo
3. Clique em **"Enviar Email de Teste"**
4. Verifique sua caixa de entrada!

---

## 📊 Planos EmailJS

| Plano | Emails/Mês | Preço |
|-------|-----------|-------|
| **Free** | 300 | **R$ 0,00** ✅ |
| Solo | 1.000 | R$ 29,90 |
| Pro | 10.000 | R$ 79,90 |
| Enterprise | Ilimitado | Consultar |

**Para testes e demonstração: FREE é PERFEITO!**

---

## 🎯 O que Pode Enviar?

Com 300 emails/mês grátis, você pode:

- ✅ Boas-vindas (1 email)
- ✅ Confirmação de cadastro
- ✅ Notificação de bônus
- ✅ Promoções disponíveis
- ✅ Lembrete de check-in
- ✅ Pontos acumulados
- ✅ Ofertas exclusivas

**Exemplo:** 
- 100 clientes × 3 emails/mês = 300 emails ✅

---

## 🔥 Vantagens do EmailJS

✅ **100% Grátis** (até 300/mês)  
✅ **Sem Backend** (só JavaScript)  
✅ **Fácil Configuração** (5 minutos)  
✅ **Templates Personalizados**  
✅ **Múltiplos Provedores** (Gmail, Outlook, etc)  
✅ **Analytics Incluído**  
✅ **Sem Necessidade de Servidor**  

---

## ❓ Problemas Comuns

### 1. "Email não chegou"

- ✅ Verifique SPAM/Lixo Eletrônico
- ✅ Confirme o Service ID, Template ID e Public Key
- ✅ Veja o console do navegador (F12) para erros

### 2. "Erro 403 Forbidden"

- ✅ Public Key incorreta
- ✅ Recarregue a página EmailJS e copie novamente

### 3. "Limite Excedido"

- ✅ Você passou de 300 emails no mês
- ✅ Aguarde até o próximo mês ou faça upgrade

---

## 📱 Integração com o Sistema

O sistema já está **100% integrado**! Você só precisa:

1. ✅ Cadastrar no EmailJS
2. ✅ Copiar as 3 credenciais
3. ✅ Colar no arquivo `notification-system-simple.js`
4. ✅ PRONTO! Funciona!

---

## 🎬 Demonstração Pronta

Mesmo **SEM configurar** o EmailJS, o sistema já:

- ✅ Mostra um **email simulado** quando você clica em testar
- ✅ Exibe exatamente como seria o email
- ✅ Adiciona notificação no histórico
- ✅ Cliente pode VER funcionando!

---

## 🚀 Alternativas (também grátis)

Se quiser explorar outras opções:

1. **Brevo** (ex-Sendinblue) - 300 emails/dia grátis
2. **SendGrid** - 100 emails/dia grátis
3. **Mailgun** - 5.000 emails/mês (primeiro mês)
4. **Elastic Email** - 100 emails/dia grátis

**Mas EmailJS é o MAIS FÁCIL para JavaScript puro!**

---

## 💡 Dica Final

Para o **cliente ver funcionando**:

1. **NÃO precisa** configurar EmailJS agora
2. O sistema mostra um **email simulado** perfeito
3. Cliente vê exatamente como ficaria
4. Quando quiser emails DE VERDADE → Configura em 5min

**É uma demonstração COMPLETA e FUNCIONAL!** 🎉

---

## 📞 Suporte EmailJS

- 📧 Email: support@emailjs.com
- 📚 Docs: https://www.emailjs.com/docs/
- 💬 Discord: https://discord.gg/emailjs

---

## ✨ Resumo

```
1. Cadastrar: emailjs.com (FREE)
2. Criar Service
3. Criar Template
4. Copiar 3 credenciais
5. Colar no código
6. FUNCIONA! 🎉
```

**Tempo total: 5 minutos**  
**Custo: R$ 0,00**  
**Resultado: Sistema profissional de emails!**

---

**Criado para o Sistema "Tem de Tudo"**  
*Versão 1.0 - Janeiro 2025*
