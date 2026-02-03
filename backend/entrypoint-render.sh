#!/bin/bash
set -e

echo "========================================="
echo "🚀 TEM DE TUDO - Deploy Render.com"
echo "========================================="

# Substituir variáveis de ambiente no .env
echo "🔧 Configurando variáveis de ambiente..."
sed -i "s/\${PGHOST}/$PGHOST/g" .env
sed -i "s/\${PGPORT}/$PGPORT/g" .env
sed -i "s/\${PGDATABASE}/$PGDATABASE/g" .env
sed -i "s/\${PGUSER}/$PGUSER/g" .env
sed -i "s/\${PGPASSWORD}/$PGPASSWORD/g" .env

# Aguardar PostgreSQL estar pronto
echo "⏳ Aguardando PostgreSQL..."
sleep 10

# Executar migrations
echo "📦 Executando migrations..."
php artisan migrate --force --no-interaction

# Executar seeders SEMPRE (usa updateOrCreate, não duplica)
echo "🌱 Populando banco de dados..."
php artisan db:seed --force --class=DatabaseSeeder --no-interaction

# Limpar cache
echo "🧹 Limpando caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Otimizar para produção
echo "⚡ Otimizando aplicação..."
php artisan config:cache
php artisan route:cache

echo "✅ Deploy concluído! Iniciando servidor..."
echo "========================================="

# Iniciar Apache
exec apache2-foreground
