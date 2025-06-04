<?php
require_once __DIR__ . '/../lib/db.php';

echo "Starting permission system migration...\n";

try {
    // Check if old permission columns exist
    $stmt = $pdo->query("SHOW COLUMNS FROM permissions LIKE 'can_%'");
    $oldColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($oldColumns)) {
        echo "Old permission columns not found. Migration may have already been run.\n";
        exit;
    }
    
    echo "Found old permission columns: " . implode(', ', $oldColumns) . "\n";
    
    // Get all existing permissions
    $stmt = $pdo->query("SELECT * FROM permissions");
    $existingPermissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($existingPermissions) . " existing permission records.\n";
    
    // Add new permission columns if they don't exist
    $newColumns = [
        'rooms_view' => 'BOOLEAN NOT NULL DEFAULT FALSE',
        'rooms_create' => 'BOOLEAN NOT NULL DEFAULT FALSE', 
        'rooms_edit' => 'BOOLEAN NOT NULL DEFAULT FALSE',
        'rooms_delete' => 'BOOLEAN NOT NULL DEFAULT FALSE',
        'reservations_view' => 'BOOLEAN NOT NULL DEFAULT FALSE',
        'reservations_create' => 'BOOLEAN NOT NULL DEFAULT FALSE',
        'reservations_edit' => 'BOOLEAN NOT NULL DEFAULT FALSE', 
        'reservations_delete' => 'BOOLEAN NOT NULL DEFAULT FALSE',
        'reservations_review_status' => 'BOOLEAN NOT NULL DEFAULT FALSE',
        'users_view' => 'BOOLEAN NOT NULL DEFAULT FALSE',
        'users_create' => 'BOOLEAN NOT NULL DEFAULT FALSE',
        'users_edit' => 'BOOLEAN NOT NULL DEFAULT FALSE',
        'users_delete' => 'BOOLEAN NOT NULL DEFAULT FALSE'
    ];
    
    foreach ($newColumns as $column => $definition) {
        try {
            $pdo->exec("ALTER TABLE permissions ADD COLUMN $column $definition");
            echo "Added column: $column\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "Column $column already exists, skipping.\n";
            } else {
                throw $e;
            }
        }
    }
    
    // Migrate existing permission data
    foreach ($existingPermissions as $perm) {
        $userId = $perm['user_id'];
        
        // Map old permissions to new structure
        $newPermissions = [
            // Room permissions - based on can_add_room and can_manage_rooms
            'rooms_view' => ($perm['can_add_room'] || $perm['can_manage_rooms']) ? 1 : 0,
            'rooms_create' => $perm['can_add_room'] ? 1 : 0,
            'rooms_edit' => $perm['can_manage_rooms'] ? 1 : 0,
            'rooms_delete' => $perm['can_manage_rooms'] ? 1 : 0,
            
            // Reservation permissions - based on can_manage_reservations and can_accept_reservations
            'reservations_view' => ($perm['can_manage_reservations'] || $perm['can_accept_reservations']) ? 1 : 0,
            'reservations_create' => 1, // Everyone can create reservations by default
            'reservations_edit' => $perm['can_manage_reservations'] ? 1 : 0,
            'reservations_delete' => $perm['can_manage_reservations'] ? 1 : 0,
            'reservations_review_status' => $perm['can_accept_reservations'] ? 1 : 0,
            
            // User management permissions - based on can_manage_users and can_verify_users
            'users_view' => ($perm['can_manage_users'] || $perm['can_verify_users']) ? 1 : 0,
            'users_create' => $perm['can_manage_users'] ? 1 : 0,
            'users_edit' => ($perm['can_manage_users'] || $perm['can_verify_users']) ? 1 : 0,
            'users_delete' => $perm['can_manage_users'] ? 1 : 0
        ];
        
        // Update the record with new permissions
        $updateFields = [];
        $updateParams = [];
        
        foreach ($newPermissions as $field => $value) {
            $updateFields[] = "$field = ?";
            $updateParams[] = $value;
        }
        
        $updateParams[] = $userId;
        
        $sql = "UPDATE permissions SET " . implode(', ', $updateFields) . " WHERE user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($updateParams);
        
        echo "Updated permissions for user ID: $userId\n";
    }
    
    echo "Permission migration completed successfully!\n";
    echo "Note: Old permission columns are still present. They can be removed manually after verifying the migration.\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
