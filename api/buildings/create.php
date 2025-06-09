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
if (!hasPermission($_SESSION['user_id'], 'buildings_create')) {
  http_response_code(403);
  echo json_encode(['error' => 'You do not have permission to create buildings']);
  exit;
}
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
  http_response_code(400);
  echo json_encode(['error' => 'Invalid JSON input']);
  exit;
}
$errors = [];
$name = trim($input['name'] ?? '');
$description = trim($input['description'] ?? '');
$address = trim($input['address'] ?? '');
if (empty($name)) {
  $errors[] = 'Building name is required.';
}
if (strlen($name) < 2) {
  $errors[] = 'Building name must be at least 2 characters long.';
}
if (strlen($name) > 100) {
  $errors[] = 'Building name must be less than 100 characters.';
}
if (!empty($description) && strlen($description) > 1000) {
  $errors[] = 'Description must be less than 1000 characters.';
}
if (!empty($address) && strlen($address) > 255) {
  $errors[] = 'Address must be less than 255 characters.';
}
if (!empty($errors)) {
  http_response_code(400);
  echo json_encode(['errors' => $errors]);
  exit;
}
try {
  $stmt = $pdo->prepare("SELECT id FROM buildings WHERE name = ?");
  $stmt->execute([$name]);
  if ($stmt->fetch()) {
    http_response_code(400);
    echo json_encode(['error' => 'A building with this name already exists']);
    exit;
  }
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Database error while checking building name']);
  exit;
}
try {
  $stmt = $pdo->prepare("
    INSERT INTO buildings (name, description, address)
    VALUES (?, ?, ?)
  ");
  $stmt->execute([
    $name,
    $description ?: null,
    $address ?: null
  ]);
  $newBuildingId = $pdo->lastInsertId();
  http_response_code(201);
  echo json_encode([
    'message' => 'Building created successfully',
    'building_id' => $newBuildingId,
    'building_name' => $name
  ]);
  exit;
} catch (Exception $e) {
  http_response_code(500);
  error_log("Building creation error: " . $e->getMessage());
  echo json_encode(['error' => 'Failed to create building. Please try again.']);
  exit;
}
