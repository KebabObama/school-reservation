<?php

declare(strict_types=1);

echo "Creating permissions table...\n";

// Get database connection from parent scope
if (!isset($pdo)) {
    require_once __DIR__ . '/../../lib/db.php';
}

// Create permissions table
$permissionsTableSql = <<<SQL
CREATE TABLE IF NOT EXISTS permissions (
    user_id INT PRIMARY KEY,
    -- Room permissions
    rooms_view BOOLEAN NOT NULL DEFAULT FALSE,
    rooms_create BOOLEAN NOT NULL DEFAULT FALSE,
    rooms_edit BOOLEAN NOT NULL DEFAULT FALSE,
    rooms_delete BOOLEAN NOT NULL DEFAULT FALSE,
    -- Building permissions
    buildings_view BOOLEAN NOT NULL DEFAULT FALSE,
    buildings_create BOOLEAN NOT NULL DEFAULT FALSE,
    buildings_edit BOOLEAN NOT NULL DEFAULT FALSE,
    buildings_delete BOOLEAN NOT NULL DEFAULT FALSE,
    -- Floor permissions
    floors_view BOOLEAN NOT NULL DEFAULT FALSE,
    floors_create BOOLEAN NOT NULL DEFAULT FALSE,
    floors_edit BOOLEAN NOT NULL DEFAULT FALSE,
    floors_delete BOOLEAN NOT NULL DEFAULT FALSE,
    -- Reservation permissions
    reservations_view BOOLEAN NOT NULL DEFAULT FALSE,
    reservations_create BOOLEAN NOT NULL DEFAULT FALSE,
    reservations_edit BOOLEAN NOT NULL DEFAULT FALSE,
    reservations_delete BOOLEAN NOT NULL DEFAULT FALSE,
    reservations_review_status BOOLEAN NOT NULL DEFAULT FALSE,
    -- User management permissions
    users_view BOOLEAN NOT NULL DEFAULT FALSE,
    users_create BOOLEAN NOT NULL DEFAULT FALSE,
    users_edit BOOLEAN NOT NULL DEFAULT FALSE,
    users_delete BOOLEAN NOT NULL DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
SQL;

$pdo->exec($permissionsTableSql);

echo "✅ Permissions table created successfully\n";
