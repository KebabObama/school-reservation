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

// Check if user has permission to delete floors
if (!canDeleteFloors($_SESSION['user_id'])) {
  http_response_code(403);
  echo json_encode(['error' => 'You do not have permission to delete floors']);
  exit;
}

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['id'])) {
  http_response_code(400);
  echo json_encode(['error' => 'Floor ID is required']);
  exit;
}

try {
  // Check if floor exists
  $stmt = $pdo->prepare("
    SELECT f.name, b.name as building_name
    FROM floors f
    LEFT JOIN buildings b ON f.building_id = b.id
    WHERE f.id = ?
  ");
  $stmt->execute([$data['id']]);
  $floor = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$floor) {
    http_response_code(404);
    echo json_encode(['error' => 'Floor not found']);
    exit;
  }

  // Get counts for confirmation message
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM rooms WHERE floor_id = ?");
  $stmt->execute([$data['id']]);
  $roomCount = $stmt->fetchColumn();

  // Get reservation count for rooms on this floor
  $stmt = $pdo->prepare("
    SELECT COUNT(*) FROM reservations r
    INNER JOIN rooms rm ON r.room_id = rm.id
    WHERE rm.floor_id = ?
  ");
  $stmt->execute([$data['id']]);
  $reservationCount = $stmt->fetchColumn();

  // Start transaction for cascading delete
  $pdo->beginTransaction();

  try {
    // Delete reservations for rooms on this floor
    $stmt = $pdo->prepare("
      DELETE r FROM reservations r
      INNER JOIN rooms rm ON r.room_id = rm.id
      WHERE rm.floor_id = ?
    ");
    $stmt->execute([$data['id']]);

    // Delete rooms on this floor
    $stmt = $pdo->prepare("DELETE FROM rooms WHERE floor_id = ?");
    $stmt->execute([$data['id']]);

    // Delete the floor
    $stmt = $pdo->prepare("DELETE FROM floors WHERE id = ?");
    $stmt->execute([$data['id']]);

    if ($stmt->rowCount() === 0) {
      throw new Exception('Floor not found');
    }

    $pdo->commit();

    $message = 'Floor "' . $floor['name'] . '" from ' . $floor['building_name'] . ' deleted successfully';
    if ($roomCount > 0 || $reservationCount > 0) {
      $message .= ' along with ' . $roomCount . ' room(s) and ' . $reservationCount . ' reservation(s)';
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
  error_log("Floor deletion error: " . $e->getMessage());
  echo json_encode(['error' => 'Failed to delete floor. Please try again.']);
}
