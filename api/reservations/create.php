<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['error' => 'Not authenticated']);
  exit;
}

require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/reservation_utils.php';

$userId = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$requiredFields = ['room_id', 'title', 'start_time', 'end_time'];
foreach ($requiredFields as $f) {
  if (empty($data[$f])) {
    http_response_code(400);
    echo json_encode(['error' => "Field '$f' is required"]);
    exit;
  }
}

try {
  $recurringType = $data['recurring_type'] ?? 'none';
  $recurringEndDate = $data['recurring_end_date'] ?? null;

  // Handle recurring reservations
  if ($recurringType !== 'none' && $recurringEndDate) {
    // Generate all recurring dates
    $recurringDates = generateRecurringDates(
      $data['start_time'],
      $data['end_time'],
      $recurringType,
      $recurringEndDate
    );

    // Check for conflicts across all recurring instances
    $conflict = checkRecurringReservationConflicts($pdo, (int)$data['room_id'], $recurringDates);
    if ($conflict) {
      $conflictMessage = formatTimeConflictError($conflict);
      if (isset($conflict['occurrence_date'])) {
        $conflictMessage .= " (Conflict on occurrence: {$conflict['occurrence_date']})";
      }
      throw new Exception($conflictMessage);
    }
  } else {
    // Single reservation - validate normally
    validateReservationTimeSlot($pdo, (int)$data['room_id'], $data['start_time'], $data['end_time']);
  }

  $stmt = $pdo->prepare("INSERT INTO reservations
        (room_id, user_id, purpose_id, title, description, start_time, end_time, status, attendees_count, setup_requirements, special_requests, recurring_type, recurring_end_date, parent_reservation_id)
        VALUES (:room_id, :user_id, :purpose_id, :title, :description, :start_time, :end_time, 'pending', :attendees_count, :setup_requirements, :special_requests, :recurring_type, :recurring_end_date, :parent_reservation_id)
    ");

  $stmt->execute([
    ':room_id' => $data['room_id'],
    ':user_id' => $userId,
    ':purpose_id' => $data['purpose_id'] ?? null,
    ':title' => $data['title'],
    ':description' => $data['description'] ?? null,
    ':start_time' => $data['start_time'],
    ':end_time' => $data['end_time'],
    ':attendees_count' => $data['attendees_count'] ?? 1,
    ':setup_requirements' => $data['setup_requirements'] ?? null,
    ':special_requests' => $data['special_requests'] ?? null,
    ':recurring_type' => $data['recurring_type'] ?? 'none',
    ':recurring_end_date' => $data['recurring_end_date'] ?? null,
    ':parent_reservation_id' => $data['parent_reservation_id'] ?? null,
  ]);

  $reservationId = $pdo->lastInsertId();

  // Create recurring instances if this is a recurring reservation
  if ($recurringType !== 'none' && $recurringEndDate && isset($recurringDates)) {
    $reservationData = [
      'room_id' => $data['room_id'],
      'user_id' => $userId,
      'purpose_id' => $data['purpose_id'] ?? null,
      'title' => $data['title'],
      'description' => $data['description'] ?? null,
      'status' => 'pending',
      'attendees_count' => $data['attendees_count'] ?? 1,
      'setup_requirements' => $data['setup_requirements'] ?? null,
      'special_requests' => $data['special_requests'] ?? null,
    ];

    $createdInstances = createRecurringInstances($pdo, $reservationId, $reservationData, $recurringDates);

    echo json_encode([
      'reservation_id' => $reservationId,
      'recurring_instances' => count($createdInstances),
      'total_reservations' => count($createdInstances) + 1
    ]);
  } else {
    echo json_encode(['reservation_id' => $reservationId]);
  }
} catch (Exception $e) {
  http_response_code(400);
  echo json_encode(['error' => $e->getMessage()]);
}
