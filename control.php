<?php
require_once __DIR__ . '/pusher_settings.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$body   = file_get_contents('php://input');
$input  = json_decode($body, true);
$action = $input['action'] ?? '';

$allowed = ['up', 'down', 'left', 'right', 'bigger', 'smaller', 'reset'];

if (!in_array($action, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
    exit;
}

$ok = pusher_trigger('game-control', 'canvas-move', ['action' => $action]);

if ($ok) {
    echo json_encode(['ok' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Pusher trigger failed']);
}
