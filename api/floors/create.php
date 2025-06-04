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

// Check if user has permission to create floors
if (!canCreateFloors($_SESSION['user_id'])) {
  http_response_code(403);
  echo json_encode(['error' => 'You do not have permission to create floors']);
  exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
  http_response_code(400);
  echo json_encode(['error' => 'Invalid JSON input']);
  exit;
}

$errors = [];

// Sanitize and validate input
$building_id = !empty($input['building_id']) ? (int)$input['building_id'] : null;
$name = trim($input['name'] ?? '');
$description = trim($input['description'] ?? '');
$level_number = !empty($input['level_number']) ? (int)$input['level_number'] : null;

if (empty($building_id)) {
  $errors[] = 'Building is required.';
}

if (empty($name)) {
  $errors[] = 'Floor name is required.';
}

if (strlen($name) < 1) {
  $errors[] = 'Floor name must be at least 1 character long.';
}

if (strlen($name) > 50) {
  $errors[] = 'Floor name must be less than 50 characters.';
}

if (!empty($description) && strlen($description) > 1000) {
  $errors[] = 'Description must be less than 1000 characters.';
}

if (!empty($errors)) {
  http_response_code(400);
  echo json_encode(['errors' => $errors]);
  exit;
}

// Check if building exists
try {
  $stmt = $pdo->prepare("SELECT name FROM buildings WHERE id = ?");
  $stmt->execute([$building_id]);
  $building = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$building) {
    http_response_code(400);
    echo json_encode(['error' => 'Selected building does not exist']);
    exit;
  }
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Database error while checking building']);
  exit;
}

// Check if floor name already exists in this building
try {
  $stmt = $pdo->prepare("SELECT id FROM floors WHERE building_id = ? AND name = ?");
  $stmt->execute([$building_id, $name]);
  if ($stmt->fetch()) {
    http_response_code(400);
    echo json_encode(['error' => 'A floor with this name already exists in this building']);
    exit;
  }
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Database error while checking floor name']);
  exit;
}

// Insert floor
try {
  $stmt = $pdo->prepare("
    INSERT INTO floors (building_id, name, description, level_number)
    VALUES (?, ?, ?, ?)
  ");

  $stmt->execute([
    $building_id,
    $name,
    $description ?: null,
    $level_number
  ]);

  $newFloorId = $pdo->lastInsertId();

  http_response_code(201);
  echo json_encode([
    'message' => 'Floor created successfully',
    'floor_id' => $newFloorId,
    'floor_name' => $name,
    'building_name' => $building['name']
  ]);
  exit;
} catch (Exception $e) {
  http_response_code(500);
  error_log("Floor creation error: " . $e->getMessage());
  echo json_encode(['error' => 'Failed to create floor. Please try again.']);
  exit;
}
?>
