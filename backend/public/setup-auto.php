<?php

http_response_code(403);
header('Content-Type: application/json; charset=UTF-8');

echo json_encode([
    'success' => false,
    'message' => 'Endpoint desabilitado em producao.',
]);
