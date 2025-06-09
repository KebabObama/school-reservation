<?php
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'Method Not Allowed. Only POST requests are accepted.']);
  exit;
}
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/token_middleware.php';
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
$token = null;
$headers = getallheaders();
if (isset($headers['Authorization'])) {
  $authHeader = $headers['Authorization'];
  if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    $token = $matches[1];
  }
} elseif (isset($headers['X-Auth-Token'])) {
  $token = $headers['X-Auth-Token'];
}
$input = json_decode(file_get_contents('php://input'), true);
if (!$token && isset($input['token'])) {
  $token = $input['token'];
}
logout($token);
if (isset($_COOKIE[session_name()])) {
  setcookie(session_name(), '', time() - 3600, '/');
}
echo json_encode(['success' => true, 'message' => 'Logged out successfully']);