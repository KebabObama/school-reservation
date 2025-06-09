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
if (!canDeleteRooms($userId)) {
  http_response_code(403);
  echo json_encode(['error' => 'No permission to delete rooms']);
  exit;
}
$data = json_decode(file_get_contents('php://input'), true);
if (empty($data['id'])) {
  http_response_code(400);
  echo json_encode(['error' => 'Room ID is required']);
  exit;
}
try {
  $stmt = $pdo->prepare("SELECT name FROM rooms WHERE id = ?");
  $stmt->execute([$data['id']]);
  $room = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$room) {
    throw new Exception('Room not found');
  }
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE room_id = ?");
  $stmt->execute([$data['id']]);
  $reservationCount = $stmt->fetchColumn();
  $pdo->beginTransaction();
  try {
    $stmt = $pdo->prepare("DELETE FROM reservations WHERE room_id = ?");
    $stmt->execute([$data['id']]);
    $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
    $stmt->execute([$data['id']]);
    if ($stmt->rowCount() === 0) {
      throw new Exception('Room not found');
    }
    $pdo->commit();
    $message = 'Room "' . $room['name'] . '" deleted successfully';
    if ($reservationCount > 0) {
      $message .= ' along with ' . $reservationCount . ' reservation(s)';
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
  http_response_code(400);
  echo json_encode(['error' => $e->getMessage()]);
}