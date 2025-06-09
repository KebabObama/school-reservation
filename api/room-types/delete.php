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
if (!canDeleteRooms($userId)) {
  http_response_code(403);
  echo json_encode(['error' => 'No permission to delete room types']);
  exit;
}
$data = json_decode(file_get_contents('php://input'), true);
if (empty($data['id'])) {
  http_response_code(400);
  echo json_encode(['error' => 'Room type ID is required']);
  exit;
}
try {
  $stmt = $pdo->prepare("SELECT * FROM room_types WHERE id = ?");
  $stmt->execute([$data['id']]);
  $roomType = $stmt->fetch();
  if (!$roomType) {
    http_response_code(404);
    echo json_encode(['error' => 'Room type not found']);
    exit;
  }
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM rooms WHERE room_type_id = ?");
  $stmt->execute([$data['id']]);
  $roomCount = $stmt->fetchColumn();
  if ($roomCount > 0) {
    http_response_code(400);
    echo json_encode([
      'error' => "Cannot delete room type. It is currently used by {$roomCount} room(s). Please update those rooms first or set them to a different room type."
    ]);
    exit;
  }
  $stmt = $pdo->prepare("DELETE FROM room_types WHERE id = ?");
  $stmt->execute([$data['id']]);
  echo json_encode([
    'success' => true,
    'message' => 'Room type deleted successfully'
  ]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}