<?php
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'Method Not Allowed. Only POST requests are accepted.']);
  exit;
}
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/token_middleware.php';
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
  $input = $_POST;
}
if (empty($input['email']) || empty($input['password'])) {
  http_response_code(400);
  echo json_encode(['error' => 'Email and password are required']);
  exit;
}
$email = trim($input['email']);
$password = $input['password'];
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!TokenMiddleware::checkRateLimit($clientIP, 5, 15)) {
  $remaining = TokenMiddleware::getRemainingAttempts($clientIP, 5, 15);
  http_response_code(429);
  echo json_encode([
    'error' => 'Too many login attempts. Please try again later.',
    'remaining_attempts' => $remaining,
    'retry_after' => 15 * 60
  ]);
  exit;
}
if (!TokenMiddleware::checkRateLimit($email, 3, 15)) {
  http_response_code(429);
  echo json_encode([
    'error' => 'Too many login attempts for this email. Please try again later.',
    'retry_after' => 15 * 60
  ]);
  exit;
}
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
$result = loginWithToken($email, $password);
if ($result['success']) {
  TokenMiddleware::clearRateLimit($clientIP);
  TokenMiddleware::clearRateLimit($email);
  $csrfToken = TokenMiddleware::generateCSRFToken();
  echo json_encode([
    'success' => true,
    'message' => 'Login successful',
    'user_id' => $result['user_id'],
    'token' => $result['token'],
    'expires_at' => $result['expires_at'],
    'expires_in' => $result['expires_in'],
    'csrf_token' => $csrfToken
  ]);
} else {
  http_response_code(401);
  echo json_encode([
    'success' => false,
    'error' => $result['error'],
    'remaining_attempts' => TokenMiddleware::getRemainingAttempts($clientIP, 5, 15)
  ]);
}