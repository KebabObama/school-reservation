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

// Get users with their permissions
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
?>

<div class="space-y-6">
  <!-- Header -->
  <div class="flex justify-between items-center">
    <div>
      <h1 class="text-3xl font-bold text-gray-900">User Permissions</h1>
      <p class="text-gray-600">Manage user access rights and permissions</p>
    </div>
    <div class="flex space-x-2">
      <button onclick="loadPage('PermissionChanges')"
        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
          </path>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z">
          </path>
        </svg>
        Advanced Management
      </button>
    </div>
  </div>

  <!-- Permissions Table -->
  <?php if (empty($usersWithPermissions)): ?>
  <div class="bg-white rounded-lg shadow p-8 text-center">
    <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z">
      </path>
    </svg>
    <h3 class="text-lg font-medium text-gray-900 mb-2">No users found</h3>
    <p class="text-gray-600">No users available to manage permissions for.</p>
  </div>
  <?php else: ?>
  <div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto cursor-grab active:cursor-grabbing" id="permissions-table-container">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-0 bg-gray-50 min-w-[280px]">
              User
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[100px]">
              Status
            </th>
            <?php foreach ($permissionCategories as $categoryName => $permissions): ?>
            <th
              class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-l border-gray-200 min-w-[<?php echo count($permissions) * 80; ?>px]"
              colspan="<?php echo count($permissions); ?>">
              <?php echo ucfirst($categoryName); ?>
            </th>
            <?php endforeach; ?>
          </tr>
          <tr class="bg-gray-100">
            <th class="px-6 py-2 bg-white tracking-wider"></th>
            <th class="px-6 py-2 bg-white tracking-wider  "></th>
            <?php foreach ($permissionCategories as $categoryName => $permissions): ?>
            <?php foreach ($permissions as $permKey => $permLabel): ?>
            <th class="px-2 py-2 text-center text-xs font-medium text-gray-600 uppercase tracking-wider min-w-[80px]">
              <div class="truncate" title="<?php echo str_replace(ucfirst($categoryName) . ' ', '', $permLabel); ?>">
                <?php echo str_replace(ucfirst($categoryName) . ' ', '', $permLabel); ?>
              </div>
            </th>
            <?php endforeach; ?>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <?php foreach ($usersWithPermissions as $user): ?>
          <tr class="hover:bg-gray-50">
            <td class="px-6 py-4 sticky left-0 bg-white min-w-[280px]">
              <div class="flex items-center">
                <div class="flex-shrink-0 h-10 w-10">
                  <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                    <span class="text-sm font-medium text-gray-700">
                      <?php echo strtoupper(substr($user['name'], 0, 1) . substr($user['surname'], 0, 1)); ?>
                    </span>
                  </div>
                </div>
                <div class="ml-4 min-w-0 flex-1">
                  <div class="text-sm font-medium text-gray-900 truncate"
                    title="<?php echo htmlspecialchars($user['name'] . ' ' . $user['surname']); ?>">
                    <?php echo htmlspecialchars($user['name'] . ' ' . $user['surname']); ?>
                  </div>
                  <div class="text-sm text-gray-500 truncate" title="<?php echo htmlspecialchars($user['email']); ?>">
                    <?php echo htmlspecialchars($user['email']); ?>
                  </div>
                </div>
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap min-w-[100px]">
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
            <?php foreach ($permissionCategories as $categoryName => $permissions): ?>
            <?php foreach ($permissions as $permKey => $permLabel): ?>
            <td class="px-2 py-4 whitespace-nowrap text-center min-w-[80px]">
              <button type="button"
                class="permission-toggle inline-flex items-center justify-center w-6 h-6 rounded-full transition-colors duration-200 hover:scale-110 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1"
                data-user-id="<?php echo $user['id']; ?>" data-permission="<?php echo $permKey; ?>"
                data-has-permission="<?php echo ($user[$permKey] ?? false) ? 'true' : 'false'; ?>"
                title="<?php echo $permLabel; ?>">
                <?php if ($user[$permKey] ?? false): ?>
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <?php else: ?>
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                <?php endif; ?>
              </button>
            </td>
            <?php endforeach; ?>
            <?php endforeach; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Permission Legend -->
  <div class="bg-blue-50 rounded-lg p-4">
    <h3 class="text-sm font-medium text-blue-900 mb-3">Permission Categories</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <?php foreach ($permissionCategories as $categoryName => $permissions): ?>
      <div class="bg-white rounded-lg p-3 border border-blue-200">
        <h4 class="font-medium text-blue-900 mb-2"><?php echo ucfirst($categoryName); ?></h4>
        <ul class="text-sm text-blue-800 space-y-1">
          <?php foreach ($permissions as $permKey => $permLabel): ?>
          <li><strong><?php echo str_replace(ucfirst($categoryName) . ' ', '', $permLabel); ?>:</strong>
            <?php
                  $descriptions = [
                    'view' => 'Can view and list items',
                    'create' => 'Can create new items',
                    'edit' => 'Can modify existing items',
                    'delete' => 'Can remove items',
                    'review_status' => 'Can change status/approval'
                  ];
                  $action = explode('_', $permKey)[1] ?? '';
                  echo $descriptions[$action] ?? 'Permission access';
                  ?>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<script>
// Handle permission toggle clicks
document.addEventListener('click', function(e) {
  if (e.target.closest('.permission-toggle')) {
    const button = e.target.closest('.permission-toggle');
    const userId = button.dataset.userId;
    const permission = button.dataset.permission;
    const currentValue = button.dataset.hasPermission === 'true';
    const newValue = !currentValue;

    // Update the button immediately for responsive UI
    updatePermissionButton(button, newValue);

    // Send AJAX request to update the permission
    fetch('/api/permissions/update.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          user_id: userId,
          permission: permission,
          value: newValue
        }),
        credentials: 'same-origin'
      })
      .then(response => response.json())
      .then(result => {
        if (result.success) {
          // Show a temporary success message
          const row = button.closest('tr');
          const originalBg = row.style.backgroundColor;
          row.style.backgroundColor = '#f0f9ff';
          setTimeout(() => {
            row.style.backgroundColor = originalBg;
          }, 1000);
        } else {
          popupSystem.error(result.error || 'Unknown error');
          // Revert the button state
          updatePermissionButton(button, currentValue);
        }
      })
      .catch(error => {
        popupSystem.error('Network error: ' + error.message);
        // Revert the button state
        updatePermissionButton(button, currentValue);
      });
  }
});

// Function to update permission button appearance
function updatePermissionButton(button, hasPermission) {
  button.dataset.hasPermission = hasPermission ? 'true' : 'false';

  if (hasPermission) {
    button.innerHTML = `
        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
      `;
  } else {
    button.innerHTML = `
        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
      `;
  }
}

// Drag to scroll functionality for the permissions table
(function() {
  const container = document.getElementById('permissions-table-container');
  if (!container) return;

  let isDown = false;
  let startX;
  let scrollLeft;

  container.addEventListener('mousedown', (e) => {
    // Don't start dragging if clicking on a button or interactive element
    if (e.target.closest('.permission-toggle') || e.target.closest('button')) {
      return;
    }

    isDown = true;
    container.classList.add('active:cursor-grabbing');
    startX = e.pageX - container.offsetLeft;
    scrollLeft = container.scrollLeft;
    e.preventDefault(); // Prevent text selection
  });

  container.addEventListener('mouseleave', () => {
    isDown = false;
    container.classList.remove('active:cursor-grabbing');
  });

  container.addEventListener('mouseup', () => {
    isDown = false;
    container.classList.remove('active:cursor-grabbing');
  });

  container.addEventListener('mousemove', (e) => {
    if (!isDown) return;
    e.preventDefault();
    const x = e.pageX - container.offsetLeft;
    const walk = (x - startX) * 2; // Scroll speed multiplier
    container.scrollLeft = scrollLeft - walk;
  });

  // Touch support for mobile devices
  container.addEventListener('touchstart', (e) => {
    if (e.target.closest('.permission-toggle') || e.target.closest('button')) {
      return;
    }

    isDown = true;
    startX = e.touches[0].pageX - container.offsetLeft;
    scrollLeft = container.scrollLeft;
  });

  container.addEventListener('touchend', () => {
    isDown = false;
  });

  container.addEventListener('touchmove', (e) => {
    if (!isDown) return;
    e.preventDefault();
    const x = e.touches[0].pageX - container.offsetLeft;
    const walk = (x - startX) * 2;
    container.scrollLeft = scrollLeft - walk;
  });
})();
</script>