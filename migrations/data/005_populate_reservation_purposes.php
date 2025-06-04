<?php

declare(strict_types=1);

echo "Creating default reservation purposes...\n";

// Get database connection from parent scope
if (!isset($pdo)) {
    require_once __DIR__ . '/../../lib/db.php';
}

// Insert default reservation purposes
$purposes = [
    ['Meeting', 'Team meetings and discussions', true],
    ['Training', 'Training sessions and workshops', true],
    ['Presentation', 'Client presentations and demos', true],
    ['Conference', 'Large conferences and events', true],
    ['Study Session', 'Individual or group study', false],
    ['Interview', 'Job interviews and assessments', true],
    ['Workshop', 'Hands-on workshops and activities', true],
    ['Social Event', 'Company social events and gatherings', true]
];

$insertedCount = 0;
foreach ($purposes as $purpose) {
    $stmt = $pdo->prepare("INSERT IGNORE INTO reservation_purposes (name, description, requires_approval) VALUES (?, ?, ?)");
    $stmt->execute($purpose);
    if ($stmt->rowCount() > 0) {
        $insertedCount++;
    }
}

echo "✅ Created $insertedCount reservation purposes\n";
