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

// Check if user has permission to view floors or create rooms (needed for room creation)
if (!canViewFloors($_SESSION['user_id']) && !canCreateRooms($_SESSION['user_id'])) {
  http_response_code(403);
  echo json_encode(['error' => 'You do not have permission to view floors']);
  exit;
}

try {
  $stmt = $pdo->query("
    SELECT f.id as floor_id,
           f.name as floor_name,
           f.level_number,
           b.id as building_id,
           b.name as building_name,
           CONCAT(b.name, ', ', f.name) as display_name
    FROM floors f
    INNER JOIN buildings b ON f.building_id = b.id
    ORDER BY b.name, f.level_number, f.name
  ");

  $floors = $stmt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode($floors);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Failed to fetch floors with buildings']);
}
