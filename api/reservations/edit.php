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
require_once __DIR__ . '/../../lib/reservation_utils.php';
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
  if (!$reservation) {
    throw new Exception('Reservation not found');
  }
  if (!canEditSpecificReservation($userId, $reservation['user_id'])) {
    throw new Exception('No permission to edit this reservation');
  }
  if (isset($data['status']) && $data['status'] !== $reservation['status']) {
    if (!canReviewReservationStatus($userId) && $reservation['user_id'] != $userId) {
      throw new Exception('No permission to change reservation status');
    }
  }
  $recurringParent = getRecurringParent($pdo, (int)$data['id']);
  $isRecurring = $recurringParent !== null;
  $editScope = $data['edit_scope'] ?? 'single';
  $checkConflict = false;
  $newRoomId = $data['room_id'] ?? $reservation['room_id'];
  $newStartTime = $data['start_time'] ?? $reservation['start_time'];
  $newEndTime = $data['end_time'] ?? $reservation['end_time'];
  $newStatus = $data['status'] ?? $reservation['status'];
  if (in_array($newStatus, ['pending', 'accepted'])) {
    $checkConflict = (
      isset($data['room_id']) && $data['room_id'] != $reservation['room_id']
    ) || (
      isset($data['start_time']) && $data['start_time'] != $reservation['start_time']
    ) || (
      isset($data['end_time']) && $data['end_time'] != $reservation['end_time']
    ) || (
      isset($data['status']) && $data['status'] != $reservation['status'] && in_array($data['status'], ['pending', 'accepted'])
    );
  }
  if ($checkConflict) {
    if ($isRecurring && $editScope === 'series') {
      $parentId = $recurringParent['id'];
      validateReservationTimeSlot($pdo, (int)$newRoomId, $newStartTime, $newEndTime, $parentId);
    } else {
      validateReservationTimeSlot($pdo, (int)$newRoomId, $newStartTime, $newEndTime, (int)$data['id']);
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
  if ($isRecurring && $editScope === 'series') {
    $parentId = $recurringParent['id'];
    $seriesFields = ['title', 'description', 'purpose_id', 'attendees_count', 'setup_requirements', 'special_requests', 'status'];
    $seriesUpdates = [];
    $seriesParams = [];
    foreach ($seriesFields as $field) {
      if (array_key_exists($field, $data)) {
        $seriesUpdates[] = "$field = ?";
        $seriesParams[] = $data[$field];
      }
    }
    if (!empty($seriesUpdates)) {
      $seriesParams[] = $parentId;
      $seriesParams[] = $parentId;
      $seriesSql = "
        UPDATE reservations
        SET " . implode(', ', $seriesUpdates) . ", updated_at = CURRENT_TIMESTAMP
        WHERE id = ? OR parent_reservation_id = ?
      ";
      $stmt = $pdo->prepare($seriesSql);
      if (!$stmt->execute($seriesParams)) {
        throw new Exception('Failed to update recurring series: ' . implode(', ', $stmt->errorInfo()));
      }
      $affectedRows = $stmt->rowCount();
      echo json_encode([
        'success' => true,
        'message' => 'Recurring series updated successfully',
        'affected_rows' => $affectedRows,
        'edit_scope' => 'series'
      ]);
    } else {
      throw new Exception('No valid fields to update for recurring series');
    }
  } else {
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
    $params[':id'] = $data['id'];
    $sql = "UPDATE reservations SET " . implode(', ', $updates) . ", updated_at = CURRENT_TIMESTAMP WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    if (!$stmt->execute($params)) {
      throw new Exception('Failed to update reservation: ' . implode(', ', $stmt->errorInfo()));
    }
    if ($stmt->rowCount() === 0) {
      throw new Exception('No changes were made to the reservation');
    }
    echo json_encode([
      'success' => true,
      'message' => 'Reservation updated successfully',
      'affected_rows' => $stmt->rowCount(),
      'edit_scope' => 'single'
    ]);
  }
} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
  http_response_code(400);
  echo json_encode(['error' => $e->getMessage()]);
}