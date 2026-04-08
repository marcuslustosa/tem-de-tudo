<?php
$pdo = new PDO('sqlite:' . __DIR__ . '/database/database.sqlite');
$cols = $pdo->query('PRAGMA table_info(qr_codes)')->fetchAll(PDO::FETCH_ASSOC);
echo "qr_codes columns:\n";
foreach ($cols as $c) {
    echo "  " . $c['name'] . " (" . $c['type'] . ")\n";
}
echo "\nSample rows:\n";
$rows = $pdo->query('SELECT * FROM qr_codes LIMIT 3')->fetchAll(PDO::FETCH_ASSOC);
print_r($rows);
