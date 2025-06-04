<?php

declare(strict_types=1);

echo "Creating sample buildings...\n";

// Get database connection from parent scope
if (!isset($pdo)) {
    require_once __DIR__ . '/../../lib/db.php';
}

// Insert default buildings
$buildings = [
    ['Building A', 'Main administrative and conference building', '123 Main Street'],
    ['Building B', 'Education and training facility', '456 Education Ave'],
    ['Building C', 'Library and study spaces', '789 Knowledge Blvd'],
    ['Building D', 'Technology and innovation center', '321 Tech Drive']
];

$insertedCount = 0;
foreach ($buildings as $building) {
    $stmt = $pdo->prepare("INSERT IGNORE INTO buildings (name, description, address) VALUES (?, ?, ?)");
    $stmt->execute($building);
    if ($stmt->rowCount() > 0) {
        $insertedCount++;
    }
}

echo "✅ Created $insertedCount buildings\n";
