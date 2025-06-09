<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['error' => 'Not authenticated']);
  exit;
}
require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/permissions.php';
$userId = $_SESSION['user_id'];
if (!canEditUsers($userId)) {
  http_response_code(403);
  echo json_encode(['error' => 'No permission to verify users']);
  exit;
}
$data = json_decode(file_get_contents('php://input'), true);
if (empty($data['user_id'])) {
  http_response_code(400);
  echo json_encode(['error' => 'User ID is required']);
  exit;
}
if (!isset($data['verified'])) {
  http_response_code(400);
  echo json_encode(['error' => 'Verification status is required']);
  exit;
}
$targetUserId = (int)$data['user_id'];
$verified = (bool)$data['verified'];
try {
  $stmt = $pdo->prepare("SELECT id, email, name, surname, is_verified FROM users WHERE id = ?");
  $stmt->execute([$targetUserId]);
  $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$targetUser) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
    exit;
  }
  if ($targetUserId === $userId) {
    http_response_code(400);
    echo json_encode(['error' => 'Cannot change your own verification status']);
    exit;
  }
  $stmt = $pdo->prepare("UPDATE users SET is_verified = ? WHERE id = ?");
  $stmt->execute([$verified, $targetUserId]);
  if ($stmt->rowCount() > 0) {
    $action = $verified ? 'verified' : 'unverified';
    echo json_encode([
      'success' => true,
      'message' => "User {$targetUser['name']} {$targetUser['surname']} has been {$action}",
      'user_id' => $targetUserId,
      'verified' => $verified
    ]);
  } else {
    echo json_encode([
      'success' => false,
      'error' => 'No changes made - user may already have the requested verification status'
    ]);
  }
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>