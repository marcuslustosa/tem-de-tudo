<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

echo "Laravel is working!\n";
echo "Routes:\n";

// Test if routes are loaded
$routes = app('router')->getRoutes();
foreach ($routes as $route) {
    echo $route->methods()[0] . ' ' . $route->uri() . "\n";
}
