<?php
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'Method Not Allowed. Only POST requests are accepted.']);
  exit;
}
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/token_middleware.php';
$userData = TokenMiddleware::authenticate();
if (!$userData) {
  http_response_code(401);
  echo json_encode(['error' => 'Invalid or expired token']);
  exit;
}
$currentToken = null;
$headers = getallheaders();
if (isset($headers['Authorization'])) {
  $authHeader = $headers['Authorization'];
  if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    $currentToken = $matches[1];
  }
} elseif (isset($headers['X-Auth-Token'])) {
  $currentToken = $headers['X-Auth-Token'];
}
$tokenData = createAuthToken($userData['user_id']);
if ($currentToken) {
  revokeToken($currentToken);
}
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
$csrfToken = TokenMiddleware::generateCSRFToken();
echo json_encode([
  'success' => true,
  'message' => 'Token refreshed successfully',
  'token' => $tokenData['token'],
  'expires_at' => $tokenData['expires_at'],
  'expires_in' => $tokenData['expires_in'],
  'csrf_token' => $csrfToken
]);