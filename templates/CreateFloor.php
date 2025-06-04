<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
if (!isset($_SESSION['user_id'])) {
  echo '<p class="text-red-600">You must be logged in to create a floor.</p>';
  return;
}

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/permissions.php';

// Check if user has permission to create floors
if (!canCreateFloors($_SESSION['user_id'])) {
  echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Access Denied</h1><p>You do not have permission to create floors.</p></div>';
  return;
}

try {
  $buildings = $pdo->query("SELECT id, name FROM buildings ORDER BY name")->fetchAll();
} catch (Exception $e) {
  $buildings = [];
}
?>

<form id="create-floor-form" class="space-y-6 max-w-2xl mx-auto p-6 bg-white rounded-md shadow-md">
  <div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-semibold text-gray-900">Create New Floor</h2>
    <button type="button" onclick="loadPage('Floors')" class="text-gray-600 hover:text-gray-800">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
      </svg>
    </button>
  </div>

  <div>
    <label for="building_id" class="block mb-1 font-medium text-gray-700">Building *</label>
    <select id="building_id" name="building_id" required
      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
      <option value="">Select a building</option>
      <?php foreach ($buildings as $building): ?>
        <option value="<?php echo $building['id']; ?>"><?php echo htmlspecialchars($building['name']); ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div>
    <label for="name" class="block mb-1 font-medium text-gray-700">Floor Name *</label>
    <input id="name" name="name" type="text" required
      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
      placeholder="Enter floor name (e.g., 1st Floor, Ground Floor, Basement)" />
  </div>

  <div>
    <label for="level_number" class="block mb-1 font-medium text-gray-700">Level Number</label>
    <input id="level_number" name="level_number" type="number"
      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
      placeholder="Enter level number (e.g., 1, 2, -1 for basement)" />
  </div>

  <div>
    <label for="description" class="block mb-1 font-medium text-gray-700">Description</label>
    <textarea id="description" name="description" rows="3"
      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
      placeholder="Describe the floor's purpose and features"></textarea>
  </div>

  <div class="flex justify-end space-x-4 pt-4 border-t border-gray-200">
    <button type="button" onclick="loadPage('Floors')"
      class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">Cancel</button>
    <button type="button" onclick="(async function() {
        const form = document.getElementById('create-floor-form');
        const formData = new FormData(form);
        
        // Convert FormData to JSON
        const data = {};
        for (let [key, value] of formData.entries()) {
          if (key === 'level_number' && value.trim()) {
            data[key] = parseInt(value);
          } else if (value.trim()) {
            data[key] = value.trim();
          }
        }
        
        // Validate required fields
        if (!data.building_id) {
          popupSystem.error('Please select a building.');
          return;
        }
        if (!data.name) {
          popupSystem.error('Please enter a floor name.');
          return;
        }
        
        try {
          const response = await fetch('/api/floors/create.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data),
            credentials: 'same-origin'
          });

          const result = await response.json();
          if (response.ok && result.floor_id) {
            popupSystem.success('Floor created successfully!');
            loadPage('Floors');
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
      })()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">Create Floor</button>
  </div>
</form>

<script>
  // Add real-time validation
  document.getElementById('building_id').addEventListener('change', function() {
    const value = this.value;
    const button = document.querySelector('button[onclick*="create.php"]');

    if (!value) {
      this.classList.add('border-red-300');
      button.disabled = true;
      button.classList.add('opacity-50', 'cursor-not-allowed');
    } else {
      this.classList.remove('border-red-300');
      checkFormValidity();
    }
  });

  document.getElementById('name').addEventListener('input', function() {
    const value = this.value.trim();
    if (value.length < 1) {
      this.classList.add('border-red-300');
    } else {
      this.classList.remove('border-red-300');
    }
    checkFormValidity();
  });

  document.getElementById('description').addEventListener('input', function() {
    const value = this.value.trim();
    if (value.length > 1000) {
      this.classList.add('border-red-300');
    } else {
      this.classList.remove('border-red-300');
    }
  });

  function checkFormValidity() {
    const building = document.getElementById('building_id').value;
    const name = document.getElementById('name').value.trim();
    const button = document.querySelector('button[onclick*="create.php"]');

    if (building && name.length >= 1) {
      button.disabled = false;
      button.classList.remove('opacity-50', 'cursor-not-allowed');
    } else {
      button.disabled = true;
      button.classList.add('opacity-50', 'cursor-not-allowed');
    }
  }

  // Initial check
  checkFormValidity();
</script>
