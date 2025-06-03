<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!isset($_SESSION['user_id'])) {
  header('Location: /');
  exit;
}

require_once __DIR__ . '/../lib/db.php';

// Get users with their permissions
try {
  $usersWithPermissions = $pdo->query("
        SELECT u.id, u.email, u.name, u.surname, u.is_verified,
               p.can_add_room, p.can_verify_users, p.can_manage_reservations,
               p.can_manage_users, p.can_manage_rooms, p.can_accept_reservations
        FROM users u 
        LEFT JOIN permissions p ON u.id = p.user_id
        ORDER BY u.name, u.surname
    ")->fetchAll();
} catch (Exception $e) {
  $usersWithPermissions = [];
}

$permissionLabels = [
  'can_add_room' => 'Add Rooms',
  'can_verify_users' => 'Verify Users',
  'can_manage_reservations' => 'Manage Reservations',
  'can_manage_users' => 'Manage Users',
  'can_manage_rooms' => 'Manage Rooms',
  'can_accept_reservations' => 'Accept Reservations'
];
?>

<div class="space-y-6">
  <!-- Header -->
  <div class="flex justify-between items-center">
    <div>
      <h1 class="text-3xl font-bold text-gray-900">User Permissions</h1>
      <p class="text-gray-600">Manage user access rights and permissions</p>
    </div>
    <div class="flex space-x-2">
      <button onclick="loadPage('PermissionChanges')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
        </svg>
        Advanced Management
      </button>
      <button onclick="loadPage('ProfileVerification')" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        User Verification
      </button>
    </div>
  </div>

  <!-- Permissions Table -->
  <?php if (empty($usersWithPermissions)): ?>
    <div class="bg-white rounded-lg shadow p-8 text-center">
      <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
      </svg>
      <h3 class="text-lg font-medium text-gray-900 mb-2">No users found</h3>
      <p class="text-gray-600">No users available to manage permissions for.</p>
    </div>
  <?php else: ?>
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-0 bg-gray-50">
                User
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Status
              </th>
              <?php foreach ($permissionLabels as $key => $label): ?>
                <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                  <?php echo $label; ?>
                </th>
              <?php endforeach; ?>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <?php foreach ($usersWithPermissions as $user): ?>
              <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap sticky left-0 bg-white">
                  <div class="flex items-center">
                    <div class="flex-shrink-0 h-10 w-10">
                      <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                        <span class="text-sm font-medium text-gray-700">
                          <?php echo strtoupper(substr($user['name'], 0, 1) . substr($user['surname'], 0, 1)); ?>
                        </span>
                      </div>
                    </div>
                    <div class="ml-4">
                      <div class="text-sm font-medium text-gray-900">
                        <?php echo htmlspecialchars($user['name'] . ' ' . $user['surname']); ?>
                      </div>
                      <div class="text-sm text-gray-500">
                        <?php echo htmlspecialchars($user['email']); ?>
                      </div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <?php if ($user['is_verified']): ?>
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                      Verified
                    </span>
                  <?php else: ?>
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                      Unverified
                    </span>
                  <?php endif; ?>
                </td>
                <?php foreach ($permissionLabels as $key => $label): ?>
                  <td class="px-3 py-4 whitespace-nowrap text-center">
                    <input type="checkbox"
                      class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                      <?php echo $user[$key] ? 'checked' : ''; ?>
                      data-user-id="<?php echo $user['id']; ?>"
                      data-permission="<?php echo $key; ?>">
                  </td>
                <?php endforeach; ?>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <div class="flex justify-end space-x-2">
                    <button class="text-blue-600 hover:text-blue-900" title="Edit User">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                      </svg>
                    </button>
                    <button class="text-green-600 hover:text-green-900" title="Save Permissions">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Permission Legend -->
    <div class="bg-blue-50 rounded-lg p-4">
      <h3 class="text-sm font-medium text-blue-900 mb-2">Permission Descriptions</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm text-blue-800">
        <div><strong>Add Rooms:</strong> Can create new rooms</div>
        <div><strong>Verify Users:</strong> Can verify user accounts</div>
        <div><strong>Manage Reservations:</strong> Can view and modify all reservations</div>
        <div><strong>Manage Users:</strong> Can create, edit, and delete users</div>
        <div><strong>Manage Rooms:</strong> Can edit and delete rooms</div>
        <div><strong>Accept Reservations:</strong> Can approve or reject reservation requests</div>
      </div>
    </div>
  <?php endif; ?>
</div>

<script>
  // Handle permission checkbox changes
  document.addEventListener('change', function(e) {
    if (e.target.type === 'checkbox' && e.target.dataset.userId) {
      const userId = e.target.dataset.userId;
      const permission = e.target.dataset.permission;
      const isChecked = e.target.checked;

      // Send AJAX request to update the permission
      fetch('/api/permissions/update.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            user_id: userId,
            permission: permission,
            value: isChecked
          }),
          credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(result => {
          if (result.success) {
            // Show a temporary success message
            const row = e.target.closest('tr');
            row.style.backgroundColor = '#f0f9ff';
            setTimeout(() => {
              row.style.backgroundColor = '';
            }, 1000);
          } else {
            alert('Error: ' + (result.error || 'Unknown error'));
            e.target.checked = !isChecked; // Revert checkbox
          }
        })
        .catch(error => {
          alert('Network error: ' + error.message);
          e.target.checked = !isChecked; // Revert checkbox
        });
    }
  });
</script>