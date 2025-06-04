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

// Check permission to edit rooms (room types are part of room management)
if (!canEditRooms($userId)) {
  http_response_code(403);
  echo json_encode(['error' => 'No permission to edit room types']);
  exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (empty($data['id'])) {
  http_response_code(400);
  echo json_encode(['error' => 'Room type ID is required']);
  exit;
}

try {
  // Check if room type exists
  $stmt = $pdo->prepare("SELECT * FROM room_types WHERE id = ?");
  $stmt->execute([$data['id']]);
  $roomType = $stmt->fetch();

  if (!$roomType) {
    http_response_code(404);
    echo json_encode(['error' => 'Room type not found']);
    exit;
  }

  // Build update query dynamically
  $updates = [];
  $params = [];

  if (isset($data['name']) && !empty(trim($data['name']))) {
    // Validate name length
    if (strlen(trim($data['name'])) < 2) {
      http_response_code(400);
      echo json_encode(['error' => 'Room type name must be at least 2 characters long']);
      exit;
    }

    // Check if new name already exists (excluding current room type)
    $checkStmt = $pdo->prepare("SELECT id FROM room_types WHERE name = ? AND id != ?");
    $checkStmt->execute([trim($data['name']), $data['id']]);
    if ($checkStmt->fetch()) {
      http_response_code(400);
      echo json_encode(['error' => 'A room type with this name already exists']);
      exit;
    }

    $updates[] = "name = ?";
    $params[] = trim($data['name']);
  }

  if (isset($data['description']) && !empty(trim($data['description']))) {
    // Validate description length
    if (strlen(trim($data['description'])) < 10) {
      http_response_code(400);
      echo json_encode(['error' => 'Description must be at least 10 characters long']);
      exit;
    }

    $updates[] = "description = ?";
    $params[] = trim($data['description']);
  }

  if (isset($data['color']) && !empty($data['color'])) {
    // Validate color format (hex color)
    if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $data['color'])) {
      http_response_code(400);
      echo json_encode(['error' => 'Invalid color format. Please use hex format (#RRGGBB)']);
      exit;
    }

    $updates[] = "color = ?";
    $params[] = $data['color'];
  }

  if (empty($updates)) {
    http_response_code(400);
    echo json_encode(['error' => 'No fields to update']);
    exit;
  }

  // Add ID to params for WHERE clause
  $params[] = $data['id'];

  // Execute update
  $sql = "UPDATE room_types SET " . implode(', ', $updates) . " WHERE id = ?";
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);

  echo json_encode([
    'success' => true,
    'message' => 'Room type updated successfully'
  ]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
