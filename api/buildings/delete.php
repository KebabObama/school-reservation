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

// Check if user has permission to delete buildings
if (!canDeleteBuildings($_SESSION['user_id'])) {
  http_response_code(403);
  echo json_encode(['error' => 'You do not have permission to delete buildings']);
  exit;
}

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['id'])) {
  http_response_code(400);
  echo json_encode(['error' => 'Building ID is required']);
  exit;
}

try {
  // Check if building exists
  $stmt = $pdo->prepare("SELECT name FROM buildings WHERE id = ?");
  $stmt->execute([$data['id']]);
  $building = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$building) {
    http_response_code(404);
    echo json_encode(['error' => 'Building not found']);
    exit;
  }

  // Get counts for confirmation message
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM floors WHERE building_id = ?");
  $stmt->execute([$data['id']]);
  $floorCount = $stmt->fetchColumn();

  $stmt = $pdo->prepare("SELECT COUNT(*) FROM rooms WHERE building_id = ?");
  $stmt->execute([$data['id']]);
  $roomCount = $stmt->fetchColumn();

  // Get reservation count for rooms in this building
  $stmt = $pdo->prepare("
    SELECT COUNT(*) FROM reservations r
    INNER JOIN rooms rm ON r.room_id = rm.id
    WHERE rm.building_id = ?
  ");
  $stmt->execute([$data['id']]);
  $reservationCount = $stmt->fetchColumn();

  // Start transaction for cascading delete
  $pdo->beginTransaction();

  try {
    // Delete reservations for rooms in this building
    $stmt = $pdo->prepare("
      DELETE r FROM reservations r
      INNER JOIN rooms rm ON r.room_id = rm.id
      WHERE rm.building_id = ?
    ");
    $stmt->execute([$data['id']]);

    // Delete rooms in this building (this will also cascade to floors due to foreign key)
    $stmt = $pdo->prepare("DELETE FROM rooms WHERE building_id = ?");
    $stmt->execute([$data['id']]);

    // Delete floors in this building
    $stmt = $pdo->prepare("DELETE FROM floors WHERE building_id = ?");
    $stmt->execute([$data['id']]);

    // Delete the building
    $stmt = $pdo->prepare("DELETE FROM buildings WHERE id = ?");
    $stmt->execute([$data['id']]);

    if ($stmt->rowCount() === 0) {
      throw new Exception('Building not found');
    }

    $pdo->commit();

    $message = 'Building "' . $building['name'] . '" deleted successfully';
    if ($floorCount > 0 || $roomCount > 0 || $reservationCount > 0) {
      $message .= ' along with ' . $floorCount . ' floor(s), ' . $roomCount . ' room(s), and ' . $reservationCount . ' reservation(s)';
    }

    echo json_encode([
      'success' => true,
      'message' => $message
    ]);
  } catch (Exception $e) {
    $pdo->rollBack();
    throw $e;
  }
} catch (Exception $e) {
  http_response_code(500);
  error_log("Building deletion error: " . $e->getMessage());
  echo json_encode(['error' => 'Failed to delete building. Please try again.']);
}
