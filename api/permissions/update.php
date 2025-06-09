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
  echo json_encode(['error' => 'No permission to manage user permissions']);
  exit;
}
$data = json_decode(file_get_contents('php://input'), true);
if (empty($data['user_id'])) {
  http_response_code(400);
  echo json_encode(['error' => 'User ID is required']);
  exit;
}
if ($data['user_id'] == $userId) {
  http_response_code(400);
  echo json_encode(['error' => 'Cannot modify your own permissions']);
  exit;
}
try {
  $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
  $stmt->execute([$data['user_id']]);
  if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
    exit;
  }
  if (isset($data['bulk_action'])) {
    $allPermissions = getAllPermissionNames();
    $permissions = array_fill_keys($allPermissions, false);
    if ($data['bulk_action'] === 'grant_all') {
      $permissions = array_fill_keys($allPermissions, true);
    }
    $columns = implode(', ', $allPermissions);
    $placeholders = implode(', ', array_fill(0, count($allPermissions), '?'));
    $updateClauses = implode(', ', array_map(fn($col) => "$col = VALUES($col)", $allPermissions));
    $stmt = $pdo->prepare("
      INSERT INTO permissions (user_id, $columns)
      VALUES (?, $placeholders)
      ON DUPLICATE KEY UPDATE $updateClauses
    ");
    $params = array_merge([$data['user_id']], array_values($permissions));
    $stmt->execute($params);
    echo json_encode([
      'success' => true,
      'message' => 'Bulk permission update completed'
    ]);
    exit;
  }
  if (empty($data['permission']) || !isset($data['value'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Permission name and value are required']);
    exit;
  }
  $validPermissions = getAllPermissionNames();
  if (!in_array($data['permission'], $validPermissions)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid permission name']);
    exit;
  }
  $stmt = $pdo->prepare("SELECT user_id FROM permissions WHERE user_id = ?");
  $stmt->execute([$data['user_id']]);
  $permissionExists = $stmt->fetch();
  if ($permissionExists) {
    $sql = "UPDATE permissions SET {$data['permission']} = ? WHERE user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([(bool)$data['value'], $data['user_id']]);
  } else {
    $allPermissions = getAllPermissionNames();
    $permissions = array_fill_keys($allPermissions, false);
    $permissions[$data['permission']] = (bool)$data['value'];
    $columns = implode(', ', $allPermissions);
    $placeholders = implode(', ', array_fill(0, count($allPermissions), '?'));
    $stmt = $pdo->prepare("
      INSERT INTO permissions (user_id, $columns)
      VALUES (?, $placeholders)
    ");
    $params = array_merge([$data['user_id']], array_values($permissions));
    $stmt->execute($params);
  }
  echo json_encode([
    'success' => true,
    'message' => 'Permission updated successfully'
  ]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}