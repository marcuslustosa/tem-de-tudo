<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Public path: " . public_path() . "\n";
echo "Index.html exists: " . (file_exists(public_path('index.html')) ? 'YES' : 'NO') . "\n";
echo "Index.html path: " . public_path('index.html') . "\n";
echo "File size: " . (file_exists(public_path('index.html')) ? filesize(public_path('index.html')) : 'N/A') . " bytes\n";
