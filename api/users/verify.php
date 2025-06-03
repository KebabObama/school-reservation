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

// Check permission to verify users
try {
    $permStmt = $pdo->prepare("SELECT can_verify_users FROM permissions WHERE user_id = ?");
    $permStmt->execute([$userId]);
    $canVerifyUsers = $permStmt->fetchColumn();
    
    if (!$canVerifyUsers) {
        http_response_code(403);
        echo json_encode(['error' => 'No permission to verify users']);
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Unable to verify permissions']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (empty($data['user_id']) || empty($data['action'])) {
    http_response_code(400);
    echo json_encode(['error' => 'User ID and action are required']);
    exit;
}

// Validate action
$validActions = ['verify', 'unverify', 'reject'];
if (!in_array($data['action'], $validActions)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action. Must be verify, unverify, or reject']);
    exit;
}

// Prevent users from modifying their own verification status
if ($data['user_id'] == $userId) {
    http_response_code(400);
    echo json_encode(['error' => 'Cannot modify your own verification status']);
    exit;
}

try {
    // Check if target user exists
    $stmt = $pdo->prepare("SELECT id, email, name, surname, is_verified FROM users WHERE id = ?");
    $stmt->execute([$data['user_id']]);
    $targetUser = $stmt->fetch();
    
    if (!$targetUser) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    switch ($data['action']) {
        case 'verify':
            if ($targetUser['is_verified']) {
                http_response_code(400);
                echo json_encode(['error' => 'User is already verified']);
                exit;
            }
            
            $stmt = $pdo->prepare("UPDATE users SET is_verified = 1 WHERE id = ?");
            $stmt->execute([$data['user_id']]);
            
            $message = 'User verified successfully';
            break;

        case 'unverify':
            if (!$targetUser['is_verified']) {
                http_response_code(400);
                echo json_encode(['error' => 'User is already unverified']);
                exit;
            }
            
            $stmt = $pdo->prepare("UPDATE users SET is_verified = 0 WHERE id = ?");
            $stmt->execute([$data['user_id']]);
            
            $message = 'User unverified successfully';
            break;

        case 'reject':
            // Before deleting user, we need to handle their reservations
            // Option 1: Delete all their reservations (cascade should handle this)
            // Option 2: Transfer reservations to admin or mark as cancelled
            
            // For safety, let's cancel their pending reservations first
            $stmt = $pdo->prepare("
                UPDATE reservations 
                SET status = 'cancelled', 
                    cancellation_reason = 'User account rejected by administrator',
                    cancelled_at = NOW()
                WHERE user_id = ? AND status = 'pending'
            ");
            $stmt->execute([$data['user_id']]);
            
            // Delete user permissions first (if any)
            $stmt = $pdo->prepare("DELETE FROM permissions WHERE user_id = ?");
            $stmt->execute([$data['user_id']]);
            
            // Delete user tokens
            $stmt = $pdo->prepare("DELETE FROM tokens WHERE user_id = ?");
            $stmt->execute([$data['user_id']]);
            
            // Finally delete the user (this will cascade delete reservations due to FK constraint)
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$data['user_id']]);
            
            $message = 'User rejected and removed from system';
            break;
    }

    echo json_encode([
        'success' => true,
        'message' => $message
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
