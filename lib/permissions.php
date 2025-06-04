<?php
require_once __DIR__ . '/db.php';

/**
 * Check if a user has a specific permission
 * 
 * @param int $userId The user ID to check
 * @param string $permission The permission to check (e.g., 'rooms_view', 'reservations_edit')
 * @return bool True if user has permission, false otherwise
 */
function hasPermission($userId, $permission) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT $permission FROM permissions WHERE user_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetchColumn();
        
        return (bool)$result;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Check if a user has any of the specified permissions
 * 
 * @param int $userId The user ID to check
 * @param array $permissions Array of permissions to check
 * @return bool True if user has at least one permission, false otherwise
 */
function hasAnyPermission($userId, $permissions) {
    foreach ($permissions as $permission) {
        if (hasPermission($userId, $permission)) {
            return true;
        }
    }
    return false;
}

/**
 * Check if a user has all of the specified permissions
 * 
 * @param int $userId The user ID to check
 * @param array $permissions Array of permissions to check
 * @return bool True if user has all permissions, false otherwise
 */
function hasAllPermissions($userId, $permissions) {
    foreach ($permissions as $permission) {
        if (!hasPermission($userId, $permission)) {
            return false;
        }
    }
    return true;
}

/**
 * Get all permissions for a user
 * 
 * @param int $userId The user ID
 * @return array Associative array of permissions
 */
function getUserPermissions($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM permissions WHERE user_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: [];
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Check if user can view rooms
 */
function canViewRooms($userId) {
    return hasPermission($userId, 'rooms_view');
}

/**
 * Check if user can create rooms
 */
function canCreateRooms($userId) {
    return hasPermission($userId, 'rooms_create');
}

/**
 * Check if user can edit rooms
 */
function canEditRooms($userId) {
    return hasPermission($userId, 'rooms_edit');
}

/**
 * Check if user can delete rooms
 */
function canDeleteRooms($userId) {
    return hasPermission($userId, 'rooms_delete');
}

/**
 * Check if user can view reservations
 */
function canViewReservations($userId) {
    return hasPermission($userId, 'reservations_view');
}

/**
 * Check if user can create reservations
 */
function canCreateReservations($userId) {
    return hasPermission($userId, 'reservations_create');
}

/**
 * Check if user can edit reservations
 */
function canEditReservations($userId) {
    return hasPermission($userId, 'reservations_edit');
}

/**
 * Check if user can delete reservations
 */
function canDeleteReservations($userId) {
    return hasPermission($userId, 'reservations_delete');
}

/**
 * Check if user can review/change reservation status
 */
function canReviewReservationStatus($userId) {
    return hasPermission($userId, 'reservations_review_status');
}

/**
 * Check if user can view users
 */
function canViewUsers($userId) {
    return hasPermission($userId, 'users_view');
}

/**
 * Check if user can create users
 */
function canCreateUsers($userId) {
    return hasPermission($userId, 'users_create');
}

/**
 * Check if user can edit users
 */
function canEditUsers($userId) {
    return hasPermission($userId, 'users_edit');
}

/**
 * Check if user can delete users
 */
function canDeleteUsers($userId) {
    return hasPermission($userId, 'users_delete');
}

/**
 * Check if user can edit a specific reservation
 * Users can edit their own reservations or if they have reservations_edit permission
 */
function canEditSpecificReservation($userId, $reservationUserId) {
    return $userId == $reservationUserId || canEditReservations($userId);
}

/**
 * Check if user can delete a specific reservation
 * Users can delete their own reservations or if they have reservations_delete permission
 */
function canDeleteSpecificReservation($userId, $reservationUserId) {
    return $userId == $reservationUserId || canDeleteReservations($userId);
}

/**
 * Get permission categories and their permissions
 */
function getPermissionCategories() {
    return [
        'rooms' => [
            'rooms_view' => 'View Rooms',
            'rooms_create' => 'Create Rooms',
            'rooms_edit' => 'Edit Rooms',
            'rooms_delete' => 'Delete Rooms'
        ],
        'reservations' => [
            'reservations_view' => 'View Reservations',
            'reservations_create' => 'Create Reservations',
            'reservations_edit' => 'Edit Reservations',
            'reservations_delete' => 'Delete Reservations',
            'reservations_review_status' => 'Review Reservation Status'
        ],
        'users' => [
            'users_view' => 'View Users',
            'users_create' => 'Create Users',
            'users_edit' => 'Edit Users',
            'users_delete' => 'Delete Users'
        ]
    ];
}

/**
 * Get all permission names as a flat array
 */
function getAllPermissionNames() {
    $categories = getPermissionCategories();
    $permissions = [];
    
    foreach ($categories as $category) {
        $permissions = array_merge($permissions, array_keys($category));
    }
    
    return $permissions;
}
?>
