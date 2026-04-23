#!/usr/bin/env php
<?php

/**
 * Script para rotacionar segredos de produção
 * 
 * ATENÇÃO: Execute este script antes do primeiro deploy em produção
 * 
 * Uso: php rotate_secrets.php
 */

echo "🔐 ROTAÇÃO DE SEGREDOS DE PRODUÇÃO\n";
echo "═══════════════════════════════════\n\n";

$envFile = __DIR__ . '/.env';

if (!file_exists($envFile)) {
    die("❌ Arquivo .env não encontrado!\n");
}

$envContent = file_get_contents($envFile);

// 1. Gera novo JWT_SECRET
echo "1️⃣ Gerando novo JWT_SECRET...\n";
$jwtSecret = base64_encode(random_bytes(64));
$envContent = preg_replace('/JWT_SECRET=.*/', "JWT_SECRET={$jwtSecret}", $envContent);
echo "   ✅ JWT_SECRET rotacionado\n\n";

// 2. Gera novo APP_KEY se não existir
echo "2️⃣ Verificando APP_KEY...\n";
if (preg_match('/APP_KEY=\s*$/', $envContent)) {
    echo "   ⚠️  APP_KEY vazio! Gerando...\n";
    $appKey = 'base64:' . base64_encode(random_bytes(32));
    $envContent = preg_replace('/APP_KEY=.*/', "APP_KEY={$appKey}", $envContent);
    echo "   ✅ APP_KEY gerado\n\n";
} else {
    echo "   ✅ APP_KEY já existe\n\n";
}

// 3. Gera VAPID keys para Web Push (se não existir)
echo "3️⃣ Verificando VAPID_PRIVATE_KEY...\n";
if (preg_match('/VAPID_PRIVATE_KEY=\s*$/', $envContent)) {
    echo "   ⚠️  VAPID_PRIVATE_KEY vazio!\n";
    echo "   ℹ️  Execute: php artisan webpush:vapid\n\n";
} else {
    echo "   ✅ VAPID_PRIVATE_KEY já existe\n\n";
}

// 4. Backup do .env antigo
$backupFile = $envFile . '.backup.' . date('Y-m-d_His');
copy($envFile, $backupFile);
echo "📦 Backup criado: " . basename($backupFile) . "\n\n";

// 5. Salva novo .env
file_put_contents($envFile, $envContent);

echo "✅ SEGREDOS ROTACIONADOS COM SUCESSO!\n\n";
echo "⚠️  IMPORTANTE:\n";
echo "   1. Configure variáveis de produção no Railway/Render:\n";
echo "      • JWT_SECRET={$jwtSecret}\n";
echo "      • APP_DEBUG=false\n";
echo "      • APP_ENV=production\n\n";
echo "   2. NUNCA commite o arquivo .env para o Git!\n";
echo "   3. Configure MercadoPago, Firebase, OpenAI manualmente\n\n";
echo "   4. Adicione ao .gitignore:\n";
echo "      .env\n";
echo "      .env.backup.*\n\n";
