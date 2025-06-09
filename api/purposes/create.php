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
if (!canEditUsers($userId)) {
  http_response_code(403);
  echo json_encode(['error' => 'No permission to create purposes']);
  exit;
}
$data = json_decode(file_get_contents('php://input'), true);
if (empty($data['name'])) {
  http_response_code(400);
  echo json_encode(['error' => 'Purpose name is required']);
  exit;
}
if (strlen(trim($data['name'])) < 2) {
  http_response_code(400);
  echo json_encode(['error' => 'Purpose name must be at least 2 characters long']);
  exit;
}
try {
  $stmt = $pdo->prepare("SELECT id FROM reservation_purposes WHERE name = ?");
  $stmt->execute([trim($data['name'])]);
  if ($stmt->fetch()) {
    http_response_code(400);
    echo json_encode(['error' => 'A purpose with this name already exists']);
    exit;
  }
  $stmt = $pdo->prepare("
        INSERT INTO reservation_purposes (name, description, requires_approval) 
        VALUES (?, ?, ?)
    ");
  $stmt->execute([
    trim($data['name']),
    trim($data['description'] ?? ''),
    isset($data['requires_approval']) ? (bool)$data['requires_approval'] : true
  ]);
  $purposeId = $pdo->lastInsertId();
  echo json_encode([
    'success' => true,
    'purpose_id' => $purposeId,
    'message' => 'Purpose created successfully'
  ]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}