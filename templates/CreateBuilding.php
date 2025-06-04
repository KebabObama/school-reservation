<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
if (!isset($_SESSION['user_id'])) {
  echo '<p class="text-red-600">You must be logged in to create a building.</p>';
  return;
}

require_once __DIR__ . '/../lib/permissions.php';

// Check if user has permission to create buildings
if (!canCreateBuildings($_SESSION['user_id'])) {
  echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Access Denied</h1><p>You do not have permission to create buildings.</p></div>';
  return;
}
?>

<form id="create-building-form" class="space-y-6 max-w-2xl mx-auto p-6 bg-white rounded-md shadow-md">
  <div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-semibold text-gray-900">Create New Building</h2>
    <button type="button" onclick="loadPage('Buildings')" class="text-gray-600 hover:text-gray-800">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
      </svg>
    </button>
  </div>

  <div>
    <label for="name" class="block mb-1 font-medium text-gray-700">Building Name *</label>
    <input id="name" name="name" type="text" required
      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
      placeholder="Enter building name (e.g., Main Building, Science Center)" />
  </div>

  <div>
    <label for="description" class="block mb-1 font-medium text-gray-700">Description</label>
    <textarea id="description" name="description" rows="3"
      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
      placeholder="Describe the building's purpose and features"></textarea>
  </div>

  <div>
    <label for="address" class="block mb-1 font-medium text-gray-700">Address</label>
    <input id="address" name="address" type="text"
      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
      placeholder="Enter building address" />
  </div>

  <div class="flex justify-end space-x-4 pt-4 border-t border-gray-200">
    <button type="button" onclick="loadPage('Buildings')"
      class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">Cancel</button>
    <button type="button" onclick="(async function() {
        const form = document.getElementById('create-building-form');
        const formData = new FormData(form);
        
        // Convert FormData to JSON
        const data = {};
        for (let [key, value] of formData.entries()) {
          if (value.trim()) data[key] = value.trim();
        }
        
        // Validate required fields
        if (!data.name) {
          popupSystem.error('Please enter a building name.');
          return;
        }
        
        try {
          const response = await fetch('/api/buildings/create.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data),
            credentials: 'same-origin'
          });

          const result = await response.json();
          if (response.ok && result.building_id) {
            popupSystem.success('Building created successfully!');
            loadPage('Buildings');
          } else {
            if (result.errors) {
              popupSystem.error(result.errors.join('\\n'));
            } else {
              popupSystem.error(result.error || 'Unknown error occurred');
            }
          }
        } catch (error) {
          popupSystem.error('Network error: ' + error.message);
        }
      })()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">Create Building</button>
  </div>
</form>

<script>
  // Add real-time validation
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

  document.getElementById('description').addEventListener('input', function() {
    const value = this.value.trim();
    if (value.length > 1000) {
      this.classList.add('border-red-300');
    } else {
      this.classList.remove('border-red-300');
    }
  });

  document.getElementById('address').addEventListener('input', function() {
    const value = this.value.trim();
    if (value.length > 255) {
      this.classList.add('border-red-300');
    } else {
      this.classList.remove('border-red-300');
    }
  });
</script>
