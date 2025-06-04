<?php

declare(strict_types=1);

echo "Creating admin user and permissions...\n";

// Get database connection from parent scope
if (!isset($pdo)) {
    require_once __DIR__ . '/../../lib/db.php';
}

// Insert Admin User (ignore if exists)
$hash = password_hash('admin', PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT IGNORE INTO users (email, name, surname, password_hash, is_verified) VALUES (?, ?, ?, ?, 1)");
$stmt->execute(['admin@spst.cz', 'Admin', 'Admin', $hash]);

// Get admin user ID
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute(['admin@spst.cz']);
$adminId = $stmt->fetchColumn();

if ($adminId) {
    // Upsert Admin Permissions (full access to everything)
    $pdo->prepare("
    REPLACE INTO permissions (
        user_id, rooms_view, rooms_create, rooms_edit, rooms_delete,
        buildings_view, buildings_create, buildings_edit, buildings_delete,
        floors_view, floors_create, floors_edit, floors_delete,
        reservations_view, reservations_create, reservations_edit, reservations_delete, reservations_review_status,
        users_view, users_create, users_edit, users_delete
    ) VALUES (?, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1)
    ")->execute([$adminId]);
    
    echo "✅ Admin user created with full permissions (ID: $adminId)\n";
} else {
    echo "⚠️  Admin user already exists\n";
}
