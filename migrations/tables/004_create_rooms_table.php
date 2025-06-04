<?php

declare(strict_types=1);

echo "Creating rooms table...\n";

// Get database connection from parent scope
if (!isset($pdo)) {
    require_once __DIR__ . '/../../lib/db.php';
}

// Create rooms table
$roomsTableSql = <<<SQL
CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    room_type_id INT DEFAULT NULL,
    building_id INT DEFAULT NULL,
    floor_id INT DEFAULT NULL,
    capacity INT DEFAULT NULL,
    equipment TEXT DEFAULT NULL,
    description TEXT DEFAULT NULL,
    image_url VARCHAR(500) DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    features JSON DEFAULT NULL,
    availability_schedule JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (room_type_id) REFERENCES room_types(id) ON DELETE SET NULL,
    FOREIGN KEY (building_id) REFERENCES buildings(id) ON DELETE CASCADE,
    FOREIGN KEY (floor_id) REFERENCES floors(id) ON DELETE CASCADE,
    INDEX idx_room_active (is_active),
    INDEX idx_room_capacity (capacity),
    INDEX idx_room_building (building_id),
    INDEX idx_room_floor (floor_id)
);
SQL;

$pdo->exec($roomsTableSql);

echo "✅ Rooms table created successfully\n";
