<?php
require_once __DIR__ . '/config.php';

// Simple proxy to the configured TOKEN_SERVER_URL to avoid CORS issues.
// Routes:
// ?type=health  -> GET TOKEN_SERVER_URL/health
// ?type=rtm     -> POST TOKEN_SERVER_URL/agora/rtm-token
// ?type=rtc     -> POST TOKEN_SERVER_URL/agora/rtc-token

header('Content-Type: application/json');

$type = $_GET['type'] ?? '';

function proxy_get($url) {
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_TIMEOUT, 10);
  $resp = curl_exec($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  http_response_code($code ?: 502);
  echo $resp ?: json_encode(['ok'=>false,'error'=>'proxy error']);
}
function proxy_post_json($url, $payload) {
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_TIMEOUT, 10);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
  $resp = curl_exec($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  http_response_code($code ?: 502);
  echo $resp ?: json_encode(['ok'=>false,'error'=>'proxy error']);
}

if ($type === 'health') {
  proxy_get(rtrim($TOKEN_SERVER_URL,'/') . '/health');
  exit;
}
if ($type === 'rtm') {
  $raw = file_get_contents('php://input');
  $payload = json_decode($raw ?: '{}', true);
  proxy_post_json(rtrim($TOKEN_SERVER_URL,'/') . '/agora/rtm-token', $payload);
  exit;
}
if ($type === 'rtc') {
  $raw = file_get_contents('php://input');
  $payload = json_decode($raw ?: '{}', true);
  proxy_post_json(rtrim($TOKEN_SERVER_URL,'/') . '/agora/rtc-token', $payload);
  exit;
}

http_response_code(400);
echo json_encode(['ok'=>false,'error'=>'invalid proxy type']);