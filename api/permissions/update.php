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

// Check permission to manage users
if (!canEditUsers($userId)) {
  http_response_code(403);
  echo json_encode(['error' => 'No permission to manage user permissions']);
  exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (empty($data['user_id'])) {
  http_response_code(400);
  echo json_encode(['error' => 'User ID is required']);
  exit;
}

// Prevent users from modifying their own permissions
if ($data['user_id'] == $userId) {
  http_response_code(400);
  echo json_encode(['error' => 'Cannot modify your own permissions']);
  exit;
}

try {
  // Check if target user exists
  $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
  $stmt->execute([$data['user_id']]);
  if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
    exit;
  }

  // Handle bulk actions
  if (isset($data['bulk_action'])) {
    // Get all permission names
    $allPermissions = getAllPermissionNames();

    // Define default permissions (all false)
    $permissions = array_fill_keys($allPermissions, false);

    if ($data['bulk_action'] === 'grant_all') {
      $permissions = array_fill_keys($allPermissions, true);
    }
    // For 'revoke_all', permissions are already set to false

    // Build upsert query
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

  // Handle individual permission update
  if (empty($data['permission']) || !isset($data['value'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Permission name and value are required']);
    exit;
  }

  // Validate permission name
  $validPermissions = getAllPermissionNames();

  if (!in_array($data['permission'], $validPermissions)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid permission name']);
    exit;
  }

  // Check if permissions record exists
  $stmt = $pdo->prepare("SELECT user_id FROM permissions WHERE user_id = ?");
  $stmt->execute([$data['user_id']]);
  $permissionExists = $stmt->fetch();

  if ($permissionExists) {
    // Update existing permission
    $sql = "UPDATE permissions SET {$data['permission']} = ? WHERE user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([(bool)$data['value'], $data['user_id']]);
  } else {
    // Create new permissions record with default values
    $allPermissions = getAllPermissionNames();
    $permissions = array_fill_keys($allPermissions, false);

    // Set the specific permission
    $permissions[$data['permission']] = (bool)$data['value'];

    // Build insert query
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
