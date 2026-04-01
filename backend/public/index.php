<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

// Evita vazamento de warnings no output HTTP (quebra JSON/headers em runtime).
ini_set('display_errors', '0');
error_reporting(E_ALL);

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/../vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
