<?php
$db = new SQLite3('database/database.sqlite');
// Check empresas table schema
$res = $db->query("PRAGMA table_info(empresas)");
echo "Empresas columns:" . PHP_EOL;
while ($r = $res->fetchArray(SQLITE3_ASSOC)) echo "  {$r['name']} ({$r['type']})" . PHP_EOL;
echo PHP_EOL;

// Check if there's an empresa linked to user 3
$res2 = $db->query("SELECT * FROM empresas WHERE id <= 3");
while ($r = $res2->fetchArray(SQLITE3_ASSOC)) {
    echo "Empresa ID={$r['id']}: ";
    foreach ($r as $k => $v) if ($v !== null && $v !== '') echo "$k=$v, ";
    echo PHP_EOL;
}

// Check pontos table schema
$res3 = $db->query("PRAGMA table_info(pontos)");
echo PHP_EOL . "Pontos columns:" . PHP_EOL;
while ($r = $res3->fetchArray(SQLITE3_ASSOC)) echo "  {$r['name']} ({$r['type']})" . PHP_EOL;
