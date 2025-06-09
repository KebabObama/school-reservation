<?php

declare(strict_types=1);
function checkReservationTimeConflict(PDO $pdo, int $roomId, string $startTime, string $endTime, ?int $excludeReservationId = null): ?array
{
  if (strtotime($endTime) <= strtotime($startTime))
    throw new InvalidArgumentException('End time must be after start time');
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
  if ($excludeReservationId !== null) {
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
function formatTimeConflictError(array $conflict): string
{
  $conflictStart = date('Y-m-d H:i', strtotime($conflict['start_time']));
  $conflictEnd = date('Y-m-d H:i', strtotime($conflict['end_time']));

  return "Time conflict: Room is already reserved from {$conflictStart} to {$conflictEnd} for '{$conflict['title']}'";
}


function validateReservationTimeSlot(PDO $pdo, int $roomId, string $startTime, string $endTime, ?int $excludeReservationId = null): void
{
  if (strtotime($endTime) <= strtotime($startTime))
    throw new Exception('End time must be after start time');
  $conflict = checkReservationTimeConflict($pdo, $roomId, $startTime, $endTime, $excludeReservationId);
  if ($conflict) {
    throw new Exception(formatTimeConflictError($conflict));
  }
}

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


function canModifyReservation(int $userId, int $reservationUserId): bool
{
  if ($userId === $reservationUserId)
    return true;
  return false;
}

function generateRecurringDates(string $startTime, string $endTime, string $recurringType, string $recurringEndDate): array
{
  $dates = [];
  $currentStart = new DateTime($startTime);
  $currentEnd = new DateTime($endTime);
  $endDate = new DateTime($recurringEndDate . ' 23:59:59');
  $duration = $currentStart->diff($currentEnd);
  $dates[] = [
    'start_time' => $currentStart->format('Y-m-d H:i:s'),
    'end_time' => $currentEnd->format('Y-m-d H:i:s')
  ];
  $maxOccurrences = 365;
  $count = 0;
  while ($currentStart < $endDate && $count < $maxOccurrences) {
    $count++;
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
        break 2;
    }
    $currentEnd = clone $currentStart;
    $currentEnd->add($duration);
    if ($currentStart <= $endDate) {
      $dates[] = [
        'start_time' => $currentStart->format('Y-m-d H:i:s'),
        'end_time' => $currentEnd->format('Y-m-d H:i:s')
      ];
    }
  }

  return $dates;
}

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
      $conflict['occurrence_index'] = $index;
      $conflict['occurrence_date'] = date('Y-m-d', strtotime($dateInfo['start_time']));
      return $conflict;
    }
  }
  return null;
}

function createRecurringInstances(PDO $pdo, int $parentReservationId, array $reservationData, array $recurringDates): array
{
  $createdIds = [];
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
      'none',
      null,
      $parentReservationId
    ]);
    $createdIds[] = $pdo->lastInsertId();
  }
  return $createdIds;
}

function deleteRecurringSeries(PDO $pdo, int $parentReservationId): int
{
  $stmt = $pdo->prepare("DELETE FROM reservations WHERE parent_reservation_id = ?");
  $stmt->execute([$parentReservationId]);
  $childrenDeleted = $stmt->rowCount();
  $stmt = $pdo->prepare("DELETE FROM reservations WHERE id = ?");
  $stmt->execute([$parentReservationId]);
  $parentDeleted = $stmt->rowCount();

  return $childrenDeleted + $parentDeleted;
}

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
  if (empty($updates))
    return 0;
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

function getRecurringParent(PDO $pdo, int $reservationId): ?array
{
  $stmt = $pdo->prepare("SELECT parent_reservation_id FROM reservations WHERE id = ?");
  $stmt->execute([$reservationId]);
  $result = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($result && $result['parent_reservation_id']) {
    $stmt = $pdo->prepare("SELECT * FROM reservations WHERE id = ?");
    $stmt->execute([$result['parent_reservation_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }
  $stmt = $pdo->prepare("SELECT * FROM reservations WHERE id = ? AND recurring_type != 'none'");
  $stmt->execute([$reservationId]);
  $parent = $stmt->fetch(PDO::FETCH_ASSOC);
  return $parent ?: null;
}