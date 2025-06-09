<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
if (!isset($_SESSION['user_id'])) {
  echo '<p class="text-red-600">You must be logged in to edit a floor.</p>';
  return;
}
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/permissions.php';
if (!canEditFloors($_SESSION['user_id'])) {
  echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Access Denied</h1><p>You do not have permission to edit floors.</p></div>';
  return;
}
$floorId = $_GET['id'] ?? null;
if (!$floorId) {
  echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Error</h1><p>Floor ID is required.</p></div>';
  return;
}
try {
  $stmt = $pdo->prepare("
    SELECT f.*, b.name as building_name 
    FROM floors f 
    LEFT JOIN buildings b ON f.building_id = b.id 
    WHERE f.id = ?
  ");
  $stmt->execute([$floorId]);
  $floor = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$floor) {
    echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Error</h1><p>Floor not found.</p></div>';
    return;
  }
  $buildings = $pdo->query("SELECT id, name FROM buildings ORDER BY name")->fetchAll();
} catch (Exception $e) {
  echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Error</h1><p>Failed to load floor data.</p></div>';
  return;
}
?>
<form id="edit-floor-form" class="space-y-6 max-w-2xl mx-auto p-6 bg-white rounded-md shadow-md">
  <div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-semibold text-gray-900">Edit Floor: <?php echo htmlspecialchars($floor['name']); ?></h2>
    <button type="button" onclick="loadPage('Floors')" class="text-gray-600 hover:text-gray-800">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
      </svg>
    </button>
  </div>
  <input type="hidden" id="floor_id" value="<?php echo $floor['id']; ?>">
  <div>
    <label for="building_id" class="block mb-1 font-medium text-gray-700">Building *</label>
    <select id="building_id" name="building_id" required
      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
      <option value="">Select a building</option>
      <?php foreach ($buildings as $building): ?>
        <option value="<?php echo $building['id']; ?>" 
                <?php echo $building['id'] == $floor['building_id'] ? 'selected' : ''; ?>>
          <?php echo htmlspecialchars($building['name']); ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div>
    <label for="name" class="block mb-1 font-medium text-gray-700">Floor Name *</label>
    <input id="name" name="name" type="text" required
      value="<?php echo htmlspecialchars($floor['name']); ?>"
      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
      placeholder="Enter floor name" />
  </div>
  <div>
    <label for="level_number" class="block mb-1 font-medium text-gray-700">Level Number</label>
    <input id="level_number" name="level_number" type="number"
      value="<?php echo $floor['level_number'] ?? ''; ?>"
      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
      placeholder="Enter level number" />
  </div>
  <div>
    <label for="description" class="block mb-1 font-medium text-gray-700">Description</label>
    <textarea id="description" name="description" rows="3"
      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
      placeholder="Describe the floor's purpose and features"><?php echo htmlspecialchars($floor['description'] ?? ''); ?></textarea>
  </div>
  <div class="flex justify-end space-x-4 pt-4 border-t border-gray-200">
    <button type="button" onclick="loadPage('Floors')"
      class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">Cancel</button>
    <button type="button" onclick="(async function() {
        const form = document.getElementById('edit-floor-form');
        const formData = new FormData(form);
        const data = { id: document.getElementById('floor_id').value };
        for (let [key, value] of formData.entries()) {
          if (key === 'level_number') {
            data[key] = value.trim() ? parseInt(value) : null;
          } else if (value !== null) {
            data[key] = value.trim();
          }
        }
        try {
          const response = await fetch('/api/floors/edit.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data),
            credentials: 'same-origin'
          });
          const result = await response.json();
          if (response.ok) {
            popupSystem.success('Floor updated successfully!');
            loadPage('Floors');
          } else {
            popupSystem.error(result.error || 'Unknown error');
          }
        } catch (error) {
          popupSystem.error('Network error: ' + error.message);
        }
      })()"
      class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">Update Floor</button>
  </div>
</form>
<script>
  document.getElementById('building_id').addEventListener('change', function() {
    const value = this.value;
    if (!value) {
      this.classList.add('border-red-300');
    } else {
      this.classList.remove('border-red-300');
    }
    checkFormValidity();
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
    const button = document.querySelector('button[onclick*="edit.php"]');
    if (building && name.length >= 1) {
      button.disabled = false;
      button.classList.remove('opacity-50', 'cursor-not-allowed');
    } else {
      button.disabled = true;
      button.classList.add('opacity-50', 'cursor-not-allowed');
    }
  }
  checkFormValidity();
</script>