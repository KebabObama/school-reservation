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
  // Get reservation details
  $stmt = $pdo->prepare("SELECT * FROM reservations WHERE id = ?");
  $stmt->execute([$data['id']]);
  $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$reservation) {
    throw new Exception('Reservation not found');
  }

  // Check permissions - users can edit their own reservations or if they have edit permission
  if (!canEditSpecificReservation($userId, $reservation['user_id'])) {
    throw new Exception('No permission to edit this reservation');
  }

  // Special handling for status changes - only users with review_status permission can change status
  if (isset($data['status']) && $data['status'] !== $reservation['status']) {
    if (!canReviewReservationStatus($userId) && $reservation['user_id'] != $userId) {
      throw new Exception('No permission to change reservation status');
    }
  }

  // Check if this is part of a recurring series
  $recurringParent = getRecurringParent($pdo, (int)$data['id']);
  $isRecurring = $recurringParent !== null;

  // Determine edit scope (single instance or entire series)
  $editScope = $data['edit_scope'] ?? 'single'; // 'single' or 'series'

  // Check for time conflicts if room_id, start_time, or end_time are being changed
  $checkConflict = false;
  $newRoomId = $data['room_id'] ?? $reservation['room_id'];
  $newStartTime = $data['start_time'] ?? $reservation['start_time'];
  $newEndTime = $data['end_time'] ?? $reservation['end_time'];
  $newStatus = $data['status'] ?? $reservation['status'];

  // Check if we need to validate conflicts (only for active statuses)
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
    // For recurring reservations, we need to be more careful about conflict checking
    if ($isRecurring && $editScope === 'series') {
      // When editing the entire series, we need to check if the new time conflicts
      // with any existing reservations (excluding the entire current series)
      $parentId = $recurringParent['id'];
      validateReservationTimeSlot($pdo, (int)$newRoomId, $newStartTime, $newEndTime, $parentId);
    } else {
      // Single instance edit - validate normally
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

  // Handle recurring series editing
  if ($isRecurring && $editScope === 'series') {
    // Update the entire recurring series
    $parentId = $recurringParent['id'];

    // Filter fields that can be updated for the entire series
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
      // Update all reservations in the series
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
    // Single instance update
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
