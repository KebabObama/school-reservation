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
if (!hasPermission($userId, 'rooms_create')) {
  http_response_code(403);
  echo json_encode(['error' => 'No permission to create room types']);
  exit;
}
$data = json_decode(file_get_contents('php://input'), true);
if (empty($data['name'])) {
  http_response_code(400);
  echo json_encode(['error' => 'Room type name is required']);
  exit;
}
if (empty($data['description'])) {
  http_response_code(400);
  echo json_encode(['error' => 'Description is required']);
  exit;
}
if (empty($data['color'])) {
  http_response_code(400);
  echo json_encode(['error' => 'Color is required']);
  exit;
}
if (strlen(trim($data['name'])) < 2) {
  http_response_code(400);
  echo json_encode(['error' => 'Room type name must be at least 2 characters long']);
  exit;
}
if (strlen(trim($data['description'])) < 10) {
  http_response_code(400);
  echo json_encode(['error' => 'Description must be at least 10 characters long']);
  exit;
}
if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $data['color'])) {
  http_response_code(400);
  echo json_encode(['error' => 'Invalid color format. Please use hex format (#RRGGBB)']);
  exit;
}
try {
  $stmt = $pdo->prepare("SELECT id FROM room_types WHERE name = ?");
  $stmt->execute([trim($data['name'])]);
  if ($stmt->fetch()) {
    http_response_code(400);
    echo json_encode(['error' => 'A room type with this name already exists']);
    exit;
  }
  $stmt = $pdo->prepare("
        INSERT INTO room_types (name, description, color) 
        VALUES (?, ?, ?)
    ");
  $stmt->execute([
    trim($data['name']),
    trim($data['description']),
    $data['color']
  ]);
  $roomTypeId = $pdo->lastInsertId();
  echo json_encode([
    'success' => true,
    'room_type_id' => $roomTypeId,
    'message' => 'Room type created successfully'
  ]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}