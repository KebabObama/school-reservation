<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
if (!isset($_SESSION['user_id'])) {
  echo '<p class="text-red-600">You must be logged in to edit a purpose.</p>';
  return;
}

require_once __DIR__ . '/../lib/db.php';

// Check if user has permission to manage purposes
try {
  $stmt = $pdo->prepare("SELECT can_manage_users FROM permissions WHERE user_id = ?");
  $stmt->execute([$_SESSION['user_id']]);
  $canManage = $stmt->fetchColumn();
  
  if (!$canManage) {
    echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Access Denied</h1><p>You do not have permission to edit reservation purposes.</p></div>';
    return;
  }
} catch (Exception $e) {
  echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Error</h1><p>Unable to verify permissions.</p></div>';
  return;
}

// Get purpose ID from URL parameter
$purposeId = $_GET['id'] ?? null;
if (!$purposeId) {
  echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Error</h1><p>Purpose ID is required.</p></div>';
  return;
}

try {
  // Get purpose data
  $stmt = $pdo->prepare("SELECT * FROM reservation_purposes WHERE id = ?");
  $stmt->execute([$purposeId]);
  $purpose = $stmt->fetch();
  
  if (!$purpose) {
    echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Error</h1><p>Purpose not found.</p></div>';
    return;
  }
  
  // Get usage count
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE purpose_id = ?");
  $stmt->execute([$purposeId]);
  $usageCount = $stmt->fetchColumn();
} catch (Exception $e) {
  echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Error</h1><p>Unable to load purpose data.</p></div>';
  return;
}
?>

<form id="edit-purpose-form" class="space-y-6 max-w-2xl mx-auto p-6 bg-white rounded-md shadow-md">
  <div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-semibold text-gray-900">Edit Purpose: <?php echo htmlspecialchars($purpose['name']); ?></h2>
    <button type="button" onclick="loadPage('ReservationPurposes')" class="text-gray-600 hover:text-gray-800">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
      </svg>
    </button>
  </div>

  <input type="hidden" id="purpose_id" value="<?php echo $purpose['id']; ?>">

  <!-- Usage Info -->
  <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
    <div class="flex items-center">
      <svg class="h-5 w-5 text-blue-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
      </svg>
      <span class="text-sm text-blue-800">
        This purpose is currently used by <strong><?php echo $usageCount; ?></strong> reservation<?php echo $usageCount !== 1 ? 's' : ''; ?>
      </span>
    </div>
  </div>

  <div>
    <label for="name" class="block mb-1 font-medium text-gray-700">Purpose Name *</label>
    <input id="name" name="name" type="text" required
      value="<?php echo htmlspecialchars($purpose['name']); ?>"
      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
      placeholder="Enter purpose name (e.g., Team Meeting, Training)" />
  </div>

  <div>
    <label for="description" class="block mb-1 font-medium text-gray-700">Description *</label>
    <textarea id="description" name="description" rows="4" required
      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
      placeholder="Enter a detailed description of this purpose"><?php echo htmlspecialchars($purpose['description'] ?? ''); ?></textarea>
  </div>

  <div class="flex items-center">
    <input id="requires_approval" name="requires_approval" type="checkbox" 
      <?php echo $purpose['requires_approval'] ? 'checked' : ''; ?>
      class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" />
    <label for="requires_approval" class="ml-2 block text-sm text-gray-900">
      Requires Approval
    </label>
    <div class="ml-2">
      <span class="text-xs text-gray-500">(Reservations with this purpose will need admin approval)</span>
    </div>
  </div>

  <!-- Impact Warning -->
  <?php if ($usageCount > 0): ?>
  <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
    <div class="flex">
      <div class="flex-shrink-0">
        <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
        </svg>
      </div>
      <div class="ml-3">
        <h3 class="text-sm font-medium text-yellow-800">Important Notice</h3>
        <div class="mt-2 text-sm text-yellow-700">
          <p>Changes to this purpose will affect all <?php echo $usageCount; ?> reservation<?php echo $usageCount !== 1 ? 's' : ''; ?> that use this purpose. If you change the approval requirement, it will apply to future reservations only.</p>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Guidelines -->
  <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
    <div class="flex">
      <div class="flex-shrink-0">
        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
        </svg>
      </div>
      <div class="ml-3">
        <h3 class="text-sm font-medium text-blue-800">Purpose Guidelines</h3>
        <div class="mt-2 text-sm text-blue-700">
          <ul class="list-disc pl-5 space-y-1">
            <li>Choose descriptive names that clearly indicate the purpose</li>
            <li>Provide detailed descriptions to help users understand when to use this purpose</li>
            <li>Enable "Requires Approval" for formal meetings or events that need oversight</li>
            <li>Disable approval for casual purposes like study sessions or informal meetings</li>
            <li>Consider the impact on existing reservations when making changes</li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <!-- Example Purposes -->
  <div class="bg-gray-50 border border-gray-200 rounded-md p-4">
    <h3 class="text-sm font-medium text-gray-800 mb-2">Example Purpose Types</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
      <div>
        <span class="font-medium text-green-600">✓ Approval Required:</span>
        <ul class="ml-4 text-gray-600">
          <li>• Board Meetings</li>
          <li>• Client Presentations</li>
          <li>• Training Sessions</li>
          <li>• Interviews</li>
        </ul>
      </div>
      <div>
        <span class="font-medium text-blue-600">✓ No Approval Needed:</span>
        <ul class="ml-4 text-gray-600">
          <li>• Study Sessions</li>
          <li>• Team Standups</li>
          <li>• Informal Meetings</li>
          <li>• Break Room Usage</li>
        </ul>
      </div>
    </div>
  </div>

  <div class="flex justify-end space-x-4 pt-4 border-t border-gray-200">
    <button type="button" onclick="loadPage('ReservationPurposes')"
      class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">Cancel</button>
    <button type="button" onclick="(async function() {
        const form = document.getElementById('edit-purpose-form');
        const formData = new FormData(form);
        
        // Convert FormData to JSON
        const data = { id: document.getElementById('purpose_id').value };
        for (let [key, value] of formData.entries()) {
          data[key] = value;
        }
        
        // Handle checkbox
        data.requires_approval = document.getElementById('requires_approval').checked;
        
        try {
          const response = await fetch('/api/purposes/edit.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data),
            credentials: 'same-origin'
          });
          
          const result = await response.json();
          if (response.ok) {
            alert('Purpose updated successfully!');
            loadPage('ReservationPurposes');
          } else {
            alert('Error: ' + (result.error || 'Unknown error'));
          }
        } catch (error) {
          alert('Network error: ' + error.message);
        }
      })()"
      class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">Update Purpose</button>
  </div>
</form>

<script>
// Add validation
document.getElementById('name').addEventListener('input', function() {
  const value = this.value.trim();
  const button = document.querySelector('button[onclick*="edit.php"]');
  
  if (value.length < 2) {
    this.classList.add('border-red-300');
    button.disabled = true;
    button.classList.add('opacity-50', 'cursor-not-allowed');
  } else {
    this.classList.remove('border-red-300');
    button.disabled = false;
    button.classList.remove('opacity-50', 'cursor-not-allowed');
  }
});

document.getElementById('description').addEventListener('input', function() {
  const value = this.value.trim();
  if (value.length < 10) {
    this.classList.add('border-red-300');
  } else {
    this.classList.remove('border-red-300');
  }
});
</script>
