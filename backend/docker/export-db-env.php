<?php
// Helper: emit shell exports for DB_* variables based on DATABASE_URL
$url = getenv('DATABASE_URL');
if (!$url) {
    exit;
}

$parts = parse_url($url);
if ($parts === false) {
    fwrite(STDERR, "WARN: DATABASE_URL nao pode ser parseado\n");
    exit;
}

$scheme = $parts['scheme'] ?? 'pgsql';
$db = ltrim($parts['path'] ?? '', '/');
$query = [];
if (!empty($parts['query'])) {
    parse_str($parts['query'], $query);
}

$sslMode = $query['sslmode'] ?? 'require';
$connection = $scheme === 'mysql' ? 'mysql' : 'pgsql';

$exports = [
    'DB_CONNECTION' => $connection,
    'DB_URL'        => $url,
    'DB_HOST'       => $parts['host'] ?? '',
    'DB_PORT'       => $parts['port'] ?? ($scheme === 'mysql' ? '3306' : '5432'),
    'DB_DATABASE'   => $db,
    'DB_USERNAME'   => $parts['user'] ?? '',
    'DB_PASSWORD'   => $parts['pass'] ?? '',
    'DB_SSLMODE'    => $sslMode,
    'PGSSLMODE'     => $sslMode,
];

foreach ($exports as $key => $value) {
    if ($value === '' || $value === null) {
        continue;
    }
    $safe = escapeshellarg(urldecode($value));
    printf("export %s=%s\n", $key, $safe);
}
