<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['error' => 'Not authenticated']);
  exit;
}
require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/permissions.php';
if (!canEditFloors($_SESSION['user_id'])) {
  http_response_code(403);
  echo json_encode(['error' => 'You do not have permission to edit floors']);
  exit;
}
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['id'])) {
  http_response_code(400);
  echo json_encode(['error' => 'Floor ID is required']);
  exit;
}
try {
  $stmt = $pdo->prepare("SELECT * FROM floors WHERE id = ?");
  $stmt->execute([$data['id']]);
  $floor = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$floor) {
    throw new Exception('Floor not found');
  }
  $fields = [
    'building_id',
    'name',
    'description',
    'level_number'
  ];
  $updates = [];
  $params = [];
  foreach ($fields as $field) {
    if (array_key_exists($field, $data)) {
      if ($field === 'building_id') {
        $building_id = (int)$data[$field];
        $checkStmt = $pdo->prepare("SELECT name FROM buildings WHERE id = ?");
        $checkStmt->execute([$building_id]);
        if (!$checkStmt->fetch()) {
          http_response_code(400);
          echo json_encode(['error' => 'Selected building does not exist']);
          exit;
        }
        $updates[] = "$field = :$field";
        $params[":$field"] = $building_id;
      } elseif ($field === 'name') {
        $name = trim($data[$field]);
        if (empty($name)) {
          http_response_code(400);
          echo json_encode(['error' => 'Floor name is required']);
          exit;
        }
        if (strlen($name) > 50) {
          http_response_code(400);
          echo json_encode(['error' => 'Floor name must be less than 50 characters']);
          exit;
        }
        $building_id = $params[':building_id'] ?? $floor['building_id'];
        $checkStmt = $pdo->prepare("SELECT id FROM floors WHERE building_id = ? AND name = ? AND id != ?");
        $checkStmt->execute([$building_id, $name, $data['id']]);
        if ($checkStmt->fetch()) {
          http_response_code(400);
          echo json_encode(['error' => 'A floor with this name already exists in this building']);
          exit;
        }
        $updates[] = "$field = :$field";
        $params[":$field"] = $name;
      } else {
        $updates[] = "$field = :$field";
        $params[":$field"] = $data[$field] ?: null;
      }
    }
  }
  if (empty($updates)) {
    throw new Exception('No fields to update');
  }
  $params[':id'] = $data['id'];
  $sql = "UPDATE floors SET " . implode(', ', $updates) . ", updated_at = CURRENT_TIMESTAMP WHERE id = :id";
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  echo json_encode(['success' => true]);
} catch (Exception $e) {
  http_response_code(400);
  echo json_encode(['error' => $e->getMessage()]);
}
?>