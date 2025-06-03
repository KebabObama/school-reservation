<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
if (!isset($_SESSION['user_id'])) {
  echo '<p class="text-red-600">You must be logged in to create a purpose.</p>';
  return;
}

require_once __DIR__ . '/../lib/db.php';

// Check if user has permission to manage purposes (assuming admin-level permission)
try {
  $stmt = $pdo->prepare("SELECT can_manage_users FROM permissions WHERE user_id = ?");
  $stmt->execute([$_SESSION['user_id']]);
  $canManage = $stmt->fetchColumn();
  
  if (!$canManage) {
    echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Access Denied</h1><p>You do not have permission to create reservation purposes.</p></div>';
    return;
  }
} catch (Exception $e) {
  echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Error</h1><p>Unable to verify permissions.</p></div>';
  return;
}
?>

<form id="create-purpose-form" class="space-y-6 max-w-2xl mx-auto p-6 bg-white rounded-md shadow-md">
  <h2 class="text-2xl font-semibold mb-4 text-gray-900">Create New Reservation Purpose</h2>

  <div>
    <label for="name" class="block mb-1 font-medium text-gray-700">Purpose Name *</label>
    <input id="name" name="name" type="text" required
      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
      placeholder="Enter purpose name (e.g., Team Meeting, Training)" />
  </div>

  <div>
    <label for="description" class="block mb-1 font-medium text-gray-700">Description</label>
    <textarea id="description" name="description" rows="3"
      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
      placeholder="Enter a description of this purpose"></textarea>
  </div>

  <div class="flex items-center">
    <input id="requires_approval" name="requires_approval" type="checkbox" checked
      class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" />
    <label for="requires_approval" class="ml-2 block text-sm text-gray-900">
      Requires Approval
    </label>
    <div class="ml-2">
      <span class="text-xs text-gray-500">(Reservations with this purpose will need admin approval)</span>
    </div>
  </div>

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
            <li>Enable "Requires Approval" for formal meetings or events</li>
            <li>Disable approval for casual purposes like study sessions</li>
            <li>Purposes help categorize and manage reservations effectively</li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <div class="flex justify-end space-x-4 pt-4 border-t border-gray-200">
    <button type="button" onclick="loadPage('ReservationPurposes')"
      class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">Cancel</button>
    <button type="button" onclick="(async function() {
        const form = document.getElementById('create-purpose-form');
        const formData = new FormData(form);
        
        // Convert FormData to JSON
        const data = {};
        for (let [key, value] of formData.entries()) {
          data[key] = value;
        }
        
        // Handle checkbox
        data.requires_approval = document.getElementById('requires_approval').checked;
        
        try {
          const response = await fetch('/api/purposes/create.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data),
            credentials: 'same-origin'
          });
          
          const result = await response.json();
          if (response.ok && result.purpose_id) {
            alert('Purpose created successfully!');
            loadPage('ReservationPurposes');
          } else {
            alert('Error: ' + (result.error || 'Unknown error'));
          }
        } catch (error) {
          alert('Network error: ' + error.message);
        }
      })()"
      class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">Create Purpose</button>
  </div>
</form>

<script>
// Add some basic validation
document.getElementById('name').addEventListener('input', function() {
  const value = this.value.trim();
  const button = document.querySelector('button[onclick*="create.php"]');
  
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
</script>
