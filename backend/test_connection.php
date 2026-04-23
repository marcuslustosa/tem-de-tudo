<?php
$ch = curl_init('http://127.0.0.1:8099/api/auth/login');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_POST => 1,
    CURLOPT_POSTFIELDS => json_encode([
        'email' => 'admin@temdetudo.com',
        'password' => 'senha123'
    ]),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json'
    ],
    CURLOPT_TIMEOUT => 10
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "=== TESTE DE CONEXÃO COM API ===\n\n";
echo "URL: http://127.0.0.1:8099/api/auth/login\n";
echo "HTTP Code: $httpCode\n";

if ($error) {
    echo "❌ CURL Error: $error\n";
    echo "\n⚠️  PROBLEMA: Servidor PHP não está respondendo!\n";
    echo "Solução: Execute em outro terminal:\n";
    echo "  cd C:\\Users\\X472795\\Desktop\\tem-de-tudo\\tem-de-tudo\\backend\n";
    echo "  php artisan serve --port=8099\n";
} else {
    echo "Resposta:\n";
    print_r(json_decode($response, true));
}
