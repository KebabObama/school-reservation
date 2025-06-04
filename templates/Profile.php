<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!isset($_SESSION['user_id'])) {
  header('Location: /');
  exit;
}

require_once __DIR__ . '/../lib/db.php';

// Get user information
try {
  $stmt = $pdo->prepare("
        SELECT u.*, p.can_add_room, p.can_verify_users, p.can_manage_reservations,
               p.can_manage_users, p.can_manage_rooms, p.can_accept_reservations
        FROM users u
        LEFT JOIN permissions p ON u.id = p.user_id
        WHERE u.id = ?
    ");
  $stmt->execute([$_SESSION['user_id']]);
  $user = $stmt->fetch();

  // Get user's reservation statistics
  $stmt = $pdo->prepare("
        SELECT
            COUNT(*) as total_reservations,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_reservations,
            SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted_reservations,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_reservations
        FROM reservations
        WHERE user_id = ?
    ");
  $stmt->execute([$_SESSION['user_id']]);
  $stats = $stmt->fetch();
} catch (Exception $e) {
  $user = null;
  $stats = ['total_reservations' => 0, 'pending_reservations' => 0, 'accepted_reservations' => 0, 'rejected_reservations' => 0];
}

if (!$user) {
  echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">User not found</h1></div>';
  exit;
}
?>

<div class="space-y-6">
  <!-- Header -->
  <div class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-lg p-6 text-white">
    <div class="flex items-center">
      <div class="h-20 w-20 rounded-full bg-white bg-opacity-20 flex items-center justify-center text-2xl font-bold">
        <?php echo strtoupper(substr($user['name'], 0, 1) . substr($user['surname'], 0, 1)); ?>
      </div>
      <div class="ml-6">
        <h1 class="text-3xl font-bold"><?php echo htmlspecialchars($user['name'] . ' ' . $user['surname']); ?></h1>
        <p class="text-blue-100"><?php echo htmlspecialchars($user['email']); ?></p>
        <div class="flex items-center mt-2">
          <?php if ($user['is_verified']): ?>
            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
              <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
              </svg>
              Verified
            </span>
          <?php else: ?>
            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">
              <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
              </svg>
              Unverified
            </span>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Statistics Cards -->
  <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
    <div class="bg-white rounded-lg shadow p-6">
      <div class="flex items-center">
        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
          </svg>
        </div>
        <div class="ml-4">
          <p class="text-sm font-medium text-gray-600">Total Reservations</p>
          <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['total_reservations']; ?></p>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
      <div class="flex items-center">
        <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
        </div>
        <div class="ml-4">
          <p class="text-sm font-medium text-gray-600">Pending</p>
          <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['pending_reservations']; ?></p>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
      <div class="flex items-center">
        <div class="p-3 rounded-full bg-green-100 text-green-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
          </svg>
        </div>
        <div class="ml-4">
          <p class="text-sm font-medium text-gray-600">Accepted</p>
          <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['accepted_reservations']; ?></p>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
      <div class="flex items-center">
        <div class="p-3 rounded-full bg-red-100 text-red-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </div>
        <div class="ml-4">
          <p class="text-sm font-medium text-gray-600">Rejected</p>
          <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['rejected_reservations']; ?></p>
        </div>
      </div>
    </div>
  </div>

  <!-- Profile Information and Permissions -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Profile Information -->
    <div class="bg-white rounded-lg shadow p-6">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-lg font-semibold text-gray-900">Profile Information</h2>
        <button onclick="editProfile()" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
          Edit Profile
        </button>
      </div>

      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">First Name</label>
          <div class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['name']); ?></div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Last Name</label>
          <div class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['surname']); ?></div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Email Address</label>
          <div class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['email']); ?></div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Account Status</label>
          <div class="mt-1">
            <?php if ($user['is_verified']): ?>
              <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                Verified Account
              </span>
            <?php else: ?>
              <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">
                Unverified Account
              </span>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Permissions -->
    <div class="bg-white rounded-lg shadow p-6">
      <h2 class="text-lg font-semibold text-gray-900 mb-6">Your Permissions</h2>

      <div class="space-y-3">
        <?php
        $permissions = [
          'can_add_room' => 'Add Rooms',
          'can_verify_users' => 'Verify Users',
          'can_manage_reservations' => 'Manage Reservations',
          'can_manage_users' => 'Manage Users',
          'can_manage_rooms' => 'Manage Rooms',
          'can_accept_reservations' => 'Accept Reservations'
        ];

        foreach ($permissions as $key => $label):
        ?>
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-700"><?php echo $label; ?></span>
            <?php if ($user[$key]): ?>
              <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                </svg>
                Granted
              </span>
            <?php else: ?>
              <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">
                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
                Denied
              </span>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Security Settings -->
  <div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-lg font-semibold text-gray-900 mb-6">Security Settings</h2>

    <div class="space-y-4">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="text-sm font-medium text-gray-900">Password</h3>
          <p class="text-sm text-gray-600">Last updated: Never</p>
        </div>
        <button onclick="changePassword()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">
          Change Password
        </button>
      </div>

      <div class="border-t pt-4">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-sm font-medium text-gray-900">Two-Factor Authentication</h3>
            <p class="text-sm text-gray-600">Add an extra layer of security to your account</p>
          </div>
          <button class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-sm">
            Enable 2FA
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  async function editProfile() {
    // Get current user data
    const currentName = '<?php echo addslashes($user['name']); ?>';
    const currentSurname = '<?php echo addslashes($user['surname']); ?>';
    const currentEmail = '<?php echo addslashes($user['email']); ?>';

    // Create a simple form dialog using multiple prompts
    try {
      const name = await popupSystem.prompt(
        'Enter your first name:',
        'Edit Profile - First Name',
        currentName, {
          required: true,
          minLength: 2,
          validator: (value) => {
            if (value.trim().length < 2) {
              return 'Name must be at least 2 characters long';
            }
            return true;
          }
        }
      );

      if (name === null) return; // User cancelled

      const surname = await popupSystem.prompt(
        'Enter your last name:',
        'Edit Profile - Last Name',
        currentSurname, {
          required: true,
          minLength: 2,
          validator: (value) => {
            if (value.trim().length < 2) {
              return 'Surname must be at least 2 characters long';
            }
            return true;
          }
        }
      );

      if (surname === null) return; // User cancelled

      const email = await popupSystem.prompt(
        'Enter your email address:',
        'Edit Profile - Email',
        currentEmail, {
          inputType: 'email',
          required: true,
          validator: (value) => {
            if (!value.includes('@')) {
              return 'Please enter a valid email address';
            }
            return true;
          }
        }
      );

      if (email === null) return; // User cancelled

      // Confirm changes
      const confirmed = await popupSystem.confirm(
        `Update profile with:\nName: ${name} ${surname}\nEmail: ${email}`,
        'Confirm Profile Update'
      );

      if (!confirmed) return;

      // Submit the changes
      try {
        const response = await fetch('/api/users/update-profile.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            name: name.trim(),
            surname: surname.trim(),
            email: email.trim()
          }),
          credentials: 'same-origin'
        });

        const result = await response.json();
        if (response.ok) {
          popupSystem.success('Profile updated successfully!');
          // Reload the page to show updated information
          setTimeout(() => {
            location.reload();
          }, 1500);
        } else {
          popupSystem.error(result.error || 'Failed to update profile');
        }
      } catch (error) {
        popupSystem.error('Network error: ' + error.message);
      }

    } catch (error) {
      popupSystem.error('Error updating profile: ' + error.message);
    }
  }

  async function changePassword() {
    try {
      const currentPassword = await popupSystem.prompt(
        'Enter your current password:',
        'Change Password - Current Password',
        '', {
          inputType: 'password',
          required: true,
          minLength: 1
        }
      );

      if (currentPassword === null) return; // User cancelled

      const newPassword = await popupSystem.prompt(
        'Enter your new password:',
        'Change Password - New Password',
        '', {
          inputType: 'password',
          required: true,
          minLength: 6,
          validator: (value) => {
            if (value.length < 6) {
              return 'Password must be at least 6 characters long';
            }
            if (!/[A-Z]/.test(value)) {
              return 'Password must contain at least one uppercase letter';
            }
            if (!/[0-9]/.test(value)) {
              return 'Password must contain at least one number';
            }
            return true;
          }
        }
      );

      if (newPassword === null) return; // User cancelled

      const confirmPassword = await popupSystem.prompt(
        'Confirm your new password:',
        'Change Password - Confirm Password',
        '', {
          inputType: 'password',
          required: true,
          validator: (value) => {
            if (value !== newPassword) {
              return 'Passwords do not match';
            }
            return true;
          }
        }
      );

      if (confirmPassword === null) return; // User cancelled

      // Submit the password change
      try {
        const response = await fetch('/api/users/update-profile.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            current_password: currentPassword,
            password: newPassword
          }),
          credentials: 'same-origin'
        });

        const result = await response.json();
        if (response.ok) {
          popupSystem.success('Password changed successfully!');
        } else {
          popupSystem.error(result.error || 'Failed to change password');
        }
      } catch (error) {
        popupSystem.error('Network error: ' + error.message);
      }

    } catch (error) {
      popupSystem.error('Error changing password: ' + error.message);
    }
  }
</script>