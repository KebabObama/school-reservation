<?php

declare(strict_types=1);

/**
 * Utility functions for reservation management
 */

/**
 * Check if a reservation time slot conflicts with existing reservations
 * 
 * @param PDO $pdo Database connection
 * @param int $roomId Room ID to check
 * @param string $startTime Start time in Y-m-d H:i:s format
 * @param string $endTime End time in Y-m-d H:i:s format
 * @param int|null $excludeReservationId Reservation ID to exclude from conflict check (for editing)
 * @return array|null Returns conflict details if found, null if no conflict
 */
function checkReservationTimeConflict(PDO $pdo, int $roomId, string $startTime, string $endTime, ?int $excludeReservationId = null): ?array
{
  // Validate input times
  if (strtotime($endTime) <= strtotime($startTime)) {
    throw new InvalidArgumentException('End time must be after start time');
  }

  // Build the conflict detection query
  $sql = "
        SELECT id, title, start_time, end_time, status, parent_reservation_id
        FROM reservations
        WHERE room_id = :room_id
        AND status IN ('pending', 'accepted')
        AND (start_time < :end_time AND end_time > :start_time)
    ";

  $params = [
    ':room_id' => $roomId,
    ':start_time' => $startTime,
    ':end_time' => $endTime
  ];

  // Exclude current reservation and its recurring series if editing
  if ($excludeReservationId !== null) {
    // Get the parent reservation ID if this is part of a recurring series
    $parentStmt = $pdo->prepare("
            SELECT
                CASE
                    WHEN parent_reservation_id IS NOT NULL THEN parent_reservation_id
                    ELSE id
                END as parent_id
            FROM reservations
            WHERE id = ?
        ");
    $parentStmt->execute([$excludeReservationId]);
    $parentResult = $parentStmt->fetch(PDO::FETCH_ASSOC);

    if ($parentResult) {
      $parentId = $parentResult['parent_id'];
      // Exclude the entire recurring series (parent and all children)
      $sql .= " AND id != :exclude_id AND parent_reservation_id != :exclude_parent_id AND id != :exclude_parent_id";
      $params[':exclude_id'] = $excludeReservationId;
      $params[':exclude_parent_id'] = $parentId;
    } else {
      $sql .= " AND id != :exclude_id";
      $params[':exclude_id'] = $excludeReservationId;
    }
  }

  $sql .= " LIMIT 1";

  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);

  $conflict = $stmt->fetch(PDO::FETCH_ASSOC);

  return $conflict ?: null;
}

/**
 * Format a time conflict error message
 * 
 * @param array $conflict Conflict details from checkReservationTimeConflict
 * @return string Formatted error message
 */
function formatTimeConflictError(array $conflict): string
{
  $conflictStart = date('Y-m-d H:i', strtotime($conflict['start_time']));
  $conflictEnd = date('Y-m-d H:i', strtotime($conflict['end_time']));

  return "Time conflict: Room is already reserved from {$conflictStart} to {$conflictEnd} for '{$conflict['title']}'";
}

/**
 * Validate reservation time slot and check for conflicts
 * 
 * @param PDO $pdo Database connection
 * @param int $roomId Room ID to check
 * @param string $startTime Start time in Y-m-d H:i:s format
 * @param string $endTime End time in Y-m-d H:i:s format
 * @param int|null $excludeReservationId Reservation ID to exclude from conflict check (for editing)
 * @throws Exception If validation fails or conflict is found
 */
function validateReservationTimeSlot(PDO $pdo, int $roomId, string $startTime, string $endTime, ?int $excludeReservationId = null): void
{
  // Check basic time validation
  if (strtotime($endTime) <= strtotime($startTime)) {
    throw new Exception('End time must be after start time');
  }

  // Check for conflicts
  $conflict = checkReservationTimeConflict($pdo, $roomId, $startTime, $endTime, $excludeReservationId);

  if ($conflict) {
    throw new Exception(formatTimeConflictError($conflict));
  }
}

/**
 * Get all reservations for a room within a date range
 * 
 * @param PDO $pdo Database connection
 * @param int $roomId Room ID
 * @param string $startDate Start date in Y-m-d format
 * @param string $endDate End date in Y-m-d format
 * @param array $statuses Array of statuses to include (default: ['pending', 'accepted'])
 * @return array Array of reservations
 */
function getRoomReservations(PDO $pdo, int $roomId, string $startDate, string $endDate, array $statuses = ['pending', 'accepted']): array
{
  $placeholders = str_repeat('?,', count($statuses) - 1) . '?';

  $sql = "
        SELECT id, title, description, start_time, end_time, status, attendees_count,
               user_id, purpose_id, setup_requirements, special_requests
        FROM reservations 
        WHERE room_id = ? 
        AND DATE(start_time) <= ? 
        AND DATE(end_time) >= ?
        AND status IN ($placeholders)
        ORDER BY start_time ASC
    ";

  $params = array_merge([$roomId, $endDate, $startDate], $statuses);

  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);

  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Check if a user can modify a specific reservation
 *
 * @param int $userId Current user ID
 * @param int $reservationUserId Reservation owner user ID
 * @return bool True if user can modify the reservation
 */
function canModifyReservation(int $userId, int $reservationUserId): bool
{
  // Users can modify their own reservations
  if ($userId === $reservationUserId) {
    return true;
  }

  // Check if user has edit permissions (this would need to be implemented based on your permission system)
  // For now, we'll assume only the reservation owner can modify it
  return false;
}

/**
 * Generate recurring reservation dates
 *
 * @param string $startTime Start time of the first reservation
 * @param string $endTime End time of the first reservation
 * @param string $recurringType Type of recurrence (daily, weekly, monthly)
 * @param string $recurringEndDate End date for the recurring series
 * @return array Array of [start_time, end_time] pairs for each occurrence
 */
function generateRecurringDates(string $startTime, string $endTime, string $recurringType, string $recurringEndDate): array
{
  $dates = [];
  $currentStart = new DateTime($startTime);
  $currentEnd = new DateTime($endTime);
  $endDate = new DateTime($recurringEndDate . ' 23:59:59');

  // Calculate duration
  $duration = $currentStart->diff($currentEnd);

  // Add the first occurrence
  $dates[] = [
    'start_time' => $currentStart->format('Y-m-d H:i:s'),
    'end_time' => $currentEnd->format('Y-m-d H:i:s')
  ];

  // Generate recurring occurrences
  $maxOccurrences = 365; // Safety limit to prevent infinite loops
  $count = 0;

  while ($currentStart < $endDate && $count < $maxOccurrences) {
    $count++;

    // Calculate next occurrence based on recurring type
    switch ($recurringType) {
      case 'daily':
        $currentStart->add(new DateInterval('P1D'));
        break;
      case 'weekly':
        $currentStart->add(new DateInterval('P1W'));
        break;
      case 'monthly':
        $currentStart->add(new DateInterval('P1M'));
        break;
      default:
        break 2; // Exit the loop for unknown types
    }

    // Calculate end time for this occurrence
    $currentEnd = clone $currentStart;
    $currentEnd->add($duration);

    // Check if this occurrence is within the end date
    if ($currentStart <= $endDate) {
      $dates[] = [
        'start_time' => $currentStart->format('Y-m-d H:i:s'),
        'end_time' => $currentEnd->format('Y-m-d H:i:s')
      ];
    }
  }

  return $dates;
}

/**
 * Check conflicts for all recurring reservation instances
 *
 * @param PDO $pdo Database connection
 * @param int $roomId Room ID to check
 * @param array $recurringDates Array of recurring dates from generateRecurringDates
 * @param int|null $excludeParentId Parent reservation ID to exclude (for editing)
 * @return array|null Returns first conflict found, null if no conflicts
 */
function checkRecurringReservationConflicts(PDO $pdo, int $roomId, array $recurringDates, ?int $excludeParentId = null): ?array
{
  foreach ($recurringDates as $index => $dateInfo) {
    $conflict = checkReservationTimeConflict(
      $pdo,
      $roomId,
      $dateInfo['start_time'],
      $dateInfo['end_time'],
      $excludeParentId
    );

    if ($conflict) {
      // Add occurrence information to the conflict
      $conflict['occurrence_index'] = $index;
      $conflict['occurrence_date'] = date('Y-m-d', strtotime($dateInfo['start_time']));
      return $conflict;
    }
  }

  return null;
}

/**
 * Create recurring reservation instances
 *
 * @param PDO $pdo Database connection
 * @param int $parentReservationId ID of the parent reservation
 * @param array $reservationData Base reservation data
 * @param array $recurringDates Array of recurring dates
 * @return array Array of created reservation IDs
 */
function createRecurringInstances(PDO $pdo, int $parentReservationId, array $reservationData, array $recurringDates): array
{
  $createdIds = [];

  // Skip the first occurrence as it's already created as the parent
  for ($i = 1; $i < count($recurringDates); $i++) {
    $dateInfo = $recurringDates[$i];

    $stmt = $pdo->prepare("
            INSERT INTO reservations (
                room_id, user_id, purpose_id, title, description, start_time, end_time,
                status, attendees_count, setup_requirements, special_requests,
                recurring_type, recurring_end_date, parent_reservation_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

    $stmt->execute([
      $reservationData['room_id'],
      $reservationData['user_id'],
      $reservationData['purpose_id'],
      $reservationData['title'],
      $reservationData['description'],
      $dateInfo['start_time'],
      $dateInfo['end_time'],
      $reservationData['status'],
      $reservationData['attendees_count'],
      $reservationData['setup_requirements'],
      $reservationData['special_requests'],
      'none', // Child instances don't have recurring type
      null,   // Child instances don't have recurring end date
      $parentReservationId
    ]);

    $createdIds[] = $pdo->lastInsertId();
  }

  return $createdIds;
}

/**
 * Delete all instances of a recurring reservation series
 *
 * @param PDO $pdo Database connection
 * @param int $parentReservationId ID of the parent reservation
 * @return int Number of deleted reservations
 */
function deleteRecurringSeries(PDO $pdo, int $parentReservationId): int
{
  // Delete all child instances
  $stmt = $pdo->prepare("DELETE FROM reservations WHERE parent_reservation_id = ?");
  $stmt->execute([$parentReservationId]);
  $childrenDeleted = $stmt->rowCount();

  // Delete the parent reservation
  $stmt = $pdo->prepare("DELETE FROM reservations WHERE id = ?");
  $stmt->execute([$parentReservationId]);
  $parentDeleted = $stmt->rowCount();

  return $childrenDeleted + $parentDeleted;
}

/**
 * Get all instances of a recurring reservation series
 *
 * @param PDO $pdo Database connection
 * @param int $parentReservationId ID of the parent reservation
 * @return array Array of all reservations in the series
 */
function getRecurringSeries(PDO $pdo, int $parentReservationId): array
{
  $stmt = $pdo->prepare("
        SELECT * FROM reservations
        WHERE id = ? OR parent_reservation_id = ?
        ORDER BY start_time ASC
    ");
  $stmt->execute([$parentReservationId, $parentReservationId]);

  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Update all future instances of a recurring reservation series
 *
 * @param PDO $pdo Database connection
 * @param int $parentReservationId ID of the parent reservation
 * @param array $updateData Data to update
 * @param string $fromDate Only update instances from this date onwards (Y-m-d format)
 * @return int Number of updated reservations
 */
function updateRecurringSeriesFromDate(PDO $pdo, int $parentReservationId, array $updateData, string $fromDate): int
{
  $allowedFields = [
    'title',
    'description',
    'purpose_id',
    'attendees_count',
    'setup_requirements',
    'special_requests',
    'status'
  ];

  $updates = [];
  $params = [];

  foreach ($updateData as $field => $value) {
    if (in_array($field, $allowedFields)) {
      $updates[] = "$field = ?";
      $params[] = $value;
    }
  }

  if (empty($updates)) {
    return 0;
  }

  // Add date and parent ID parameters
  $params[] = $fromDate;
  $params[] = $parentReservationId;
  $params[] = $parentReservationId;

  $sql = "
        UPDATE reservations
        SET " . implode(', ', $updates) . ", updated_at = CURRENT_TIMESTAMP
        WHERE (id = ? OR parent_reservation_id = ?)
        AND DATE(start_time) >= ?
    ";

  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);

  return $stmt->rowCount();
}

/**
 * Check if a reservation is part of a recurring series
 *
 * @param PDO $pdo Database connection
 * @param int $reservationId ID of the reservation to check
 * @return array|null Returns parent reservation info if part of series, null otherwise
 */
function getRecurringParent(PDO $pdo, int $reservationId): ?array
{
  // First check if this reservation has a parent
  $stmt = $pdo->prepare("SELECT parent_reservation_id FROM reservations WHERE id = ?");
  $stmt->execute([$reservationId]);
  $result = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($result && $result['parent_reservation_id']) {
    // This is a child, get the parent
    $stmt = $pdo->prepare("SELECT * FROM reservations WHERE id = ?");
    $stmt->execute([$result['parent_reservation_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  // Check if this reservation is a parent (has recurring_type != 'none')
  $stmt = $pdo->prepare("SELECT * FROM reservations WHERE id = ? AND recurring_type != 'none'");
  $stmt->execute([$reservationId]);
  $parent = $stmt->fetch(PDO::FETCH_ASSOC);

  return $parent ?: null;
}
