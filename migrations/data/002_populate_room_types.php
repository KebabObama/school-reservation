<?php
declare(strict_types=1);
echo "Creating default room types...\n";
if (!isset($pdo)) {
    require_once __DIR__ . '/../../lib/db.php';
}
$roomTypes = [
    ['Conference Room', 'Large rooms for meetings and presentations', '#3B82F6'],
    ['Meeting Room', 'Small to medium rooms for team meetings', '#10B981'],
    ['Training Room', 'Rooms equipped for training and workshops', '#F59E0B'],
    ['Auditorium', 'Large spaces for events and presentations', '#8B5CF6'],
    ['Study Room', 'Quiet spaces for individual or small group study', '#EF4444'],
    ['Lab', 'Specialized rooms with equipment for research', '#6B7280']
];
$insertedCount = 0;
foreach ($roomTypes as $type) {
    $stmt = $pdo->prepare("INSERT IGNORE INTO room_types (name, description, color) VALUES (?, ?, ?)");
    $stmt->execute($type);
    if ($stmt->rowCount() > 0) {
        $insertedCount++;
    }
}
echo "✅ Created $insertedCount room types\n";