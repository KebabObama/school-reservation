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

// Check permission to manage purposes (assuming admin-level permission)
try {
    $permStmt = $pdo->prepare("SELECT can_manage_users FROM permissions WHERE user_id = ?");
    $permStmt->execute([$userId]);
    $canManage = $permStmt->fetchColumn();
    
    if (!$canManage) {
        http_response_code(403);
        echo json_encode(['error' => 'No permission to create purposes']);
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Unable to verify permissions']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (empty($data['name'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Purpose name is required']);
    exit;
}

// Validate name length
if (strlen(trim($data['name'])) < 2) {
    http_response_code(400);
    echo json_encode(['error' => 'Purpose name must be at least 2 characters long']);
    exit;
}

try {
    // Check if purpose name already exists
    $stmt = $pdo->prepare("SELECT id FROM reservation_purposes WHERE name = ?");
    $stmt->execute([trim($data['name'])]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['error' => 'A purpose with this name already exists']);
        exit;
    }

    // Insert new purpose
    $stmt = $pdo->prepare("
        INSERT INTO reservation_purposes (name, description, requires_approval) 
        VALUES (?, ?, ?)
    ");
    
    $stmt->execute([
        trim($data['name']),
        trim($data['description'] ?? ''),
        isset($data['requires_approval']) ? (bool)$data['requires_approval'] : true
    ]);

    $purposeId = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'purpose_id' => $purposeId,
        'message' => 'Purpose created successfully'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
