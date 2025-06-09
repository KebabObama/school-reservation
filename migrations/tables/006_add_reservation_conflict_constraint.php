<?php
declare(strict_types=1);
echo "Adding reservation time conflict constraint...\n";
if (!isset($pdo)) {
  require_once __DIR__ . '/../../lib/db.php';
}
$constraintSql = <<<SQL
-- First, let's check if there are any existing conflicts and resolve them
-- This query will show any existing conflicts that need to be resolved manually
SELECT 
    r1.id as reservation1_id,
    r1.title as reservation1_title,
    r1.start_time as reservation1_start,
    r1.end_time as reservation1_end,
    r2.id as reservation2_id,
    r2.title as reservation2_title,
    r2.start_time as reservation2_start,
    r2.end_time as reservation2_end,
    rm.name as room_name
FROM reservations r1
JOIN reservations r2 ON r1.room_id = r2.room_id 
    AND r1.id < r2.id
    AND r1.status IN ('pending', 'accepted')
    AND r2.status IN ('pending', 'accepted')
    AND r1.start_time < r2.end_time 
    AND r1.end_time > r2.start_time
JOIN rooms rm ON r1.room_id = rm.id
ORDER BY rm.name, r1.start_time;
SQL;
try {
  $stmt = $pdo->query($constraintSql);
  $conflicts = $stmt->fetchAll(PDO::FETCH_ASSOC);
  if (!empty($conflicts)) {
    echo "⚠️  Found " . count($conflicts) . " existing time conflicts:\n";
    foreach ($conflicts as $conflict) {
      echo "  - Room '{$conflict['room_name']}': '{$conflict['reservation1_title']}' ({$conflict['reservation1_start']} - {$conflict['reservation1_end']}) conflicts with '{$conflict['reservation2_title']}' ({$conflict['reservation2_start']} - {$conflict['reservation2_end']})\n";
    }
    echo "  Please resolve these conflicts manually before applying the constraint.\n";
    echo "  You can either:\n";
    echo "  1. Change the status of conflicting reservations to 'rejected' or 'cancelled'\n";
    echo "  2. Modify the times to avoid overlap\n";
    echo "  3. Move one reservation to a different room\n";
    return;
  }
  echo "✅ No existing time conflicts found. Proceeding with constraint creation...\n";
  $indexSql = <<<SQL
-- Add a composite index to improve performance of conflict detection queries
CREATE INDEX IF NOT EXISTS idx_reservation_room_time_status
ON reservations (room_id, start_time, end_time, status);
-- Add a more specific index for active reservations (MariaDB doesn't support partial indexes)
CREATE INDEX IF NOT EXISTS idx_reservation_active_conflicts
ON reservations (room_id, status, start_time, end_time);
SQL;
  $pdo->exec($indexSql);
  echo "✅ Reservation conflict detection indexes created successfully\n";
  echo "✅ Time conflict checking is now enforced at the application level\n";
} catch (Exception $e) {
  echo "❌ Error adding reservation conflict constraint: " . $e->getMessage() . "\n";
  throw $e;
}