<?php
declare(strict_types=1);

function loadEnv(string $path): array {
    $env = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (strpos($line, '=') === false) continue;
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $env[$key] = $value;
    }
    return $env;
}

$env = loadEnv(__DIR__ . '/../.env');

$host = $env['DB_HOST'] ?? '127.0.0.1';
$port = $env['DB_PORT'] ?? '3306';
$user = $env['DB_USER'] ?? 'root';
$pass = $env['DB_PASS'] ?? '';
$dbName = $env['DB_NAME'] ?? 'room_manager';

// Connect to MySQL server without DB to create DB if not exists
$pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
$pdo->exec("USE `$dbName`");

// Schema creation SQL (run all at once)
$schemaSql = <<<SQL
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    surname VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_verified BOOLEAN NOT NULL DEFAULT FALSE
);

CREATE TABLE IF NOT EXISTS tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    expires_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS permissions (
    user_id INT PRIMARY KEY,
    can_add_room BOOLEAN NOT NULL DEFAULT FALSE,
    can_verify_users BOOLEAN NOT NULL DEFAULT FALSE,
    can_manage_reservations BOOLEAN NOT NULL DEFAULT FALSE,
    can_manage_users BOOLEAN NOT NULL DEFAULT FALSE,
    can_manage_rooms BOOLEAN NOT NULL DEFAULT FALSE,
    can_accept_reservations BOOLEAN NOT NULL DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS room_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    color VARCHAR(7) DEFAULT '#3B82F6',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    room_type_id INT DEFAULT NULL,
    capacity INT DEFAULT NULL,
    equipment TEXT DEFAULT NULL,
    location VARCHAR(255) DEFAULT NULL,
    floor VARCHAR(50) DEFAULT NULL,
    building VARCHAR(100) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    image_url VARCHAR(500) DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    features JSON DEFAULT NULL,
    availability_schedule JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (room_type_id) REFERENCES room_types(id) ON DELETE SET NULL,
    INDEX idx_room_active (is_active),
    INDEX idx_room_capacity (capacity),
    INDEX idx_room_location (location)
);

CREATE TABLE IF NOT EXISTS reservation_purposes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    requires_approval BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    user_id INT NOT NULL,
    purpose_id INT DEFAULT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    status ENUM('pending', 'accepted', 'rejected', 'cancelled') DEFAULT 'pending',
    attendees_count INT DEFAULT 1,
    setup_requirements TEXT,
    special_requests TEXT,
    recurring_type ENUM('none', 'daily', 'weekly', 'monthly') DEFAULT 'none',
    recurring_end_date DATE DEFAULT NULL,
    parent_reservation_id INT DEFAULT NULL,
    approved_by INT DEFAULT NULL,
    approved_at DATETIME DEFAULT NULL,
    cancelled_at DATETIME DEFAULT NULL,
    cancellation_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (purpose_id) REFERENCES reservation_purposes(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (parent_reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
    INDEX idx_reservation_time (start_time, end_time),
    INDEX idx_reservation_status (status),
    INDEX idx_reservation_room (room_id),
    INDEX idx_reservation_user (user_id),
    INDEX idx_reservation_date (start_time),
    CONSTRAINT chk_end_after_start CHECK (end_time > start_time)
);
SQL;

$pdo->exec($schemaSql);

// Insert Admin User (ignore if exists)
$hash = password_hash('admin', PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT IGNORE INTO users (email, name, surname, password_hash, is_verified) VALUES (?, ?, ?, ?, 1)");
$stmt->execute(['admin@spst.cz', 'Admin', 'Admin', $hash]);
$adminId = (int) $pdo->lastInsertId();

// Upsert Admin Permissions
$pdo->prepare("
REPLACE INTO permissions (
    user_id, can_add_room, can_verify_users, can_manage_reservations,
    can_manage_users, can_manage_rooms, can_accept_reservations
) VALUES (?, 1, 1, 1, 1, 1, 1)
")->execute([$adminId]);

// Insert default room types
$roomTypes = [
    ['Conference Room', 'Large rooms for meetings and presentations', '#3B82F6'],
    ['Meeting Room', 'Small to medium rooms for team meetings', '#10B981'],
    ['Training Room', 'Rooms equipped for training and workshops', '#F59E0B'],
    ['Auditorium', 'Large spaces for events and presentations', '#8B5CF6'],
    ['Study Room', 'Quiet spaces for individual or small group study', '#EF4444'],
    ['Lab', 'Specialized rooms with equipment for research', '#6B7280']
];

foreach ($roomTypes as $type) {
    $pdo->prepare("INSERT IGNORE INTO room_types (name, description, color) VALUES (?, ?, ?)")->execute($type);
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

foreach ($purposes as $purpose) {
    $pdo->prepare("INSERT IGNORE INTO reservation_purposes (name, description, requires_approval) VALUES (?, ?, ?)")->execute($purpose);
}

echo "Migration completed successfully.\n";