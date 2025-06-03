<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /');
    exit;
}

require_once __DIR__ . '/../lib/db.php';

// Check if user has permission to verify users
try {
    $stmt = $pdo->prepare("SELECT can_verify_users FROM permissions WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $canVerifyUsers = $stmt->fetchColumn();
    
    if (!$canVerifyUsers) {
        echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Access Denied</h1><p>You do not have permission to verify user profiles.</p></div>';
        return;
    }
} catch (Exception $e) {
    echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Error</h1><p>Unable to verify permissions.</p></div>';
    return;
}

// Get users for verification
try {
    $unverifiedUsers = $pdo->query("
        SELECT u.id, u.email, u.name, u.surname, u.is_verified, 
               COUNT(r.id) as reservation_count,
               MAX(r.created_at) as last_reservation
        FROM users u
        LEFT JOIN reservations r ON u.id = r.user_id
        WHERE u.is_verified = 0
        GROUP BY u.id, u.email, u.name, u.surname, u.is_verified
        ORDER BY u.name, u.surname
    ")->fetchAll();
    
    $verifiedUsers = $pdo->query("
        SELECT u.id, u.email, u.name, u.surname, u.is_verified,
               COUNT(r.id) as reservation_count,
               MAX(r.created_at) as last_reservation
        FROM users u
        LEFT JOIN reservations r ON u.id = r.user_id
        WHERE u.is_verified = 1
        GROUP BY u.id, u.email, u.name, u.surname, u.is_verified
        ORDER BY u.name, u.surname
        LIMIT 20
    ")->fetchAll();
} catch (Exception $e) {
    $unverifiedUsers = [];
    $verifiedUsers = [];
}
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Profile Verification</h1>
            <p class="text-gray-600">Verify user profiles and manage account status</p>
        </div>
        <div class="flex space-x-2">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                <?php echo count($unverifiedUsers); ?> Pending
            </span>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                <?php echo count($verifiedUsers); ?> Verified
            </span>
        </div>
    </div>

    <!-- Unverified Users -->
    <?php if (!empty($unverifiedUsers)): ?>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-yellow-50">
            <h2 class="text-lg font-medium text-gray-900 flex items-center">
                <svg class="w-5 h-5 mr-2 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                Pending Verification (<?php echo count($unverifiedUsers); ?>)
            </h2>
        </div>
        
        <div class="divide-y divide-gray-200">
            <?php foreach ($unverifiedUsers as $user): ?>
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="h-12 w-12 rounded-full bg-yellow-100 flex items-center justify-center">
                                <span class="text-lg font-medium text-yellow-800">
                                    <?php echo strtoupper(substr($user['name'], 0, 1) . substr($user['surname'], 0, 1)); ?>
                                </span>
                            </div>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-gray-900">
                                <?php echo htmlspecialchars($user['name'] . ' ' . $user['surname']); ?>
                            </h3>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($user['email']); ?></p>
                            <div class="mt-1 flex items-center text-xs text-gray-400">
                                <span><?php echo $user['reservation_count']; ?> reservations</span>
                                <?php if ($user['last_reservation']): ?>
                                    <span class="mx-1">•</span>
                                    <span>Last activity: <?php echo date('M j, Y', strtotime($user['last_reservation'])); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex space-x-2">
                        <button onclick="(async function() {
                            const userId = <?php echo $user['id']; ?>;
                            
                            try {
                                const response = await fetch('/api/users/verify.php', {
                                    method: 'POST',
                                    headers: {'Content-Type': 'application/json'},
                                    body: JSON.stringify({
                                        user_id: userId,
                                        action: 'verify'
                                    }),
                                    credentials: 'same-origin'
                                });
                                
                                const result = await response.json();
                                if (response.ok) {
                                    alert('User verified successfully!');
                                    location.reload();
                                } else {
                                    alert('Error: ' + (result.error || 'Unknown error'));
                                }
                            } catch (error) {
                                alert('Network error: ' + error.message);
                            }
                        })()"
                        class="px-4 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                            Verify
                        </button>
                        
                        <button onclick="(async function() {
                            if (!confirm('Are you sure you want to reject this user? This action cannot be undone.')) return;
                            
                            const userId = <?php echo $user['id']; ?>;
                            
                            try {
                                const response = await fetch('/api/users/verify.php', {
                                    method: 'POST',
                                    headers: {'Content-Type': 'application/json'},
                                    body: JSON.stringify({
                                        user_id: userId,
                                        action: 'reject'
                                    }),
                                    credentials: 'same-origin'
                                });
                                
                                const result = await response.json();
                                if (response.ok) {
                                    alert('User rejected and removed.');
                                    location.reload();
                                } else {
                                    alert('Error: ' + (result.error || 'Unknown error'));
                                }
                            } catch (error) {
                                alert('Network error: ' + error.message);
                            }
                        })()"
                        class="px-4 py-2 bg-red-600 text-white text-sm rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                            Reject
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-lg shadow p-6 text-center">
        <svg class="mx-auto h-12 w-12 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">All users verified</h3>
        <p class="mt-1 text-sm text-gray-500">No users are currently pending verification.</p>
    </div>
    <?php endif; ?>

    <!-- Recently Verified Users -->
    <?php if (!empty($verifiedUsers)): ?>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-green-50">
            <h2 class="text-lg font-medium text-gray-900 flex items-center">
                <svg class="w-5 h-5 mr-2 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                Verified Users (Recent)
            </h2>
        </div>
        
        <div class="divide-y divide-gray-200">
            <?php foreach (array_slice($verifiedUsers, 0, 10) as $user): ?>
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                                <span class="text-sm font-medium text-green-800">
                                    <?php echo strtoupper(substr($user['name'], 0, 1) . substr($user['surname'], 0, 1)); ?>
                                </span>
                            </div>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-base font-medium text-gray-900">
                                <?php echo htmlspecialchars($user['name'] . ' ' . $user['surname']); ?>
                            </h3>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($user['email']); ?></p>
                            <div class="mt-1 flex items-center text-xs text-gray-400">
                                <span><?php echo $user['reservation_count']; ?> reservations</span>
                                <?php if ($user['last_reservation']): ?>
                                    <span class="mx-1">•</span>
                                    <span>Last activity: <?php echo date('M j, Y', strtotime($user['last_reservation'])); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            Verified
                        </span>
                        
                        <button onclick="(async function() {
                            if (!confirm('Are you sure you want to unverify this user?')) return;
                            
                            const userId = <?php echo $user['id']; ?>;
                            
                            try {
                                const response = await fetch('/api/users/verify.php', {
                                    method: 'POST',
                                    headers: {'Content-Type': 'application/json'},
                                    body: JSON.stringify({
                                        user_id: userId,
                                        action: 'unverify'
                                    }),
                                    credentials: 'same-origin'
                                });
                                
                                const result = await response.json();
                                if (response.ok) {
                                    alert('User unverified successfully.');
                                    location.reload();
                                } else {
                                    alert('Error: ' + (result.error || 'Unknown error'));
                                }
                            } catch (error) {
                                alert('Network error: ' + error.message);
                            }
                        })()"
                        class="ml-3 px-3 py-1 bg-yellow-600 text-white text-xs rounded hover:bg-yellow-700">
                            Unverify
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
