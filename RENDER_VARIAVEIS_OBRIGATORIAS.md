# 🎯 RENDER - VARIÁVEIS DE AMBIENTE OBRIGATÓRIAS

## ⚠️ IMPORTANTE: Adicionar no painel do Render

Vá em **Environment Variables** e adicione TODAS estas variáveis:

```
APP_NAME=Tem de Tudo
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:3cQV4S7tE8m2dR9wQ5lN6pK1jH0uI8yT7rE3wQ9pL5k=
APP_URL=https://app-tem-de-tudo.onrender.com
APP_LOCALE=pt_BR
APP_FALLBACK_LOCALE=pt_BR

DB_CONNECTION=pgsql
DB_SSL_MODE=require
DB_HOST=dpg-d3vps0k9c44c738q64gg-a
DB_PORT=5432
DB_DATABASE=tem_de_tudo_database
DB_USERNAME=tem_de_tudo_database_user
DB_PASSWORD=9P0c4gV4RZd8moh9ZYqGIo0BmyZ10XhA

SESSION_DRIVER=database
SESSION_LIFETIME=120
CACHE_STORE=database
QUEUE_CONNECTION=database
LOG_LEVEL=error
LOG_CHANNEL=stack

JWT_SECRET=t3md3tud0syst3mj4wt53cr3tk3y2024s3cur3h4shk3y
JWT_TTL=60
JWT_REFRESH_TTL=20160
JWT_ALGO=HS256

SANCTUM_STATEFUL_DOMAINS=app-tem-de-tudo.onrender.com

MAIL_MAILER=log
MAIL_FROM_ADDRESS=noreply@temdetudo.com
MAIL_FROM_NAME=Tem de Tudo

PHP_CLI_SERVER_WORKERS=4
BCRYPT_ROUNDS=12
FILESYSTEM_DISK=local
```

## 🚀 Depois de adicionar:

1. **Salvar** as variáveis
2. **Redeploy** automático vai acontecer
3. **Sistema funcionará** perfeitamente

**SEM ESSAS VARIÁVEIS O BANCO NÃO FUNCIONA!** ⚠️