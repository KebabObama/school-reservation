<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/permissions.php';
require_once __DIR__ . '/../../lib/token_middleware.php';

// Authenticate using token or session
$userData = requireAuth();

// Check if user has permission to view buildings
if (!canViewBuildings($userData['user_id'])) {
  http_response_code(403);
  echo json_encode(['error' => 'You do not have permission to view buildings']);
  exit;
}

try {
  $stmt = $pdo->query("
    SELECT b.*, 
           COUNT(f.id) as floor_count,
           COUNT(r.id) as room_count
    FROM buildings b
    LEFT JOIN floors f ON b.id = f.building_id
    LEFT JOIN rooms r ON b.id = r.building_id
    GROUP BY b.id
    ORDER BY b.name
  ");

  $buildings = $stmt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode($buildings);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Failed to fetch buildings']);
}
