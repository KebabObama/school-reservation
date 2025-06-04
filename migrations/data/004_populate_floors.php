<?php

declare(strict_types=1);

echo "Creating sample floors...\n";

// Get database connection from parent scope
if (!isset($pdo)) {
    require_once __DIR__ . '/../../lib/db.php';
}

// Insert default floors
$floors = [
    // Building A floors
    [1, '1st Floor', 'Ground floor with reception and meeting rooms', 1],
    [1, '2nd Floor', 'Conference rooms and administrative offices', 2],
    [1, '3rd Floor', 'Executive offices and boardrooms', 3],
    [1, '4th Floor', 'Executive boardroom and premium facilities', 4],

    // Building B floors
    [2, '1st Floor', 'Training rooms and workshops', 1],
    [2, '2nd Floor', 'Advanced training facilities', 2],

    // Building C floors
    [3, '1st Floor', 'Study rooms and quiet spaces', 1],
    [3, '2nd Floor', 'Group study areas and collaboration spaces', 2],

    // Building D floors
    [4, '3rd Floor', 'Innovation labs and creative spaces', 3]
];

$insertedCount = 0;
foreach ($floors as $floor) {
    $stmt = $pdo->prepare("INSERT IGNORE INTO floors (building_id, name, description, level_number) VALUES (?, ?, ?, ?)");
    $stmt->execute($floor);
    if ($stmt->rowCount() > 0) {
        $insertedCount++;
    }
}

echo "✅ Created $insertedCount floors\n";
