<?php
$db = new PDO('sqlite:' . __DIR__ . '/database/database.sqlite');
$r = $db->query('PRAGMA table_info(pontos)');
echo "=== ESTRUTURA DA TABELA PONTOS ===\n";
foreach ($r->fetchAll(PDO::FETCH_ASSOC) as $col) {
    echo $col['cid'] . ' | ' . $col['name'] . ' | notnull=' . $col['notnull'] . ' | default=' . $col['dflt_value'] . "\n";
}
