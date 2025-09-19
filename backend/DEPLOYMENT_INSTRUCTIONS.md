# Instruções para Deploy do Laravel TemDeTudo no Render

## 1. Configurar Variáveis de Ambiente no Render

No painel do Render, vá para Environment e adicione as seguintes variáveis:

```
APP_NAME=TemDeTudo
APP_ENV=production
APP_KEY=base64:s+zyjEb/+vh031mPReJlkhxQZ/3owX5hnAtkJLe2Jqw=
APP_DEBUG=false
APP_URL=https://tem-de-tudo.onrender.com

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=pgsql
DB_HOST=dpg-d3649r9r0fns73bk8af0-a.oregon-postgres.render.com
DB_PORT=5432
DB_DATABASE=temdetudo
DB_USERNAME=temdetudo_user
DB_PASSWORD=iGkxwfolwLle003d2Q2OdREQ0MF0OB12

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_HOST="${PUSHER_HOST}"
VITE_PUSHER_PORT="${PUSHER_PORT}"
VITE_PUSHER_SCHEME="${PUSHER_SCHEME}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

## 2. Comando de Build (opcional)

Se necessário, configure o comando de build como:

```bash
composer install --no-dev --optimize-autoloader
```

## 3. Comando de Start

Configure o comando de start como:

```bash
php artisan serve --host=0.0.0.0 --port=$PORT
```

## 4. Deploy do Frontend

Para o frontend estático, crie um novo serviço Static Site no Render apontando para a pasta `frontend/`.

## 5. Observações

- O backend serve o frontend via fallback SPA.
- APP_KEY é base64 válida.
- Banco PostgreSQL pré-configurado.
- Sem erros 500 esperados.

---

Projeto pronto para produção no Render.
