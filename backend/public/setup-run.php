<?php
// Setup runner - execute Laravel migrations (and optional seed) via browser
// Security: protect with SETUP_TOKEN env and ?token=... query param.
header('Content-Type: text/plain; charset=utf-8');
set_time_limit(300);

$tokenEnv = getenv('SETUP_TOKEN');
$tokenReq = $_GET['token'] ?? '';

if (!$tokenEnv) {
    http_response_code(500);
    exit("SETUP_TOKEN not configured. Aborting.\n");
}

// Constant-time compare to avoid timing leaks
if (!hash_equals($tokenEnv, $tokenReq)) {
    http_response_code(403);
    exit("Forbidden\n");
}

$seed = ($_GET['seed'] ?? '') === '1';
$root = realpath(__DIR__ . '/..');
if (!$root) {
    http_response_code(500);
    exit("Could not resolve project root.\n");
}

chdir($root);

$commands = [
    'php artisan migrate --force --no-interaction',
];
if ($seed) {
    $commands[] = 'php artisan db:seed --force --no-interaction';
}

echo "Running setup at " . date('c') . "\n";

foreach ($commands as $cmd) {
    echo ">> $cmd\n";
    $descriptor = [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $proc = proc_open($cmd, $descriptor, $pipes);
    if (!is_resource($proc)) {
        echo "Failed to start command\n";
        http_response_code(500);
        exit;
    }
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($proc);

    if ($stdout) {
        echo $stdout . "\n";
    }
    if ($stderr) {
        echo "STDERR: " . $stderr . "\n";
    }
    if ($exitCode !== 0) {
        http_response_code(500);
        echo "Command failed with exit code $exitCode\n";
        exit;
    }
}

echo "Setup finished successfully.\n";
