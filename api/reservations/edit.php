<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['error' => 'Not authenticated']);
  exit;
}

require_once __DIR__ . '/../../lib/db.php';

$userId = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['id'])) {
  http_response_code(400);
  echo json_encode(['error' => 'Reservation ID is required']);
  exit;
}

try {
  // Get reservation details
  $stmt = $pdo->prepare("SELECT * FROM reservations WHERE id = ?");
  $stmt->execute([$data['id']]);
  $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$reservation) {
    throw new Exception('Reservation not found');
  }

  // Check permissions
  if ($reservation['user_id'] != $userId) {
    $permStmt = $pdo->prepare("SELECT can_manage_reservations FROM permissions WHERE user_id = ?");
    $permStmt->execute([$userId]);
    $perm = $permStmt->fetchColumn();
    if (!$perm) {
      throw new Exception('No permission to edit this reservation');
    }
  }
  $fields = [
    'room_id',
    'purpose_id',
    'title',
    'description',
    'start_time',
    'end_time',
    'status',
    'attendees_count',
    'setup_requirements',
    'special_requests',
    'recurring_type',
    'recurring_end_date',
    'parent_reservation_id',
    'approved_by',
    'approved_at',
    'cancelled_at',
    'cancellation_reason'
  ];

  // Build update query
  $updates = [];
  $params = [];

  foreach ($fields as $field) {
    if (array_key_exists($field, $data)) {
      $updates[] = "$field = :$field";
      $params[":$field"] = $data[$field];
    }
  }

  if (empty($updates)) {
    throw new Exception('No fields to update');
  }

  // Add ID parameter
  $params[':id'] = $data['id'];

  // Execute update
  $sql = "UPDATE reservations SET " . implode(', ', $updates) . ", updated_at = CURRENT_TIMESTAMP WHERE id = :id";
  $stmt = $pdo->prepare($sql);

  if (!$stmt->execute($params)) {
    throw new Exception('Failed to update reservation: ' . implode(', ', $stmt->errorInfo()));
  }

  // Check if any rows were affected
  if ($stmt->rowCount() === 0) {
    throw new Exception('No changes were made to the reservation');
  }

  echo json_encode([
    'success' => true,
    'message' => 'Reservation updated successfully',
    'affected_rows' => $stmt->rowCount()
  ]);
} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
  http_response_code(400);
  echo json_encode(['error' => $e->getMessage()]);
}
