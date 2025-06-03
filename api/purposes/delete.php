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

// Check permission to manage purposes
try {
    $permStmt = $pdo->prepare("SELECT can_manage_users FROM permissions WHERE user_id = ?");
    $permStmt->execute([$userId]);
    $canManage = $permStmt->fetchColumn();
    
    if (!$canManage) {
        http_response_code(403);
        echo json_encode(['error' => 'No permission to delete purposes']);
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Unable to verify permissions']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Purpose ID is required']);
    exit;
}

try {
    // Check if purpose exists
    $stmt = $pdo->prepare("SELECT * FROM reservation_purposes WHERE id = ?");
    $stmt->execute([$data['id']]);
    $purpose = $stmt->fetch();
    
    if (!$purpose) {
        http_response_code(404);
        echo json_encode(['error' => 'Purpose not found']);
        exit;
    }

    // Check if purpose is being used in any reservations
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE purpose_id = ?");
    $stmt->execute([$data['id']]);
    $reservationCount = $stmt->fetchColumn();
    
    if ($reservationCount > 0) {
        http_response_code(400);
        echo json_encode([
            'error' => "Cannot delete purpose. It is currently used in {$reservationCount} reservation(s). Please update those reservations first."
        ]);
        exit;
    }

    // Delete the purpose
    $stmt = $pdo->prepare("DELETE FROM reservation_purposes WHERE id = ?");
    $stmt->execute([$data['id']]);

    echo json_encode([
        'success' => true,
        'message' => 'Purpose deleted successfully'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
