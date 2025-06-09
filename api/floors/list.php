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
if (!canViewFloors($_SESSION['user_id']) && !hasPermission($_SESSION['user_id'], 'rooms_create') && !hasPermission($_SESSION['user_id'], 'floors_create')) {
  http_response_code(403);
  echo json_encode(['error' => 'You do not have permission to view floors']);
  exit;
}
try {
  $stmt = $pdo->query("
    SELECT f.*, 
           b.name as building_name,
           COUNT(r.id) as room_count
    FROM floors f
    LEFT JOIN buildings b ON f.building_id = b.id
    LEFT JOIN rooms r ON f.id = r.floor_id
    GROUP BY f.id
    ORDER BY b.name, f.level_number, f.name
  ");
  $floors = $stmt->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode($floors);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Failed to fetch floors']);
}