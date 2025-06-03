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

// Check permission to manage users
try {
    $permStmt = $pdo->prepare("SELECT can_manage_users FROM permissions WHERE user_id = ?");
    $permStmt->execute([$userId]);
    $canManageUsers = $permStmt->fetchColumn();
    
    if (!$canManageUsers) {
        http_response_code(403);
        echo json_encode(['error' => 'No permission to manage user permissions']);
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Unable to verify permissions']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (empty($data['user_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'User ID is required']);
    exit;
}

// Prevent users from modifying their own permissions
if ($data['user_id'] == $userId) {
    http_response_code(400);
    echo json_encode(['error' => 'Cannot modify your own permissions']);
    exit;
}

try {
    // Check if target user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$data['user_id']]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    // Handle bulk actions
    if (isset($data['bulk_action'])) {
        $permissions = [
            'can_add_room' => false,
            'can_verify_users' => false,
            'can_manage_reservations' => false,
            'can_manage_users' => false,
            'can_manage_rooms' => false,
            'can_accept_reservations' => false
        ];

        if ($data['bulk_action'] === 'grant_all') {
            $permissions = array_map(function() { return true; }, $permissions);
        }
        // For 'revoke_all', permissions are already set to false

        // Upsert permissions
        $stmt = $pdo->prepare("
            INSERT INTO permissions (user_id, can_add_room, can_verify_users, can_manage_reservations, 
                                   can_manage_users, can_manage_rooms, can_accept_reservations)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                can_add_room = VALUES(can_add_room),
                can_verify_users = VALUES(can_verify_users),
                can_manage_reservations = VALUES(can_manage_reservations),
                can_manage_users = VALUES(can_manage_users),
                can_manage_rooms = VALUES(can_manage_rooms),
                can_accept_reservations = VALUES(can_accept_reservations)
        ");

        $stmt->execute([
            $data['user_id'],
            $permissions['can_add_room'],
            $permissions['can_verify_users'],
            $permissions['can_manage_reservations'],
            $permissions['can_manage_users'],
            $permissions['can_manage_rooms'],
            $permissions['can_accept_reservations']
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Bulk permission update completed'
        ]);
        exit;
    }

    // Handle individual permission update
    if (empty($data['permission']) || !isset($data['value'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Permission name and value are required']);
        exit;
    }

    // Validate permission name
    $validPermissions = [
        'can_add_room',
        'can_verify_users',
        'can_manage_reservations',
        'can_manage_users',
        'can_manage_rooms',
        'can_accept_reservations'
    ];

    if (!in_array($data['permission'], $validPermissions)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid permission name']);
        exit;
    }

    // Check if permissions record exists
    $stmt = $pdo->prepare("SELECT user_id FROM permissions WHERE user_id = ?");
    $stmt->execute([$data['user_id']]);
    $permissionExists = $stmt->fetch();

    if ($permissionExists) {
        // Update existing permission
        $sql = "UPDATE permissions SET {$data['permission']} = ? WHERE user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([(bool)$data['value'], $data['user_id']]);
    } else {
        // Create new permissions record with default values
        $permissions = [
            'can_add_room' => false,
            'can_verify_users' => false,
            'can_manage_reservations' => false,
            'can_manage_users' => false,
            'can_manage_rooms' => false,
            'can_accept_reservations' => false
        ];
        
        // Set the specific permission
        $permissions[$data['permission']] = (bool)$data['value'];

        $stmt = $pdo->prepare("
            INSERT INTO permissions (user_id, can_add_room, can_verify_users, can_manage_reservations, 
                                   can_manage_users, can_manage_rooms, can_accept_reservations)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['user_id'],
            $permissions['can_add_room'],
            $permissions['can_verify_users'],
            $permissions['can_manage_reservations'],
            $permissions['can_manage_users'],
            $permissions['can_manage_rooms'],
            $permissions['can_accept_reservations']
        ]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Permission updated successfully'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
