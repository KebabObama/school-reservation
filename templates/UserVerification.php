<?php
if (session_status() === PHP_SESSION_NONE)
  session_start();
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
          <span
            class="hidden sm:inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $user['is_verified'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
            <?php echo $user['is_verified'] ? 'Verified' : 'Unverified'; ?>
          </span>
          <button onclick="(async function() {
              const userId = <?php echo $user['id']; ?>;
              const currentStatus = <?php echo $user['is_verified'] ? 'true' : 'false'; ?>;
              const newStatus = !currentStatus;
              const actionText = newStatus ? 'verify' : 'unverify';
              const button = event.target.closest('button');
              const userRow = button.closest('.p-4');
              const statusBadge = userRow.querySelector('.inline-flex');
              const confirmed = await popupSystem.confirm(
                'Are you sure you want to ' + actionText + ' this user?',
                actionText.charAt(0).toUpperCase() + actionText.slice(1) + ' User',
                {
                  confirmText: actionText.charAt(0).toUpperCase() + actionText.slice(1),
                  cancelText: 'Cancel',
                  type: newStatus ? 'info' : 'warning'
                }
              );
              if (confirmed) {
                fetch('/api/users/verify.php', {
                  method: 'POST',
                  headers: {
                    'Content-Type': 'application/json'
                  },
                  body: JSON.stringify({
                    user_id: userId,
                    verified: newStatus
                  }),
                  credentials: 'same-origin'
                }).then(response => response.json())
                .then(result => {
                  if (result.success) {
                    popupSystem.success(result.message);
                    if (newStatus) {
                      statusBadge.className = 'inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800';
                      statusBadge.textContent = 'Verified';
                      button.className = 'bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm flex items-center';
                      button.innerHTML = `<svg class='w-4 h-4 mr-1' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z'></path></svg>Unverify`;
                    } else {
                      statusBadge.className = 'inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800';
                      statusBadge.textContent = 'Unverified';
                      button.className = 'bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm flex items-center';
                      button.innerHTML = `<svg class='w-4 h-4 mr-1' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'></path></svg>Verify`;
                    }
                    const newOnclick = button.getAttribute('onclick').replace(
                      'const currentStatus = ' + currentStatus + ';',
                      'const currentStatus = ' + newStatus + ';'
                    );
                    button.setAttribute('onclick', newOnclick);
                  } else {
                    popupSystem.error(result.error || 'Unknown error');
                  }
                }).catch(error => {
                  popupSystem.error('Network error: ' + error.message);
                });
              }
            })()"
            class="<?php echo $user['is_verified'] ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700'; ?> text-white px-3 py-1 rounded text-sm flex items-center">
            <?php if ($user['is_verified']): ?>
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Unverify
            <?php else: ?>
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Verify
            <?php endif; ?>
          </button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
      <div class="flex flex-col sm:gap-2 sm:flex-row sm:justify-between sm:items-center space-y-4 sm:space-y-0">
        <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2">
          <button id="toggleSelectButton" onclick="(function() {
              const checkboxes = document.querySelectorAll('.user-checkbox');
              const checkedCount = document.querySelectorAll('.user-checkbox:checked').length;
              const totalCount = checkboxes.length;
              const button = document.getElementById('toggleSelectButton');
              if (checkedCount === totalCount) {
                checkboxes.forEach(cb => cb.checked = false);
                button.textContent = 'Select All';
                button.className = 'bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm flex items-center justify-center';
              } else {
                checkboxes.forEach(cb => cb.checked = true);
                button.textContent = 'Select None';
                button.className = 'bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm flex items-center justify-center';
              }
            })()"
            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm flex items-center justify-center">
            Select All
          </button>
          <button onclick='
              document.querySelectorAll(".user-checkbox").forEach(cb => cb.checked = false);
              const allUsers = <?php echo json_encode($usersWithPermissions); ?>;
              allUsers.forEach(user => {
                if (!user.is_verified) {
                  const checkbox = document.getElementById("user-" + user.id);
                  if (checkbox) checkbox.checked = true;
                }
              });
              const toggleButton = document.getElementById("toggleSelectButton");
              const checkedCount = document.querySelectorAll(".user-checkbox:checked").length;
              const totalCount = document.querySelectorAll(".user-checkbox").length;
              if (checkedCount === totalCount) {
                toggleButton.textContent = "Select None";
                toggleButton.className = "bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm flex items-center justify-center";
              } else {
                toggleButton.textContent = "Select All";
                toggleButton.className = "bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm flex items-center justify-center";
              }
            '
            class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg text-sm flex items-center justify-center">
            Select Unverified
          </button>
        </div>
        <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2">
          <button
            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm flex items-center justify-center"
            onclick="(async function() {
              const selectedUsers = [];
              const selectedCheckboxes = [];
              document.querySelectorAll('.user-checkbox:checked').forEach(cb => {
                const userId = cb.id.replace('user-', '');
                selectedUsers.push(userId);
                selectedCheckboxes.push(cb);
              });
              if (selectedUsers.length === 0) {
                popupSystem.error('Please select at least one user');
                return;
              }
              const confirmed = await popupSystem.confirm(
                'Are you sure you want to verify ' + selectedUsers.length + ' user(s)?',
                'Bulk Verify Users',
                {
                  confirmText: 'Verify All',
                  cancelText: 'Cancel',
                  type: 'info'
                }
              );
              if (confirmed) {
                popupSystem.info('Verifying ' + selectedUsers.length + ' user(s)...');
                Promise.all(selectedUsers.map(userId =>
                  fetch('/api/users/verify.php', {
                    method: 'POST',
                    headers: {
                      'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                      user_id: userId,
                      verified: true
                    }),
                    credentials: 'same-origin'
                  }).then(response => response.json())
                )).then(results => {
                  const successful = results.filter(r => r.success).length;
                  const failed = results.length - successful;
                  if (successful > 0) {
                    popupSystem.success('Successfully verified ' + successful + ' user(s)');
                    selectedCheckboxes.forEach((cb, index) => {
                      if (results[index] && results[index].success) {
                        const userRow = cb.closest('.p-4');
                        const statusBadge = userRow.querySelector('.inline-flex');
                        const actionButton = userRow.querySelector(`button[onclick*='currentStatus']`);
              statusBadge.className='inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800' ;
              statusBadge.textContent='Verified' ;
              actionButton.className='bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm flex items-center' ;
              actionButton.innerHTML=`<svg class='w-4 h-4 mr-1' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
              <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z'></path></svg>Unverify`;
              const newOnclick = actionButton.getAttribute('onclick').replace('const currentStatus = false;', 'const currentStatus = true;');
              actionButton.setAttribute('onclick', newOnclick);
              cb.checked = false;
              }
              });
              const toggleButton = document.getElementById('toggleSelectButton');
              const checkedCount = document.querySelectorAll('.user-checkbox:checked').length;
              const totalCount = document.querySelectorAll('.user-checkbox').length;
              if (checkedCount === totalCount) {
                toggleButton.textContent = 'Select None';
                toggleButton.className = 'bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm flex items-center justify-center';
              } else {
                toggleButton.textContent = 'Select All';
                toggleButton.className = 'bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm flex items-center justify-center';
              }
              }
              if (failed > 0) {
              popupSystem.error('Failed to verify ' + failed + ' user(s)');
              }
              }).catch(error => {
              popupSystem.error('Network error: ' + error.message);
              });
              }
              })()">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Verify Selected
          </button>
          <button onclick="(async function() {
              const selectedUsers = [];
              const selectedCheckboxes = [];
              document.querySelectorAll('.user-checkbox:checked').forEach(cb => {
                const userId = cb.id.replace('user-', '');
                selectedUsers.push(userId);
                selectedCheckboxes.push(cb);
              });
              if (selectedUsers.length === 0) {
                popupSystem.error('Please select at least one user');
                return;
              }
              const confirmed = await popupSystem.confirm(
                'Are you sure you want to unverify ' + selectedUsers.length + ' user(s)?',
                'Bulk Unverify Users',
                {
                  confirmText: 'Unverify All',
                  cancelText: 'Cancel',
                  type: 'warning'
                }
              );
              if (confirmed) {
                popupSystem.info('Unverifying ' + selectedUsers.length + ' user(s)...');
                Promise.all(selectedUsers.map(userId =>
                  fetch('/api/users/verify.php', {
                    method: 'POST',
                    headers: {
                      'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                      user_id: userId,
                      verified: false
                    }),
                    credentials: 'same-origin'
                  }).then(response => response.json())
                )).then(results => {
                  const successful = results.filter(r => r.success).length;
                  const failed = results.length - successful;
                  if (successful > 0) {
                    popupSystem.success('Successfully unverified ' + successful + ' user(s)');
                    selectedCheckboxes.forEach((cb, index) => {
                      if (results[index] && results[index].success) {
                        const userRow = cb.closest('.p-4');
                        const statusBadge = userRow.querySelector('.inline-flex');
                        const actionButton = userRow.querySelector(`button[onclick*='currentStatus']`);
              statusBadge.className='inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800' ;
              statusBadge.textContent='Unverified' ;
              actionButton.className='bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm flex items-center' ;
              actionButton.innerHTML=`<svg class='w-4 h-4 mr-1' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
              <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'></path></svg>Verify`;
              const newOnclick = actionButton.getAttribute('onclick').replace('const currentStatus = true;', 'const currentStatus = false;');
              actionButton.setAttribute('onclick', newOnclick);
              cb.checked = false;
              }
              });
              const toggleButton = document.getElementById('toggleSelectButton');
              const checkedCount = document.querySelectorAll('.user-checkbox:checked').length;
              const totalCount = document.querySelectorAll('.user-checkbox').length;
              if (checkedCount === totalCount) {
                toggleButton.textContent = 'Select None';
                toggleButton.className = 'bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm flex items-center justify-center';
              } else {
                toggleButton.textContent = 'Select All';
                toggleButton.className = 'bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm flex items-center justify-center';
              }
              }
              if (failed > 0) {
              popupSystem.error('Failed to unverify ' + failed + ' user(s)');
              }
              }).catch(error => {
              popupSystem.error('Network error: ' + error.message);
              });
              }
              })()"
            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm flex items-center justify-center">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Unverify Selected
          </button>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>