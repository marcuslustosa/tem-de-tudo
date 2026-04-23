#!/usr/bin/env php
<?php

declare(strict_types=1);

use Minishlink\WebPush\VAPID;

require __DIR__ . '/../vendor/autoload.php';

if (!getenv('OPENSSL_CONF')) {
    $opensslCandidates = [
        'C:\\laragon\\bin\\extras\\ssl\\openssl.cnf',
        'C:\\laragon\\bin\\git\\mingw64\\etc\\ssl\\openssl.cnf',
        '/etc/ssl/openssl.cnf',
        '/usr/lib/ssl/openssl.cnf',
    ];

    foreach ($opensslCandidates as $candidate) {
        if (is_file($candidate)) {
            putenv('OPENSSL_CONF=' . $candidate);
            $_ENV['OPENSSL_CONF'] = $candidate;
            break;
        }
    }
}

function randomBase64(int $length): string
{
    return base64_encode(random_bytes($length));
}

function randomHex(int $length): string
{
    return bin2hex(random_bytes($length));
}

$secrets = [
    'APP_KEY' => 'base64:' . randomBase64(32),
    'JWT_SECRET' => randomBase64(64),
    'SETUP_TOKEN' => randomHex(16),
];

$vapid = VAPID::createVapidKeys();
$secrets['VAPID_PUBLIC_KEY'] = $vapid['publicKey'];
$secrets['VAPID_PRIVATE_KEY'] = $vapid['privateKey'];
$secrets['VAPID_SUBJECT'] = 'mailto:no-reply@temdetudo.app';

echo "Generated production secrets (do not commit):\n\n";
foreach ($secrets as $key => $value) {
    echo $key . '=' . $value . PHP_EOL;
}

echo PHP_EOL;
echo "Railway CLI (after railway login):\n";
$parts = [];
foreach ($secrets as $key => $value) {
    $escaped = str_replace('"', '\\"', $value);
    $parts[] = $key . '=\"' . $escaped . '\"';
}
echo 'railway variables set ' . implode(' ', $parts) . PHP_EOL;
