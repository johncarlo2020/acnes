<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$file = __DIR__ . '/leaderboard.json';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }

    $name  = trim(substr(strip_tags((string)($input['name'] ?? '')), 0, 50));
    $score = filter_var($input['score'] ?? 0, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 0, 'max_range' => 999999]
    ]);

    if ($name === '' || $score === false) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid data']);
        exit;
    }

    $fp = fopen($file, 'c+');
    if (!$fp) {
        http_response_code(500);
        echo json_encode(['error' => 'Could not open leaderboard']);
        exit;
    }

    flock($fp, LOCK_EX);
    $contents = stream_get_contents($fp);
    $entries  = ($contents !== '' && $contents !== false) ? json_decode($contents, true) : [];
    if (!is_array($entries)) $entries = [];

    $entries[] = [
        'name'  => $name,
        'score' => $score,
        'time'  => date('c'),
    ];

    usort($entries, fn($a, $b) => $b['score'] - $a['score']);
    $entries = array_slice($entries, 0, 100);

    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($entries));
    flock($fp, LOCK_UN);
    fclose($fp);

    // Fire Pusher event so the game screen auto-switches to leaderboard
    require_once __DIR__ . '/pusher_settings.php';
    pusher_trigger('game-control', 'player-joined', [
        'entries' => array_slice($entries, 0, 10),
    ]);

    echo json_encode(['ok' => true]);

} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!file_exists($file)) {
        echo json_encode([]);
        exit;
    }
    $entries = json_decode(file_get_contents($file), true);
    echo json_encode(is_array($entries) ? $entries : []);

} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
