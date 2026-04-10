<?php
define('PUSHER_APP_ID',  '2138886');
define('PUSHER_KEY',     '7c5006b837d47438d60c');
define('PUSHER_SECRET',  'e3545c2c444a22274c51');
define('PUSHER_CLUSTER', 'us2');

/**
 * Trigger a Pusher event using the Pusher HTTP API directly (no SDK / Composer needed).
 */
function pusher_trigger(string $channel, string $event, array $data): bool {
    $appId   = PUSHER_APP_ID;
    $key     = PUSHER_KEY;
    $secret  = PUSHER_SECRET;
    $cluster = PUSHER_CLUSTER;

    $body      = json_encode(['name' => $event, 'channel' => $channel, 'data' => json_encode($data)]);
    $timestamp = (string) time();
    $bodyMd5   = md5($body);
    $path      = "/apps/{$appId}/events";

    // Build canonical query string (must be sorted alphabetically)
    $params = [
        'auth_key'       => $key,
        'auth_timestamp' => $timestamp,
        'auth_version'   => '1.0',
        'body_md5'       => $bodyMd5,
    ];
    ksort($params);
    $queryString = http_build_query($params);

    // Sign
    $stringToSign  = "POST\n{$path}\n{$queryString}";
    $authSignature = hash_hmac('sha256', $stringToSign, $secret);

    $url = "https://api-{$cluster}.pusher.com{$path}?{$queryString}&auth_signature={$authSignature}";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
    ]);
    curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $code >= 200 && $code < 300;
}
