<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
if (!isset($_SESSION['user_id'])) {
  echo '<p class="text-red-600">You must be logged in to create a room.</p>';
  return;
}
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/permissions.php';
if (!hasPermission($_SESSION['user_id'], 'rooms_create')) {
  echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Access Denied</h1><p>You do not have permission to create rooms.</p></div>';
  return;
}
try {
  $roomTypes = $pdo->query("SELECT id, name FROM room_types ORDER BY name")->fetchAll();
  $locations = $pdo->query("
    SELECT f.id as floor_id,
           f.name as floor_name,
           f.level_number,
           b.id as building_id,
           b.name as building_name,
           CONCAT(b.name, ', ', f.name) as display_name
    FROM floors f
    INNER JOIN buildings b ON f.building_id = b.id
    ORDER BY b.name, f.level_number, f.name
  ")->fetchAll();
} catch (Exception $e) {
  $roomTypes = [];
  $locations = [];
}
?>
<form id="create-room-form" class="space-y-6 max-w-3xl mx-auto p-6 bg-white rounded-md shadow-md">
  <h2 class="text-2xl font-semibold mb-4 text-gray-900">Create New Room</h2>
  <div>
    <label for="name" class="block mb-1 font-medium text-gray-700">Room Name *</label>
    <input id="name" name="name" type="text" required
      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
      placeholder="Enter room name" />
  </div>
  <div>
    <label for="room_type_id" class="block mb-1 font-medium text-gray-700">Room Type *</label>
    <select id="room_type_id" name="room_type_id" required
      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
      <option value="">Select room type</option>
      <?php foreach ($roomTypes as $type): ?>
        <option value="<?= htmlspecialchars($type['id']) ?>">
          <?= htmlspecialchars($type['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
      <label for="capacity" class="block mb-1 font-medium text-gray-700">Capacity *</label>
      <input id="capacity" name="capacity" type="number" min="1" required
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        placeholder="Number of people" />
    </div>
    <div>
      <label for="floor_id" class="block mb-1 font-medium text-gray-700">Location (Building, Floor) *</label>
      <select id="floor_id" name="floor_id" required
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        <option value="">Select a location</option>
        <?php foreach ($locations as $location): ?>
          <option value="<?= htmlspecialchars($location['floor_id']) ?>">
            <?= htmlspecialchars($location['display_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <div>
    <label for="description" class="block mb-1 font-medium text-gray-700">Description (Optional)</label>
    <textarea id="description" name="description" rows="3"
      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
      placeholder="Room description and features"></textarea>
  </div>
  <div>
    <label for="equipment" class="block mb-1 font-medium text-gray-700">Equipment (Optional)</label>
    <textarea id="equipment" name="equipment" rows="3"
      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
      placeholder="Available equipment (projector, whiteboard, etc.)"></textarea>
  </div>
  <!-- Features Section -->
  <div>
    <label class="block mb-3 font-medium text-gray-700">Room Features (Optional)</label>
    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
      <?php
      $availableFeatures = [
        'projector' => 'Projector',
        'whiteboard' => 'Whiteboard',
        'audio_system' => 'Audio System',
        'video_conferencing' => 'Video Conferencing',
        'air_conditioning' => 'Air Conditioning',
        'wifi' => 'WiFi',
        'power_outlets' => 'Power Outlets',
        'natural_light' => 'Natural Light',
        'accessible' => 'Wheelchair Accessible',
        'kitchen_access' => 'Kitchen Access',
        'parking' => 'Parking Available',
        'security' => '24/7 Security'
      ];
      foreach ($availableFeatures as $key => $label): ?>
        <label class="flex items-center">
          <input type="checkbox" name="features[]" value="<?php echo $key; ?>"
            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
          <span class="ml-2 text-sm text-gray-700"><?php echo $label; ?></span>
        </label>
      <?php endforeach; ?>
    </div>
    <p class="mt-2 text-sm text-gray-500">Select features that describe this room (optional)</p>
  </div>
  <div>
    <label for="image_url" class="block mb-1 font-medium text-gray-700">Image URL</label>
    <input id="image_url" name="image_url" type="url"
      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
      placeholder="https://example.com/room-image.jpg" />
  </div>
  <div class="flex justify-end space-x-4 pt-4 border-t border-gray-200">
    <button type="button" onclick="loadPage('Rooms')"
      class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">Cancel</button>
    <button type="button" onclick="(async function() {
        const form = document.getElementById('create-room-form');
        const formData = new FormData(form);
        const data = {};
        data.features = [];
        for (let [key, value] of formData.entries()) {
          if (key === 'features[]') {
            data.features.push(value);
          } else if (value !== '') {
            data[key] = value;
          }
        }
        try {
          const response = await fetch('/api/rooms/create.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data),
            credentials: 'same-origin'
          });
          const result = await response.json();
          console.log(result);
          if (response.ok && result.room_id) {
            popupSystem.success('Room created successfully!');
            loadPage('Rooms');
          } else {
            popupSystem.error(result.error || 'Unknown error occurred');
          }
        } catch (error) {
          popupSystem.error('Network error: ' + error.message);
        }
      })()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2
      focus:ring-blue-500">Create
      Room</button>
  </div>
</form>
<script>
  document.getElementById('name').addEventListener('input', function() {
    const value = this.value.trim();
    if (value.length < 2) {
      this.classList.add('border-red-300');
    } else {
      this.classList.remove('border-red-300');
    }
  });
  document.getElementById('capacity').addEventListener('input', function() {
    const value = parseInt(this.value);
    if (isNaN(value) || value < 1) {
      this.classList.add('border-red-300');
    } else {
      this.classList.remove('border-red-300');
    }
  });
</script>