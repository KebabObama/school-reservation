<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
if (!isset($_SESSION['user_id'])) {
  echo '<p class="text-red-600">You must be logged in to edit a room.</p>';
  return;
}
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/permissions.php';
if (!canEditRooms($_SESSION['user_id'])) {
  echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Access Denied</h1><p>You do not have permission to edit rooms.</p></div>';
  return;
}
$roomId = $_GET['id'] ?? null;
if (!$roomId) {
  echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Error</h1><p>Room ID is required.</p></div>';
  return;
}
try {
  $stmt = $pdo->prepare("
    SELECT r.*, b.name as building_name, f.name as floor_name
    FROM rooms r
    LEFT JOIN buildings b ON r.building_id = b.id
    LEFT JOIN floors f ON r.floor_id = f.id
    WHERE r.id = ?
  ");
  $stmt->execute([$roomId]);
  $room = $stmt->fetch();
  if (!$room) {
    echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Error</h1><p>Room not found.</p></div>';
    return;
  }
  $roomTypes = $pdo->query("SELECT id, name FROM room_types ORDER BY name")->fetchAll();
  $features = $room['features'] ? json_decode($room['features'], true) : [];
  $availability = $room['availability_schedule'] ? json_decode($room['availability_schedule'], true) : [];
} catch (Exception $e) {
  echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Error</h1><p>Unable to load room data.</p></div>';
  return;
}
?>
<form id="edit-room-form" class="space-y-6 max-w-4xl mx-auto p-6 bg-white rounded-md shadow-md">
  <div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-semibold text-gray-900">Edit Room: <?php echo htmlspecialchars($room['name']); ?></h2>
    <button type="button" onclick="loadPage('Rooms')" class="text-gray-600 hover:text-gray-800">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
      </svg>
    </button>
  </div>
  <input type="hidden" id="room_id" value="<?php echo $room['id']; ?>">
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div>
      <label for="name" class="block mb-1 font-medium text-gray-700">Room Name *</label>
      <input id="name" name="name" type="text" required value="<?php echo htmlspecialchars($room['name']); ?>"
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        placeholder="Enter room name" />
    </div>
    <div>
      <label for="room_type_id" class="block mb-1 font-medium text-gray-700">Room Type *</label>
      <select id="room_type_id" name="room_type_id" required
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        <option value="">Select room type</option>
        <?php foreach ($roomTypes as $type): ?>
        <option value="<?php echo $type['id']; ?>"
          <?php echo $room['room_type_id'] == $type['id'] ? 'selected' : ''; ?>>
          <?php echo htmlspecialchars($type['name']); ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <div>
    <label for="description" class="block mb-1 font-medium text-gray-700">Description (Optional)</label>
    <textarea id="description" name="description" rows="3"
      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
      placeholder="Enter room description"><?php echo htmlspecialchars($room['description'] ?? ''); ?></textarea>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
      <label for="capacity" class="block mb-1 font-medium text-gray-700">Capacity *</label>
      <input id="capacity" name="capacity" type="number" min="1" required value="<?php echo $room['capacity']; ?>"
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        placeholder="Number of people" />
    </div>
    <div>
      <label for="floor_id" class="block mb-1 font-medium text-gray-700">Location (Building, Floor) *</label>
      <select id="floor_id" name="floor_id" required
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        <option value="">Select a location</option>
      </select>
    </div>
  </div>
  <div>
    <label for="equipment" class="block mb-1 font-medium text-gray-700">Equipment (Optional)</label>
    <textarea id="equipment" name="equipment" rows="3"
      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
      placeholder="List available equipment (e.g., Projector, Whiteboard, Audio System)"><?php echo htmlspecialchars($room['equipment'] ?? ''); ?></textarea>
  </div>
  <div>
    <label for="image_url" class="block mb-1 font-medium text-gray-700">Image URL</label>
    <input id="image_url" name="image_url" type="url" value="<?php echo htmlspecialchars($room['image_url'] ?? ''); ?>"
      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
      placeholder="https://example.com/room-image.jpg" />
  </div>
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
          <?php echo in_array($key, $features) ? 'checked' : ''; ?>
          class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
        <span class="ml-2 text-sm text-gray-700"><?php echo $label; ?></span>
      </label>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="flex items-center">
    <input id="is_active" name="is_active" type="checkbox" <?php echo $room['is_active'] ? 'checked' : ''; ?>
      class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
    <label for="is_active" class="ml-2 block text-sm text-gray-900">
      Room is active and available for booking
    </label>
  </div>
  <div class="flex justify-end space-x-4 pt-4 border-t border-gray-200">
    <button type="button" onclick="loadPage('Rooms')"
      class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">Cancel</button>
    <button type="button" onclick="(async function() {
        const form = document.getElementById('edit-room-form');
        const formData = new FormData(form);
        const data = { id: document.getElementById('room_id').value };
        data.features = [];
        for (let [key, value] of formData.entries()) {
          if (key === 'features[]') {
            data.features.push(value);
          } else if (value !== '') {
            data[key] = value;
          }
        }
        data.is_active = document.getElementById('is_active').checked;
        try {
          const response = await fetch('/api/rooms/edit.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data),
            credentials: 'same-origin'
          });
          const result = await response.json();
          if (response.ok) {
            popupSystem.success('Room updated successfully!');
            loadPage('Rooms');
          } else popupSystem.error(result.error || 'Unknown error');
        } catch (error) {
          popupSystem.error('Network error: ' + error.message);
        }
      })()"
      class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">Update
      Room</button>
  </div>
</form>
<script>
document.getElementById('name').addEventListener('input', function() {
  const value = this.value.trim();
  if (value.length < 2) this.classList.add('border-red-300');
  else this.classList.remove('border-red-300');
});
function validateFeatures() {
  return true;
}
const submitButton = document.querySelector('button[onclick*="edit.php"]');
const originalOnclick = submitButton.getAttribute('onclick');
submitButton.setAttribute('onclick', 'if (validateFeatures()) { ' + originalOnclick + ' }');
document.addEventListener('DOMContentLoaded', async function() {
  const floorSelect = document.getElementById('floor_id');
  const currentFloorId = <?php echo $room['floor_id'] ?? 'null'; ?>;
  try {
    const response = await fetch('/api/floors/list-with-buildings.php');
    if (!response.ok)
      throw new Error(`HTTP error! status: ${response.status}`);
    const floors = await response.json();
    if (floors.length === 0) {
      const option = document.createElement('option');
      option.value = '';
      option.textContent = 'No locations available';
      option.disabled = true;
      floorSelect.appendChild(option);
    } else {
      floors.forEach(floor => {
        const option = document.createElement('option');
        option.value = floor.floor_id;
        option.textContent = floor.display_name;
        if (floor.floor_id == currentFloorId)
          option.selected = true;
        floorSelect.appendChild(option);
      });
    }
  } catch (error) {
    console.error('Error loading locations:', error);
    const option = document.createElement('option');
    option.value = '';
    option.textContent = 'Error loading locations';
    option.disabled = true;
    floorSelect.appendChild(option);
  }
});
</script>