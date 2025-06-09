<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!isset($_SESSION['user_id'])) {
  header('Location: /');
  exit;
}

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/permissions.php';

if (!canEditUsers($_SESSION['user_id'])) {
  echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Access Denied</h1><p>You do not have permission to manage user permissions.</p></div>';
  return;
}

try {
  $allPermissions = getAllPermissionNames();
  $permissionColumns = implode(', p.', $allPermissions);

  $usersWithPermissions = $pdo->query("
        SELECT u.id, u.email, u.name, u.surname, u.is_verified,
               p.$permissionColumns
        FROM users u
        LEFT JOIN permissions p ON u.id = p.user_id
        ORDER BY u.name, u.surname
    ")->fetchAll();
} catch (Exception $e) {
  $usersWithPermissions = [];
}

$permissionCategories = getPermissionCategories();
$permissions = [];
foreach ($permissionCategories as $category) {
  $permissions = array_merge($permissions, $category);
}
?>

<div class="space-y-6">
  <div class="flex justify-between items-center">
    <div>
      <h1 class="text-3xl font-bold text-gray-900">Permission Management</h1>
      <p class="text-gray-600">Manage individual user permissions with detailed controls</p>
    </div>
    <button onclick="loadPage('Permissions')"
      class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg flex items-center">
      <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
      </svg>
      Back to Overview
    </button>
  </div>

  <div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
      <h2 class="text-lg font-medium text-gray-900">Users & Permissions</h2>
    </div>

    <div class="divide-y divide-gray-200">
      <?php foreach ($usersWithPermissions as $user): ?>
      <div class="p-6">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                <span class="text-sm font-medium text-gray-700">
                  <?php echo strtoupper(substr($user['name'], 0, 1) . substr($user['surname'], 0, 1)); ?>
                </span>
              </div>
            </div>
            <div class="ml-4">
              <div class="flex items-center">
                <h3 class="text-lg font-medium text-gray-900">
                  <?php echo htmlspecialchars($user['name'] . ' ' . $user['surname']); ?>
                </h3>
                <?php if ($user['is_verified']): ?>
                <span
                  class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                  Verified
                </span>
                <?php else: ?>
                <span
                  class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                  Unverified
                </span>
                <?php endif; ?>
              </div>
              <p class="text-sm text-gray-500"><?php echo htmlspecialchars($user['email']); ?></p>
            </div>
          </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <?php foreach ($permissions as $perm => $label): ?>
          <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
            <span class="text-sm font-medium text-gray-700"><?php echo $label; ?></span>
            <div class="flex items-center space-x-2">
              <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" <?php echo $user[$perm] ? 'checked' : ''; ?> class="sr-only peer" onchange="(async function(event) {
                                           const checkbox = event.target;
                                           const userId = <?php echo $user['id']; ?>;
                                           const permission = '<?php echo $perm; ?>';
                                           const isChecked = checkbox.checked;

                                           try {
                                               const response = await fetch('/api/permissions/update.php', {
                                                   method: 'POST',
                                                   headers: {'Content-Type': 'application/json'},
                                                   body: JSON.stringify({
                                                       user_id: userId,
                                                       permission: permission,
                                                       value: isChecked
                                                   }),
                                                   credentials: 'same-origin'
                                               });

                                               const result = await response.json();
                                               if (response.ok) {
                                                   const container = checkbox.closest('.p-3');
                                                   container.style.backgroundColor = '#f0f9ff';
                                                   setTimeout(() => {
                                                       container.style.backgroundColor = '#f9fafb';
                                                   }, 1000);
                                               } else {
                                                   popupSystem.error(result.error || 'Unknown error');
                                                   checkbox.checked = !isChecked; 
                                               }
                                           } catch (error) {
                                               popupSystem.error('Network error: ' + error.message);
                                               checkbox.checked = !isChecked; 
                                           }
                                       })(event)">
                <div
                  class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600">
                </div>
              </label>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>