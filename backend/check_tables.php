<?php
$pdo = new PDO('sqlite:database/database.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
echo "Tabelas: " . implode(", ", $tables) . "\n\n";

// Checar se coupons existe
if (in_array('coupons', $tables)) {
    echo "coupons: OK\n";
    $info = $pdo->query("PRAGMA table_info(coupons)")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($info as $col) echo "  {$col['name']} ({$col['type']})\n";
} else {
    echo "coupons: NAO EXISTE\n";
}

echo "\n---\n";

// Campos da tabela empresas
$info = $pdo->query("PRAGMA table_info(empresas)")->fetchAll(PDO::FETCH_ASSOC);
echo "empresas colunas:\n";
foreach ($info as $col) echo "  {$col['name']} ({$col['type']})\n";

echo "\n---\n";
// Campos da tabela users
$info = $pdo->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_ASSOC);
echo "users colunas:\n";
foreach ($info as $col) echo "  {$col['name']}\n";
