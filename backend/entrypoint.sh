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

export DB_CONNECTION="${DB_CONNECTION:-pgsql}"
export DB_HOST="${DB_HOST:-${PGHOST:-}}"
export DB_PORT="${DB_PORT:-${PGPORT:-5432}}"
export DB_DATABASE="${DB_DATABASE:-${PGDATABASE:-}}"
export DB_USERNAME="${DB_USERNAME:-${PGUSER:-}}"
export DB_PASSWORD="${DB_PASSWORD:-${PGPASSWORD:-}}"
export DB_SSLMODE="${DB_SSLMODE:-${PGSSLMODE:-require}}"
export PGSSLMODE="${PGSSLMODE:-$DB_SSLMODE}"

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
db_retries="${DB_CONNECT_RETRIES:-20}"
db_retry_sleep="${DB_CONNECT_RETRY_SLEEP_SECONDS:-3}"
db_connected="false"

for attempt in $(seq 1 "${db_retries}"); do
  if PGPASSWORD="${DB_PASSWORD}" PGSSLMODE="${PGSSLMODE}" psql \
    -h "${DB_HOST}" \
    -U "${DB_USERNAME}" \
    -d "${DB_DATABASE}" \
    -c "SELECT 1;" > /dev/null 2>&1; then
    db_connected="true"
    break
  fi

  echo "Banco indisponivel (tentativa ${attempt}/${db_retries}), aguardando ${db_retry_sleep}s..."
  sleep "${db_retry_sleep}"
done

if [ "${db_connected}" != "true" ]; then
  echo "Erro ao conectar no banco apos ${db_retries} tentativas."
  exit 1
fi
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

# Padrao: usa servidor embutido do Laravel para evitar crashes de MPM no Apache.
# Apache so e usado quando WEB_SERVER=apache E ENABLE_APACHE=true.
if [ "${WEB_SERVER:-artisan}" != "apache" ] || [ "${ENABLE_APACHE:-false}" != "true" ]; then
  APP_PORT="${PORT:-8080}"
  echo "Starting Laravel server on port ${APP_PORT} (safe mode)..."
  if [ "${WEB_SERVER:-artisan}" = "apache" ] && [ "${ENABLE_APACHE:-false}" != "true" ]; then
    echo "WEB_SERVER=apache ignorado porque ENABLE_APACHE!=true."
  fi
  exec php artisan serve --host=0.0.0.0 --port="${APP_PORT}"
fi

echo "Starting Apache..."
# Safety net: garante apenas um MPM (prefork)
rm -f /etc/apache2/mods-enabled/mpm_event.load /etc/apache2/mods-enabled/mpm_event.conf
rm -f /etc/apache2/mods-enabled/mpm_worker.load /etc/apache2/mods-enabled/mpm_worker.conf
ln -sf /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load
ln -sf /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf

if ! apache2ctl -M 2>/dev/null | grep -q 'mpm_prefork_module'; then
  echo "ERRO: mpm_prefork nao carregado"
  apache2ctl -M || true
  exit 1
fi

if [ "$(apache2ctl -M 2>/dev/null | grep -c 'mpm_')" -ne 1 ]; then
  echo "ERRO: mais de um MPM carregado"
  apache2ctl -M || true
  exit 1
fi

if [ "$#" -gt 0 ]; then
  exec "$@"
fi

exec apache2-foreground

