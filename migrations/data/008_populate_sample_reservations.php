<?php
declare(strict_types=1);
echo "Creating sample reservations...\n";
if (!isset($pdo)) {
    require_once __DIR__ . '/../../lib/db.php';
}
$stmt = $pdo->query("SELECT id FROM users WHERE email != 'admin@spst.cz' ORDER BY id LIMIT 10");
$availableUsers = $stmt->fetchAll(PDO::FETCH_COLUMN);
$stmt = $pdo->query("SELECT id FROM rooms ORDER BY id LIMIT 15");
$availableRooms = $stmt->fetchAll(PDO::FETCH_COLUMN);
$stmt = $pdo->query("SELECT id FROM reservation_purposes ORDER BY id LIMIT 6");
$availablePurposes = $stmt->fetchAll(PDO::FETCH_COLUMN);
if (empty($availableUsers) || empty($availableRooms) || empty($availablePurposes)) {
    echo "⚠️  Insufficient data to create reservations (users: " . count($availableUsers) . ", rooms: " . count($availableRooms) . ", purposes: " . count($availablePurposes) . ")\n";
    return;
}
$currentDate = date('Y-m-d');
$sampleReservations = [
    [1, $availableUsers[0] ?? 2, $availablePurposes[0] ?? 1, 'Weekly Team Meeting', 'Regular team sync meeting', date('Y-m-d H:i:s', strtotime('-7 days 09:00')), date('Y-m-d H:i:s', strtotime('-7 days 10:00')), 'accepted', 8, null, null, 'none', null, null, 1, date('Y-m-d H:i:s', strtotime('-6 days'))],
    [2, $availableUsers[1] ?? 3, $availablePurposes[1] ?? 2, 'Product Training Session', 'Training for new product features', date('Y-m-d H:i:s', strtotime('-5 days 14:00')), date('Y-m-d H:i:s', strtotime('-5 days 16:00')), 'accepted', 15, 'Projector setup required', null, 'none', null, null, 1, date('Y-m-d H:i:s', strtotime('-4 days'))],
    [3, $availableUsers[2] ?? 4, $availablePurposes[2] ?? 3, 'Client Presentation', 'Quarterly review presentation', date('Y-m-d H:i:s', strtotime('-3 days 10:00')), date('Y-m-d H:i:s', strtotime('-3 days 12:00')), 'accepted', 6, 'Video conferencing setup', 'Catering for 6 people', 'none', null, null, 1, date('Y-m-d H:i:s', strtotime('-2 days'))],
    [4, $availableUsers[3] ?? 5, $availablePurposes[3] ?? 4, 'Board Meeting', 'Monthly board meeting', date('Y-m-d H:i:s', strtotime('today 09:00')), date('Y-m-d H:i:s', strtotime('today 11:00')), 'accepted', 10, null, 'Coffee and refreshments', 'none', null, null, 1, date('Y-m-d H:i:s', strtotime('-1 day'))],
    [5, $availableUsers[4] ?? 6, $availablePurposes[4] ?? 5, 'Study Group Session', 'Group study for upcoming exam', date('Y-m-d H:i:s', strtotime('today 15:00')), date('Y-m-d H:i:s', strtotime('today 17:00')), 'accepted', 4, null, null, 'none', null, null, 1, date('Y-m-d H:i:s', strtotime('-1 day'))],
    [6, $availableUsers[5] ?? 7, $availablePurposes[0] ?? 1, 'Project Kickoff Meeting', 'Starting new project initiative', date('Y-m-d H:i:s', strtotime('+1 day 10:00')), date('Y-m-d H:i:s', strtotime('+1 day 12:00')), 'pending', 12, 'Whiteboard and markers', null, 'none', null, null, null, null],
    [7, $availableUsers[6] ?? 8, $availablePurposes[1] ?? 2, 'Skills Workshop', 'Professional development workshop', date('Y-m-d H:i:s', strtotime('+2 days 13:00')), date('Y-m-d H:i:s', strtotime('+2 days 17:00')), 'pending', 20, 'Workshop materials setup', 'Lunch for participants', 'none', null, null, null, null],
    [8, $availableUsers[7] ?? 9, $availablePurposes[2] ?? 3, 'Customer Demo', 'Product demonstration for potential client', date('Y-m-d H:i:s', strtotime('+3 days 14:00')), date('Y-m-d H:i:s', strtotime('+3 days 15:30')), 'accepted', 8, 'Demo setup required', null, 'none', null, null, 1, date('Y-m-d H:i:s', strtotime('today'))],
    [9, $availableUsers[8] ?? 10, $availablePurposes[3] ?? 4, 'All Hands Meeting', 'Company-wide quarterly meeting', date('Y-m-d H:i:s', strtotime('+5 days 09:00')), date('Y-m-d H:i:s', strtotime('+5 days 11:00')), 'accepted', 50, 'Microphone and speakers', 'Breakfast for all attendees', 'none', null, null, 1, date('Y-m-d H:i:s', strtotime('today'))],
    [10, $availableUsers[9] ?? 11, $availablePurposes[4] ?? 5, 'Research Session', 'Individual research work', date('Y-m-d H:i:s', strtotime('+7 days 08:00')), date('Y-m-d H:i:s', strtotime('+7 days 12:00')), 'pending', 1, null, null, 'none', null, null, null, null],
    [11, $availableUsers[0] ?? 2, $availablePurposes[0] ?? 1, 'Cancelled Event', 'Event that was cancelled', date('Y-m-d H:i:s', strtotime('+4 days 10:00')), date('Y-m-d H:i:s', strtotime('+4 days 12:00')), 'cancelled', 15, null, null, 'none', null, null, null, null],
    [12, $availableUsers[1] ?? 3, $availablePurposes[1] ?? 2, 'Rejected Booking', 'Booking that was rejected due to conflict', date('Y-m-d H:i:s', strtotime('+6 days 14:00')), date('Y-m-d H:i:s', strtotime('+6 days 16:00')), 'rejected', 10, null, null, 'none', null, null, null, null],
    [13, $availableUsers[2] ?? 4, $availablePurposes[0] ?? 1, 'Weekly Team Standup', 'Regular weekly team meeting', date('Y-m-d H:i:s', strtotime('next Monday 09:00')), date('Y-m-d H:i:s', strtotime('next Monday 09:30')), 'accepted', 8, null, null, 'weekly', date('Y-m-d', strtotime('+3 months')), null, 1, date('Y-m-d H:i:s', strtotime('today'))],
    [14, $availableUsers[3] ?? 5, $availablePurposes[2] ?? 3, 'Investor Presentation', 'Quarterly investor update', date('Y-m-d H:i:s', strtotime('+10 days 15:00')), date('Y-m-d H:i:s', strtotime('+10 days 17:00')), 'pending', 12, 'Professional setup required', 'Premium catering', 'none', null, null, null, null],
    [15, $availableUsers[4] ?? 6, $availablePurposes[3] ?? 4, 'Department Meeting', 'Monthly department sync', date('Y-m-d H:i:s', strtotime('+14 days 11:00')), date('Y-m-d H:i:s', strtotime('+14 days 12:30')), 'accepted', 20, null, null, 'none', null, null, 1, date('Y-m-d H:i:s', strtotime('today'))],
    [16, $availableUsers[5] ?? 7, $availablePurposes[4] ?? 5, 'Exam Preparation', 'Final exam study session', date('Y-m-d H:i:s', strtotime('+21 days 13:00')), date('Y-m-d H:i:s', strtotime('+21 days 18:00')), 'pending', 6, null, null, 'none', null, null, null, null]
];
$insertedCount = 0;
foreach ($sampleReservations as $reservation) {
    $roomId = $availableRooms[($reservation[0] - 1) % count($availableRooms)] ?? 1;
    $userId = $reservation[1];
    $purposeId = $reservation[2];
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO reservations (
            room_id, user_id, purpose_id, title, description, start_time, end_time, status,
            attendees_count, setup_requirements, special_requests, recurring_type, recurring_end_date,
            parent_reservation_id, approved_by, approved_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $roomId,
        $userId,
        $purposeId,
        $reservation[3],
        $reservation[4],
        $reservation[5],
        $reservation[6],
        $reservation[7],
        $reservation[8],
        $reservation[9],
        $reservation[10],
        $reservation[11],
        $reservation[12],
        $reservation[13],
        $reservation[14],
        $reservation[15]
    ]);
    if ($stmt->rowCount() > 0) {
        $insertedCount++;
    }
}
echo "✅ Created $insertedCount sample reservations\n";