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
if (!canEditBuildings($_SESSION['user_id'])) {
  http_response_code(403);
  echo json_encode(['error' => 'You do not have permission to edit buildings']);
  exit;
}
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['id'])) {
  http_response_code(400);
  echo json_encode(['error' => 'Building ID is required']);
  exit;
}
try {
  $stmt = $pdo->prepare("SELECT * FROM buildings WHERE id = ?");
  $stmt->execute([$data['id']]);
  $building = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$building) {
    throw new Exception('Building not found');
  }
  $fields = [
    'name',
    'description',
    'address'
  ];
  $updates = [];
  $params = [];
  foreach ($fields as $field) {
    if (array_key_exists($field, $data)) {
      if ($field === 'name') {
        $name = trim($data[$field]);
        if (empty($name)) {
          http_response_code(400);
          echo json_encode(['error' => 'Building name is required']);
          exit;
        }
        if (strlen($name) < 2) {
          http_response_code(400);
          echo json_encode(['error' => 'Building name must be at least 2 characters long']);
          exit;
        }
        if (strlen($name) > 100) {
          http_response_code(400);
          echo json_encode(['error' => 'Building name must be less than 100 characters']);
          exit;
        }
        $checkStmt = $pdo->prepare("SELECT id FROM buildings WHERE name = ? AND id != ?");
        $checkStmt->execute([$name, $data['id']]);
        if ($checkStmt->fetch()) {
          http_response_code(400);
          echo json_encode(['error' => 'A building with this name already exists']);
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
  $sql = "UPDATE buildings SET " . implode(', ', $updates) . ", updated_at = CURRENT_TIMESTAMP WHERE id = :id";
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  echo json_encode(['success' => true]);
} catch (Exception $e) {
  http_response_code(400);
  echo json_encode(['error' => $e->getMessage()]);
}
?>