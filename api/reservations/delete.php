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
$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['id'])) {
  http_response_code(400);
  echo json_encode(['error' => 'Reservation ID is required']);
  exit;
}

try {
  $stmt = $pdo->prepare("SELECT * FROM reservations WHERE id = ?");
  $stmt->execute([$data['id']]);
  $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$reservation)
    throw new Exception('Reservation not found');
  if (!canDeleteSpecificReservation($userId, $reservation['user_id'])) {
    throw new Exception('No permission to delete this reservation');
  }
  $stmt = $pdo->prepare("DELETE FROM reservations WHERE id = ?");
  $stmt->execute([$data['id']]);
  echo json_encode(['success' => true]);
} catch (Exception $e) {
  http_response_code(400);
  echo json_encode(['error' => $e->getMessage()]);
}
