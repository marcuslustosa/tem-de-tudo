<?php

header('Content-Type: application/json; charset=UTF-8');

echo json_encode([
    'status' => 'ok',
    'service' => 'tem-de-tudo',
    'timestamp' => gmdate('c'),
]);
