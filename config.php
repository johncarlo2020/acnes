<?php
require_once __DIR__ . '/pusher_settings.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

echo json_encode([
    'key'     => PUSHER_KEY,
    'cluster' => PUSHER_CLUSTER,
]);
