#!/bin/bash
set -euo pipefail

echo "=== Iniciando Tem de Tudo ==="

cd /var/www/html

echo "Configurando diretorios..."
mkdir -p storage/framework/{sessions,views,cache}
mkdir -p storage/logs
mkdir -p bootstrap/cache
mkdir -p /var/log
chmod -R 775 storage bootstrap/cache || true

echo "Preparando ambiente..."
if [ ! -f .env ] && [ -f .env.example ]; then
  cp .env.example .env
fi

if [ -n "${DATABASE_URL:-}" ] && [ -z "${DB_HOST:-}" ] && [ -f "/var/www/html/docker/export-db-env.php" ]; then
  echo "Derivando DB_* a partir de DATABASE_URL..."
  eval "$(php /var/www/html/docker/export-db-env.php)"
fi

required_vars=(DB_CONNECTION DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD)
for var in "${required_vars[@]}"; do
  if [ -z "${!var:-}" ]; then
    echo "Variavel obrigatoria ausente: ${var}"
    exit 1
  fi
done

if [ -z "${APP_KEY:-}" ]; then
  if [ -f .env ] && grep -q '^APP_KEY=base64:' .env; then
    echo "APP_KEY encontrada no .env."
  else
    echo "Gerando APP_KEY..."
    php artisan key:generate --force
  fi
else
  echo "APP_KEY fornecida por variavel de ambiente."
fi

echo "Testando conexao com PostgreSQL..."
PGPASSWORD="${DB_PASSWORD}" psql -h "${DB_HOST}" -U "${DB_USERNAME}" -d "${DB_DATABASE}" -c "\l" > /dev/null || {
    echo "Erro ao conectar no banco"
    exit 1
}
echo "Conexao PostgreSQL estabelecida"

if [ "${RUN_MIGRATIONS_ON_START:-true}" = "true" ]; then
  echo "Executando migrations..."
  php artisan migrate --force --no-interaction
else
  echo "Migrations no start desativadas (RUN_MIGRATIONS_ON_START=false)."
fi

if [ "${SEED_ON_START:-false}" = "true" ]; then
  echo "Executando seeders..."
  php artisan db:seed --force --no-interaction
else
  echo "Seed no start desativado (SEED_ON_START=false)."
fi

echo "Limpando caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

echo "Configurando storage link..."
php artisan storage:link || true

echo "Verificacao final..."
php artisan migrate:status

echo "=== Sistema Pronto ==="

echo "Iniciando queue worker..."
php artisan queue:work --sleep=3 --tries=3 --max-time=3600 --daemon >> /var/log/queue-worker.log 2>&1 &
echo "Queue worker PID: $!"

echo "Iniciando scheduler..."
( while true; do php artisan schedule:run >> /var/log/scheduler.log 2>&1; sleep 60; done ) &
echo "Scheduler PID: $!"

echo "Starting Apache..."
exec apache2-foreground
