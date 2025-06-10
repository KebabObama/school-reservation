<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: /');
  exit;
}
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/permissions.php';
if (!canEditUsers($_SESSION['user_id'])) {
  echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Access Denied</h1><p>You do not have permission to verify users.</p></div>';
  return;
}
try {
  $usersWithPermissions = $pdo->query("
    SELECT u.id, u.email, u.name, u.surname, u.is_verified
    FROM users u
    ORDER BY u.name, u.surname
  ")->fetchAll();
} catch (Exception $e) {
  $usersWithPermissions = [];
}
?>
<div class="space-y-6">
  <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center space-y-4 sm:space-y-0">
    <div>
      <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">User Verification Management</h1>
      <p class="text-gray-600">Manage user verification status - verified users can log in to the system</p>
    </div>
  </div>
  <?php if (empty($usersWithPermissions)): ?>
    <div class="bg-white rounded-lg shadow p-8 text-center">
      <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z">
        </path>
      </svg>
      <h3 class="text-lg font-medium text-gray-900 mb-2">No users found</h3>
      <p class="text-gray-600">No users available to manage verification for.</p>
    </div>
  <?php else: ?>
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <div class="divide-y divide-gray-200 overflow-y-auto">
        <?php foreach ($usersWithPermissions as $user): ?>
          <div class="p-4 flex items-center justify-between hover:bg-gray-50">
            <div class="flex items-center">
              <input type="checkbox" id="user-<?php echo $user['id']; ?>"
                class="user-checkbox mr-4 h-4 w-4 text-blue-600 rounded">
              <div class="flex items-center">
                <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center mr-4">
                  <span class="text-sm font-medium text-gray-700">
                    <?php echo strtoupper(substr($user['name'], 0, 1) . substr($user['surname'], 0, 1)); ?>
                  </span>
                </div>
                <div>
                  <div class="text-sm font-medium text-gray-900">
                    <?php echo htmlspecialchars($user['name'] . ' ' . $user['surname']); ?>
                  </div>
                  <div class="text-sm text-gray-500"><?php echo htmlspecialchars($user['email']); ?></div>
                </div>
              </div>
            </div>
            <div class="flex items-center space-x-3">
              <span id="status-badge-<?php echo $user['id']; ?>"
                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $user['is_verified'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                <?php echo $user['is_verified'] ? 'Verified' : 'Unverified'; ?>
              </span>
              <button id="verify-btn-<?php echo $user['id']; ?>" onclick="(async function() {
                const userId = <?php echo $user['id']; ?>;
                const currentStatus = <?php echo $user['is_verified'] ? 'true' : 'false'; ?>;
                const newStatus = !currentStatus;
                const actionText = newStatus ? 'verify' : 'unverify';
                const badge = document.getElementById('status-badge-' + userId);
                const button = document.getElementById('verify-btn-' + userId);
                const confirmed = await popupSystem.confirm(
                  'Are you sure you want to ' + actionText + ' this user?',
                  actionText.charAt(0).toUpperCase() + actionText.slice(1) + ' User',
                  {
                    confirmText: actionText.charAt(0).toUpperCase() + actionText.slice(1),
                    cancelText: 'Cancel',
                    type: newStatus ? 'info' : 'warning'
                  }
                );
                if (!confirmed) return;
                const response = await fetch('/api/users/verify.php', {
                  method: 'POST',
                  headers: { 'Content-Type': 'application/json' },
                  credentials: 'same-origin',
                  body: JSON.stringify({ user_id: userId, verified: newStatus })
                });
                const result = await response.json();
                if (result.success) {
                  popupSystem.success(result.message);
                  badge.className = 'inline-flex px-2 py-1 text-xs font-semibold rounded-full ' + (newStatus ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800');
                  badge.textContent = newStatus ? 'Verified' : 'Unverified';
                  button.className = (newStatus ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700') + ' text-white px-3 py-1 rounded text-sm flex items-center';
                  button.innerHTML = newStatus
                    ? `<svg class='w-4 h-4 mr-1' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z'></path></svg>Unverify`
                    : `<svg class='w-4 h-4 mr-1' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'></path></svg>Verify`;
                  const newOnclick = button.getAttribute('onclick').replace('const currentStatus = ' + currentStatus + ';', 'const currentStatus = ' + newStatus + ';');
                  button.setAttribute('onclick', newOnclick);
                } else popupSystem.error(result.error || 'Unknown error');
              })()"
                class="<?php echo $user['is_verified'] ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700'; ?> text-white px-3 py-1 rounded text-sm flex items-center">
                <?php if ($user['is_verified']): ?>
                  <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>Unverify
                <?php else: ?>
                  <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>Verify
                <?php endif; ?>
              </button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</div>