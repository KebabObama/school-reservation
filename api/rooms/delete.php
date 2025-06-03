<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

require_once __DIR__ . '/../../lib/db.php';

$userId = $_SESSION['user_id'];

// Check permission to manage rooms
$permStmt = $pdo->prepare("SELECT can_manage_rooms FROM permissions WHERE user_id = ?");
$permStmt->execute([$userId]);
$canManageRooms = $permStmt->fetchColumn();

if (!$canManageRooms) {
    http_response_code(403);
    echo json_encode(['error' => 'No permission to delete rooms']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Room ID is required']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
    $stmt->execute([$data['id']]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$room) {
        throw new Exception('Room not found');
    }

    $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
    $stmt->execute([$data['id']]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}