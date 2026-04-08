<?php
$url = 'http://127.0.0.1:8099/api/auth/register';
$email = 'teste_' . time() . '@teste.com';
$data = json_encode([
    'name' => 'Teste',
    'email' => $email,
    'password' => 'senha123',
    'password_confirmation' => 'senha123',
    'perfil' => 'cliente',
    'terms' => true,
]);
$opts = ['http' => [
    'method' => 'POST',
    'header' => "Content-Type: application/json\r\nAccept: application/json\r\n",
    'content' => $data,
    'ignore_errors' => true,
]];
$ctx = stream_context_create($opts);
$res = file_get_contents($url, false, $ctx);
echo "--- REQUEST ---\n";
echo $data . "\n\n";
echo "--- RESPONSE ---\n";
echo $res . "\n";
$j = json_decode($res, true);
if (isset($j['errors'])) {
    echo "\n--- ERROS DE VALIDAÇÃO ---\n";
    print_r($j['errors']);
}
