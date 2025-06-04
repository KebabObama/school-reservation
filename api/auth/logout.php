<?php
session_start();

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  header('Content-Type: application/json');
  echo json_encode(['error' => 'Method Not Allowed. Only POST requests are accepted.']);
  exit;
}

// Destroy the session
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
  setcookie(session_name(), '', time() - 3600, '/');
}

header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
?>
