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
if (!canEditRooms($userId)) {
  http_response_code(403);
  echo json_encode(['error' => 'No permission to edit rooms']);
  exit;
}
$data = json_decode(file_get_contents('php://input'), true);
if (empty($data['id'])) {
  http_response_code(400);
  echo json_encode(['error' => 'Room ID is required']);
  exit;
}
try {
  $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
  $stmt->execute([$data['id']]);
  $room = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$room) {
    throw new Exception('Room not found');
  }
  $fields = [
    'name',
    'room_type_id',
    'floor_id',
    'capacity',
    'equipment',
    'description',
    'image_url',
    'is_active',
    'features',
    'availability_schedule'
  ];
  $updates = [];
  $params = [];
  if (array_key_exists('floor_id', $data) && $data['floor_id']) {
    try {
      $stmt = $pdo->prepare("SELECT building_id FROM floors WHERE id = ?");
      $stmt->execute([$data['floor_id']]);
      $floor = $stmt->fetch(PDO::FETCH_ASSOC);
      if ($floor) {
        $data['building_id'] = (int)$floor['building_id'];
        $fields[] = 'building_id';
      }
    } catch (Exception $e) {
      throw new Exception('Invalid floor selected');
    }
  }
  foreach ($fields as $field) {
    if (array_key_exists($field, $data)) {
      if (in_array($field, ['features', 'availability_schedule']) && $data[$field] !== null) {
        $updates[] = "$field = :$field";
        $params[":$field"] = json_encode($data[$field]);
      } else {
        $updates[] = "$field = :$field";
        $params[":$field"] = $data[$field];
      }
    }
  }
  if (empty($updates)) {
    throw new Exception('No fields to update');
  }
  $params[':id'] = $data['id'];
  $sql = "UPDATE rooms SET " . implode(', ', $updates) . ", updated_at = CURRENT_TIMESTAMP WHERE id = :id";
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  echo json_encode(['success' => true]);
} catch (Exception $e) {
  http_response_code(400);
  echo json_encode(['error' => $e->getMessage()]);
}