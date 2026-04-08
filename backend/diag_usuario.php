<?php
// Verifica o usuário diretamente no banco e testa o login
$db = new PDO('sqlite:' . __DIR__ . '/database/database.sqlite');

// Busca o usuário (case-insensitive)
$stmt = $db->prepare("SELECT id, name, email, perfil, status, password, created_at FROM users WHERE LOWER(email) = LOWER(?) ORDER BY id DESC LIMIT 1");
$stmt->execute(['MARCUSLUSTOSA90@GMAIL.COM']);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "❌ USUÁRIO NÃO ENCONTRADO NO BANCO\n";
    echo "Verificando os últimos 5 cadastros:\n";
    $stmt2 = $db->query("SELECT id, name, email, perfil, status, created_at FROM users ORDER BY id DESC LIMIT 5");
    foreach ($stmt2->fetchAll(PDO::FETCH_ASSOC) as $u) {
        echo "  ID={$u['id']} | {$u['email']} | {$u['perfil']} | {$u['status']} | {$u['created_at']}\n";
    }
    exit;
}

echo "✅ USUÁRIO ENCONTRADO:\n";
echo "  ID: {$user['id']}\n";
echo "  Email: {$user['email']}\n";
echo "  Perfil: {$user['perfil']}\n";
echo "  Status: {$user['status']}\n";
echo "  Criado: {$user['created_at']}\n";
echo "  Hash da senha: " . substr($user['password'], 0, 30) . "...\n\n";

// Testa se as senhas batem
$senhas = ['123456', '1234567', 'senha123'];
foreach ($senhas as $s) {
    $ok = password_verify($s, $user['password']);
    echo ($ok ? "✅" : "❌") . " password_verify('$s') = " . ($ok ? "BATE" : "não bate") . "\n";
}

// Quantos hashes tem (double hash?)
$hash = $user['password'];
echo "\nComprimento do hash: " . strlen($hash) . " chars\n";
echo "Começa com \$2y\$: " . (str_starts_with($hash, '$2y$') ? 'SIM (bcrypt correto)' : 'NÃO - CORROMPIDO') . "\n";

// Verifica se é double-hash: tenta verificar o hash de 123456 contra o hash armazenado
$hash123456 = password_hash('123456', PASSWORD_BCRYPT);
echo "\nTeste double-hash: " . (password_verify($hash123456, $hash) ? "❌ É DOUBLE HASH (bug)" : "✅ Não é double hash") . "\n";
