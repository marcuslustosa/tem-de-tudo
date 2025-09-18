# Instruções para Deploy do Laravel TemDeTudo no Render

## 1. Configuração do arquivo `.env`

Execute o script abaixo na raiz do projeto backend para gerar a chave e criar o `.env`:

```bash
# Gere uma chave aleatória base64 de 32 bytes
APP_KEY=$(php -r "echo 'base64:'.base64_encode(random_bytes(32));")

cat > .env <<EOL
APP_NAME=TemDeTudo
APP_ENV=production
APP_KEY=$APP_KEY
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
MAIL_FROM_NAME="\${APP_NAME}"

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

VITE_PUSHER_APP_KEY="\${PUSHER_APP_KEY}"
VITE_PUSHER_HOST="\${PUSHER_HOST}"
VITE_PUSHER_PORT="\${PUSHER_PORT}"
VITE_PUSHER_SCHEME="\${PUSHER_SCHEME}"
VITE_PUSHER_APP_CLUSTER="\${PUSHER_APP_CLUSTER}"
EOL

echo ".env criado com sucesso!"
```

## 2. Limpar caches e permissões

Execute os comandos abaixo para limpar caches e garantir permissões corretas:

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

## 3. Rodar migrations e seeders

Execute para criar as tabelas e popular dados iniciais:

```bash
php artisan migrate --force
php artisan db:seed --force
```

## 4. Comando para iniciar o servidor no Render

No Render, configure o comando de start como:

```bash
php artisan serve --host=0.0.0.0 --port=$PORT
```

## 5. Observações importantes sobre erros 500 comuns

- **APP_KEY ausente ou inválida:** O script acima gera uma chave válida automaticamente.
- **Permissões incorretas:** As pastas `storage` e `bootstrap/cache` devem ter permissão de escrita.
- **Configuração do banco PostgreSQL:** As credenciais estão pré-configuradas no script.
- **Cache desatualizado:** Sempre limpe o cache após alterações.
- **Extensão pdo_pgsql:** Certifique-se de que o Render tem a extensão PostgreSQL instalada.

Seguindo esses passos, o Laravel TemDeTudo funcionará perfeitamente no Render com PostgreSQL.

---

Para dúvidas sobre o deploy no Render, consulte a documentação oficial.
