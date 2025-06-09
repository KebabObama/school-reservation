<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['error' => 'Not authenticated']);
  exit;
}
require_once __DIR__ . '/../../lib/db.php';
$userId = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$targetUserId = $userId;
try {
  $stmt = $pdo->prepare("SELECT id, email, name, surname FROM users WHERE id = ?");
  $stmt->execute([$targetUserId]);
  $targetUser = $stmt->fetch();
  if (!$targetUser) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
    exit;
  }
  $updates = [];
  $params = [];
  if (isset($data['name']) && !empty(trim($data['name']))) {
    $updates[] = "name = ?";
    $params[] = trim($data['name']);
  }
  if (isset($data['surname']) && !empty(trim($data['surname']))) {
    $updates[] = "surname = ?";
    $params[] = trim($data['surname']);
  }
  if (isset($data['email']) && !empty(trim($data['email']))) {
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
      http_response_code(400);
      echo json_encode(['error' => 'Invalid email format']);
      exit;
    }
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $checkStmt->execute([trim($data['email']), $targetUserId]);
    if ($checkStmt->fetch()) {
      http_response_code(400);
      echo json_encode(['error' => 'Email is already taken by another user']);
      exit;
    }
    $updates[] = "email = ?";
    $params[] = trim($data['email']);
  }
  if (isset($data['password']) && !empty($data['password'])) {
    if (!isset($data['current_password']) || empty($data['current_password'])) {
      http_response_code(400);
      echo json_encode(['error' => 'Current password is required when changing your password']);
      exit;
    }
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $currentHash = $stmt->fetchColumn();
    if (!password_verify($data['current_password'], $currentHash)) {
      http_response_code(400);
      echo json_encode(['error' => 'Current password is incorrect']);
      exit;
    }
    if (strlen($data['password']) < 6) {
      http_response_code(400);
      echo json_encode(['error' => 'Password must be at least 6 characters long']);
      exit;
    }
    $updates[] = "password_hash = ?";
    $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
  }
  if (empty($updates)) {
    http_response_code(400);
    echo json_encode(['error' => 'No fields to update']);
    exit;
  }
  $params[] = $targetUserId;
  $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  if (isset($data['name'])) {
    $_SESSION['user_name'] = trim($data['name']);
  }
  if (isset($data['surname'])) {
    $_SESSION['user_surname'] = trim($data['surname']);
  }
  if (isset($data['email'])) {
    $_SESSION['user_email'] = trim($data['email']);
  }
  echo json_encode([
    'success' => true,
    'message' => 'Profile updated successfully'
  ]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}