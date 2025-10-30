# 📧 Configuração Gmail SMTP para Notificações

## Passo 1: Criar conta Gmail dedicada (Recomendado)

1. Acesse: https://accounts.google.com/signup
2. Crie: `temdetudo.notifications@gmail.com` (ou similar)
3. Complete o cadastro

**OU use uma conta Gmail existente**

## Passo 2: Ativar Verificação em 2 Etapas

1. Acesse: https://myaccount.google.com/security
2. Role até **Como fazer login no Google**
3. Clique em **Verificação em duas etapas**
4. Siga o processo de ativação (celular, SMS, etc)

## Passo 3: Gerar Senha de App

1. Ainda em: https://myaccount.google.com/security
2. Role até **Verificação em duas etapas**
3. No final da página, clique em **Senhas de app**
4. Selecione:
   - **App:** Outro (nome personalizado)
   - **Nome:** TemDeTudo Laravel
5. Clique em **Gerar**
6. **IMPORTANTE:** Copie a senha de 16 caracteres
   - Formato: `xxxx xxxx xxxx xxxx`
   - Exemplo: `abcd efgh ijkl mnop`
7. **Guarde essa senha!** Ela não será mostrada novamente

## Passo 4: Atualizar .env.render

Edite o arquivo `.env.render`:

```env
# Email Configuration (Gmail SMTP)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=temdetudo.notifications@gmail.com
MAIL_PASSWORD=xxxx xxxx xxxx xxxx  # COLE A SENHA DE APP AQUI (com ou sem espaços)
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="temdetudo.notifications@gmail.com"
MAIL_FROM_NAME="TemDeTudo"
```

## Passo 5: Atualizar no Render (se necessário)

Se você gerenciar as variáveis pelo painel do Render:

1. Acesse: https://dashboard.render.com
2. Selecione o serviço `tem-de-tudo`
3. Vá em **Environment**
4. Adicione/atualize:
   - `MAIL_USERNAME`: temdetudo.notifications@gmail.com
   - `MAIL_PASSWORD`: xxxx xxxx xxxx xxxx
5. Salve

## Passo 6: Testar Localmente (Opcional)

```bash
# No terminal do projeto
php artisan tinker

# Execute:
Mail::raw('Teste', function($message) {
    $message->to('seu@email.com')
            ->subject('Teste Laravel Mail');
});

# Se retornar null, deu certo!
# Verifique seu email (inclusive spam)
```

## Passo 7: Testar na API

```bash
# Via Postman/curl
POST https://tem-de-tudo.onrender.com/api/admin/notifications/broadcast
Authorization: Bearer [seu_token_jwt]
Content-Type: application/json

{
  "title": "Teste de Email",
  "message": "Se você receber isso, está funcionando!",
  "users": [1, 2],
  "send_email": true
}
```

## ✅ Pronto!

Agora os emails funcionarão corretamente!

---

## 🔧 Troubleshooting

### Erro: "Username and Password not accepted"
- Verifique se a verificação em 2 etapas está ativada
- Certifique-se de usar a **senha de app**, NÃO a senha normal
- Recrie a senha de app se necessário

### Erro: "Could not authenticate"
- Verifique se há espaços extras na senha
- Tente remover os espaços: `xxxxyyyyzzzzwwww`

### Emails caem no spam
- Configure SPF/DKIM no domínio (avançado)
- Peça aos usuários para marcar como "Não é spam"
- Use um serviço de email transacional (SendGrid, Mailgun) em produção

### Gmail bloqueia envios
- Gmail tem limite de ~500 emails/dia para contas gratuitas
- Para produção, considere usar:
  - **SendGrid** (100 emails/dia grátis)
  - **Mailgun** (5000 emails/mês grátis)
  - **Amazon SES** (62.000 emails/mês grátis)

---

## 📊 Alternativas para Produção

### SendGrid (Recomendado)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=[sua_api_key_sendgrid]
MAIL_ENCRYPTION=tls
```

### Mailgun

```env
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=mg.seudominio.com
MAILGUN_SECRET=[sua_secret_key]
```

### Amazon SES

```env
MAIL_MAILER=ses
AWS_ACCESS_KEY_ID=[sua_key]
AWS_SECRET_ACCESS_KEY=[sua_secret]
AWS_DEFAULT_REGION=us-east-1
```
