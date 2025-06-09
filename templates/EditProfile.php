<?php
if (session_status() === PHP_SESSION_NONE)
  session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: /');
  exit;
}
require_once __DIR__ . '/../lib/db.php';
try {
  $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
  $stmt->execute([$_SESSION['user_id']]);
  $user = $stmt->fetch();
} catch (Exception $e) {
  $user = null;
}
if (!$user) {
  echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">User not found</h1></div>';
  exit;
}
?>
<div class="max-w-2xl mx-auto space-y-6">
  <div class="bg-white rounded-lg shadow p-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Edit Profile</h1>
        <p class="text-gray-600">Update your personal information</p>
      </div>
      <button onclick="loadPage('Profile')" class="text-gray-500 hover:text-gray-700">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
      </button>
    </div>
  </div>
  <div class="bg-white rounded-lg shadow p-6">
    <form id="editProfileForm" method="POST" action="#" class="space-y-6">
      <input type="hidden" name="action" value="update_profile">
      <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($_SESSION['user_id']); ?>">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label for="name" class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
          <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            required minlength="2">
          <div id="nameError" class="text-red-600 text-sm mt-1 hidden"></div>
        </div>
        <div>
          <label for="surname" class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
          <input type="text" id="surname" name="surname" value="<?php echo htmlspecialchars($user['surname']); ?>"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            required minlength="2">
          <div id="surnameError" class="text-red-600 text-sm mt-1 hidden"></div>
        </div>
      </div>
      <div>
        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          required>
        <div id="emailError" class="text-red-600 text-sm mt-1 hidden"></div>
      </div>
      <div class="border-t pt-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Change Password (Optional)</h3>
        <div class="space-y-4">
          <div>
            <label for="currentPassword" class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
            <input type="password" id="currentPassword" name="current_password"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="Enter current password to change it" autocomplete="current-password">
            <div id="currentPasswordError" class="text-red-600 text-sm mt-1 hidden"></div>
          </div>
          <div>
            <label for="newPassword" class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
            <input type="password" id="newPassword" name="password"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="Enter new password" minlength="6" autocomplete="new-password">
            <div id="newPasswordError" class="text-red-600 text-sm mt-1 hidden"></div>
            <p class="text-sm text-gray-600 mt-1">Password must be at least 6 characters long, contain one uppercase
              letter and one number</p>
          </div>
          <div>
            <label for="confirmPassword" class="block text-sm font-medium text-gray-700 mb-2">Confirm New
              Password</label>
            <input type="password" id="confirmPassword" name="confirm_password"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="Confirm your new password" autocomplete="new-password">
            <div id="confirmPasswordError" class="text-red-600 text-sm mt-1 hidden"></div>
          </div>
        </div>
      </div>
      <div class="flex justify-end space-x-4 pt-6 border-t">
        <button type="button" onclick="loadPage('Profile')"
          class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 focus:ring-2 focus:ring-blue-500">
          Cancel
        </button>
        <button type="button" onclick="(async () => {
            const confirmed = await popupSystem.confirm(
              'Are you sure you want to save these changes to your profile?',
              'Confirm Profile Update',
              { confirmText: 'Save Changes', cancelText: 'Cancel', type: 'info' }
            );
            if (!confirmed) return;
            const form = document.getElementById('editProfileForm');
            document.querySelectorAll(`[id$='Error']`).forEach(el => {
              el.classList.add('hidden');
              el.textContent = '';
            });
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            let hasErrors = false;
            const showError = (id, msg) => {
              const el = document.getElementById(id);
              el.textContent = msg;
              el.classList.remove('hidden');
            };
            if (data.name.trim().length < 2) {
              showError('nameError', 'Name must be at least 2 characters long');
              hasErrors = true;
            }
            if (data.surname.trim().length < 2) {
              showError('surnameError', 'Surname must be at least 2 characters long');
              hasErrors = true;
            }
            if (!data.email.includes('@')) {
              showError('emailError', 'Please enter a valid email address');
              hasErrors = true;
            }
            if (data.password || data.current_password) {
              if (!data.current_password) {
                showError('currentPasswordError', 'Current password is required to change password');
                hasErrors = true;
              }
              if (data.password.length < 6) {
                showError('newPasswordError', 'Password must be at least 6 characters long');
                hasErrors = true;
              }
              if (!/[A-Z]/.test(data.password)) {
                showError('newPasswordError', 'Password must contain at least one uppercase letter');
                hasErrors = true;
              }
              if (!/[0-9]/.test(data.password)) {
                showError('newPasswordError', 'Password must contain at least one number');
                hasErrors = true;
              }
              if (data.password !== data.confirm_password) {
                showError('confirmPasswordError', 'Passwords do not match');
                hasErrors = true;
              }
            }
            if (hasErrors) return;
            const submitData = {
              name: data.name.trim(),
              surname: data.surname.trim(),
              email: data.email.trim()
            };
            if (data.current_password && data.password) {
              submitData.current_password = data.current_password;
              submitData.password = data.password;
            }
            try {
              const response = await fetch('/api/users/update-profile.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(submitData),
                credentials: 'same-origin'
              });
              const result = await response.json();
              if (response.ok) {
                popupSystem.success('Profile updated successfully!');
              } else {
                popupSystem.error(result.error || 'Failed to update profile');
              }
            } catch (error) {
              popupSystem.error('Network error: ' + error.message);
            }
        })()" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500">
          Save Changes
        </button>
      </div>
    </form>
  </div>
</div>