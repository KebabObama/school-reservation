<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
if (!isset($_SESSION['user_id'])) {
  echo '<p class="text-red-600">You must be logged in to edit a building.</p>';
  return;
}
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/permissions.php';
if (!canEditBuildings($_SESSION['user_id'])) {
  echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Access Denied</h1><p>You do not have permission to edit buildings.</p></div>';
  return;
}
$buildingId = $_GET['id'] ?? null;
if (!$buildingId) {
  echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Error</h1><p>Building ID is required.</p></div>';
  return;
}
try {
  $stmt = $pdo->prepare("SELECT * FROM buildings WHERE id = ?");
  $stmt->execute([$buildingId]);
  $building = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$building) {
    echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Error</h1><p>Building not found.</p></div>';
    return;
  }
} catch (Exception $e) {
  echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Error</h1><p>Failed to load building data.</p></div>';
  return;
}
?>
<form id="edit-building-form" class="space-y-6 max-w-2xl mx-auto p-6 bg-white rounded-md shadow-md">
  <div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-semibold text-gray-900">Edit Building: <?php echo htmlspecialchars($building['name']); ?></h2>
    <button type="button" onclick="loadPage('Buildings')" class="text-gray-600 hover:text-gray-800">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
      </svg>
    </button>
  </div>
  <input type="hidden" id="building_id" value="<?php echo $building['id']; ?>">
  <div>
    <label for="name" class="block mb-1 font-medium text-gray-700">Building Name *</label>
    <input id="name" name="name" type="text" required
      value="<?php echo htmlspecialchars($building['name']); ?>"
      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
      placeholder="Enter building name" />
  </div>
  <div>
    <label for="description" class="block mb-1 font-medium text-gray-700">Description</label>
    <textarea id="description" name="description" rows="3"
      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
      placeholder="Describe the building's purpose and features"><?php echo htmlspecialchars($building['description'] ?? ''); ?></textarea>
  </div>
  <div>
    <label for="address" class="block mb-1 font-medium text-gray-700">Address</label>
    <input id="address" name="address" type="text"
      value="<?php echo htmlspecialchars($building['address'] ?? ''); ?>"
      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
      placeholder="Enter building address" />
  </div>
  <div class="flex justify-end space-x-4 pt-4 border-t border-gray-200">
    <button type="button" onclick="loadPage('Buildings')"
      class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">Cancel</button>
    <button type="button" onclick="(async function() {
        const form = document.getElementById('edit-building-form');
        const formData = new FormData(form);
        const data = { id: document.getElementById('building_id').value };
        for (let [key, value] of formData.entries()) {
          if (value !== null) {
            data[key] = value.trim();
          }
        }
        try {
          const response = await fetch('/api/buildings/edit.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data),
            credentials: 'same-origin'
          });
          const result = await response.json();
          if (response.ok) {
            popupSystem.success('Building updated successfully!');
            loadPage('Buildings');
          } else {
            popupSystem.error(result.error || 'Unknown error');
          }
        } catch (error) {
          popupSystem.error('Network error: ' + error.message);
        }
      })()"
      class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">Update Building</button>
  </div>
</form>
<script>
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