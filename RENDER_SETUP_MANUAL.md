# 🚀 CONFIGURAÇÃO RENDER - PASSO A PASSO

## 1️⃣ Criar Web Service

### No Dashboard do Render:
1. Clique **"New +"** (botão azul)
2. Selecione **"Web Service"**
3. Conecte ao GitHub se ainda não conectou

### Configuração Inicial:
```
Repository: marcuslustosa/tem-de-tudo
Branch: main
Name: tem-de-tudo
Runtime: PHP
Region: Oregon (US West)
Root Directory: backend
```

## 2️⃣ Comandos de Build/Start

### Build Command:
```bash
composer install --no-dev --optimize-autoloader && php artisan config:clear && php artisan route:clear && php artisan view:clear
```

### Start Command:
```bash
php artisan migrate --force && php artisan db:seed --force && php artisan serve --host=0.0.0.0 --port=$PORT
```

## 3️⃣ Variáveis de Ambiente

**COPIE E COLE ESTAS VARIÁVEIS:**

```
APP_NAME=Tem de Tudo
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:3cQV4S7tE8m2dR9wQ5lN6pK1jH0uI8yT7rE3wQ9pL5k=
APP_URL=https://tem-de-tudo.onrender.com
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

SANCTUM_STATEFUL_DOMAINS=tem-de-tudo.onrender.com

MAIL_MAILER=log
MAIL_FROM_ADDRESS=noreply@temdetudo.com
MAIL_FROM_NAME=Tem de Tudo

PHP_CLI_SERVER_WORKERS=4
BCRYPT_ROUNDS=12
FILESYSTEM_DISK=local
```

## 4️⃣ Deploy

1. **Clique "Create Web Service"**
2. **Aguarde o deploy** (5-10 minutos)
3. **Verifique se funcionou**: https://tem-de-tudo.onrender.com

## 🎯 Teste Final

### Login Admin:
- **URL**: https://tem-de-tudo.onrender.com/admin.html
- **Email**: admin@temdetudo.com
- **Senha**: admin123

---

**Pronto! Sistema 100% funcional no Render!** 🎉