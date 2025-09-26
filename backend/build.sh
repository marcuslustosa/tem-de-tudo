#!/bin/bash

# Script de build otimizado para Render.com
echo "🚀 Iniciando build para Render..."

# Navegar para o diretório backend
cd backend

echo "📦 Instalando dependências do Composer..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "⚙️ Configurando ambiente..."
cp .env.render .env

echo "🔑 Gerando chave da aplicação..."
php artisan key:generate --force

echo "🗄️ Preparando banco SQLite..."
touch database/database.sqlite
chmod 664 database/database.sqlite

echo "📋 Executando migrações..."
php artisan migrate --force

echo "🌱 Executando seeders..."
php artisan db:seed --force

echo "⚡ Otimizando Laravel..."
php artisan config:cache
php artisan route:cache  
php artisan view:cache

echo "🎯 Build concluído com sucesso!"
echo "Sistema pronto para produção no Render!"

# Criar usuários padrão se não existirem
php artisan tinker --execute="
if(!\\App\\Models\\User::where('email', 'admin@sistema.com')->exists()) {
    \\App\\Models\\User::create([
        'name' => 'Admin Sistema',
        'email' => 'admin@sistema.com', 
        'password' => \\Hash::make('admin123'),
        'role' => 'admin'
    ]);
    echo 'Admin criado!\n';
}

if(!\\App\\Models\\User::where('email', 'empresa@teste.com')->exists()) {
    \\App\\Models\\User::create([
        'name' => 'Empresa Teste',
        'email' => 'empresa@teste.com',
        'password' => \\Hash::make('123456'), 
        'role' => 'empresa'
    ]);
    echo 'Empresa criada!\n';
}

if(!\\App\\Models\\User::where('email', 'cliente@teste.com')->exists()) {
    \\App\\Models\\User::create([
        'name' => 'Cliente Teste',
        'email' => 'cliente@teste.com',
        'password' => \\Hash::make('123456'),
        'role' => 'cliente'  
    ]);
    echo 'Cliente criado!\n';
}
"