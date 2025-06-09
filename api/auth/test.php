<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../lib/token_middleware.php';
$userData = TokenMiddleware::authenticate();
if ($userData) {
  echo json_encode([
    'success' => true,
    'message' => 'Authentication successful',
    'user' => [
      'id' => $userData['user_id'],
      'email' => $userData['email'],
      'name' => $userData['name'],
      'surname' => $userData['surname'],
      'is_verified' => $userData['is_verified']
    ],
    'auth_method' => isset($_SESSION['user_id']) ? 'session' : 'token'
  ]);
} else {
  http_response_code(401);
  echo json_encode([
    'success' => false,
    'message' => 'Authentication failed',
    'error' => 'No valid session or token found'
  ]);
}