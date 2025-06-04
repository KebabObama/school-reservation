<?php
header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'Method Not Allowed. Only POST requests are accepted.']);
  exit;
}

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/token_middleware.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Extract token from request
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

// Get JSON input for token
$input = json_decode(file_get_contents('php://input'), true);
if (!$token && isset($input['token'])) {
  $token = $input['token'];
}

// Enhanced logout with token revocation
logout($token);

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
  setcookie(session_name(), '', time() - 3600, '/');
}

echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
