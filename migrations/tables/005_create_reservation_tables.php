<?php

declare(strict_types=1);

echo "Creating reservation tables (reservation_purposes, reservations)...\n";

// Get database connection from parent scope
if (!isset($pdo)) {
    require_once __DIR__ . '/../../lib/db.php';
}

// Create reservation tables
$reservationTablesSql = <<<SQL
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

$pdo->exec($reservationTablesSql);

echo "✅ Reservation tables created successfully\n";
