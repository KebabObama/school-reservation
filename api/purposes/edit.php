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

// Check permission to manage purposes (requires user management permission)
if (!canEditUsers($userId)) {
  http_response_code(403);
  echo json_encode(['error' => 'No permission to edit purposes']);
  exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (empty($data['id'])) {
  http_response_code(400);
  echo json_encode(['error' => 'Purpose ID is required']);
  exit;
}

try {
  // Check if purpose exists
  $stmt = $pdo->prepare("SELECT * FROM reservation_purposes WHERE id = ?");
  $stmt->execute([$data['id']]);
  $purpose = $stmt->fetch();

  if (!$purpose) {
    http_response_code(404);
    echo json_encode(['error' => 'Purpose not found']);
    exit;
  }

  // Build update query dynamically
  $updates = [];
  $params = [];

  if (isset($data['name']) && !empty(trim($data['name']))) {
    // Check if new name already exists (excluding current purpose)
    $checkStmt = $pdo->prepare("SELECT id FROM reservation_purposes WHERE name = ? AND id != ?");
    $checkStmt->execute([trim($data['name']), $data['id']]);
    if ($checkStmt->fetch()) {
      http_response_code(400);
      echo json_encode(['error' => 'A purpose with this name already exists']);
      exit;
    }

    $updates[] = "name = ?";
    $params[] = trim($data['name']);
  }

  if (isset($data['description'])) {
    $updates[] = "description = ?";
    $params[] = trim($data['description']);
  }

  if (isset($data['requires_approval'])) {
    $updates[] = "requires_approval = ?";
    $params[] = (bool)$data['requires_approval'];
  }

  if (empty($updates)) {
    http_response_code(400);
    echo json_encode(['error' => 'No fields to update']);
    exit;
  }

  // Add ID to params for WHERE clause
  $params[] = $data['id'];

  // Execute update
  $sql = "UPDATE reservation_purposes SET " . implode(', ', $updates) . " WHERE id = ?";
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);

  echo json_encode([
    'success' => true,
    'message' => 'Purpose updated successfully'
  ]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
