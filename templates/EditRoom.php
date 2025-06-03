<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
if (!isset($_SESSION['user_id'])) {
  echo '<p class="text-red-600">You must be logged in to edit a room.</p>';
  return;
}

require_once __DIR__ . '/../lib/db.php';

// Check if user has permission to manage rooms
try {
  $stmt = $pdo->prepare("SELECT can_manage_rooms FROM permissions WHERE user_id = ?");
  $stmt->execute([$_SESSION['user_id']]);
  $canManage = $stmt->fetchColumn();
  
  if (!$canManage) {
    echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Access Denied</h1><p>You do not have permission to edit rooms.</p></div>';
    return;
  }
} catch (Exception $e) {
  echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Error</h1><p>Unable to verify permissions.</p></div>';
  return;
}

// Get room ID from URL parameter
$roomId = $_GET['id'] ?? null;
if (!$roomId) {
  echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Error</h1><p>Room ID is required.</p></div>';
  return;
}

try {
  // Get room data
  $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
  $stmt->execute([$roomId]);
  $room = $stmt->fetch();
  
  if (!$room) {
    echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Error</h1><p>Room not found.</p></div>';
    return;
  }
  
  // Get room types
  $roomTypes = $pdo->query("SELECT id, name FROM room_types ORDER BY name")->fetchAll();
  
  // Parse JSON fields
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
      <input id="name" name="name" type="text" required
        value="<?php echo htmlspecialchars($room['name']); ?>"
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        placeholder="Enter room name" />
    </div>

    <div>
      <label for="room_type_id" class="block mb-1 font-medium text-gray-700">Room Type *</label>
      <select id="room_type_id" name="room_type_id" required
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        <option value="">Select room type</option>
        <?php foreach ($roomTypes as $type): ?>
          <option value="<?php echo $type['id']; ?>" <?php echo $room['room_type_id'] == $type['id'] ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($type['name']); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div>
    <label for="description" class="block mb-1 font-medium text-gray-700">Description *</label>
    <textarea id="description" name="description" rows="3" required
      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
      placeholder="Enter room description"><?php echo htmlspecialchars($room['description'] ?? ''); ?></textarea>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div>
      <label for="capacity" class="block mb-1 font-medium text-gray-700">Capacity *</label>
      <input id="capacity" name="capacity" type="number" min="1" required
        value="<?php echo $room['capacity']; ?>"
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        placeholder="Number of people" />
    </div>

    <div>
      <label for="floor" class="block mb-1 font-medium text-gray-700">Floor *</label>
      <input id="floor" name="floor" type="text" required
        value="<?php echo htmlspecialchars($room['floor'] ?? ''); ?>"
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        placeholder="e.g., 1st Floor, Ground" />
    </div>

    <div>
      <label for="building" class="block mb-1 font-medium text-gray-700">Building *</label>
      <input id="building" name="building" type="text" required
        value="<?php echo htmlspecialchars($room['building'] ?? ''); ?>"
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        placeholder="Building name or number" />
    </div>
  </div>

  <div>
    <label for="location" class="block mb-1 font-medium text-gray-700">Location *</label>
    <input id="location" name="location" type="text" required
      value="<?php echo htmlspecialchars($room['location'] ?? ''); ?>"
      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
      placeholder="Detailed location or address" />
  </div>

  <div>
    <label for="equipment" class="block mb-1 font-medium text-gray-700">Equipment *</label>
    <textarea id="equipment" name="equipment" rows="3" required
      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
      placeholder="List available equipment (e.g., Projector, Whiteboard, Audio System)"><?php echo htmlspecialchars($room['equipment'] ?? ''); ?></textarea>
  </div>

  <div>
    <label for="image_url" class="block mb-1 font-medium text-gray-700">Image URL</label>
    <input id="image_url" name="image_url" type="url"
      value="<?php echo htmlspecialchars($room['image_url'] ?? ''); ?>"
      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
      placeholder="https://example.com/room-image.jpg" />
  </div>

  <!-- Features Section -->
  <div>
    <label class="block mb-3 font-medium text-gray-700">Room Features *</label>
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

  <!-- Status -->
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
        
        // Convert FormData to JSON
        const data = { id: document.getElementById('room_id').value };
        for (let [key, value] of formData.entries()) {
          if (key === 'features[]') {
            if (!data.features) data.features = [];
            data.features.push(value);
          } else if (value) {
            data[key] = value;
          }
        }
        
        // Handle checkboxes
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
            alert('Room updated successfully!');
            loadPage('Rooms');
          } else {
            alert('Error: ' + (result.error || 'Unknown error'));
          }
        } catch (error) {
          alert('Network error: ' + error.message);
        }
      })()"
      class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">Update Room</button>
  </div>
</form>

<script>
// Add validation
document.getElementById('name').addEventListener('input', function() {
  const value = this.value.trim();
  if (value.length < 2) {
    this.classList.add('border-red-300');
  } else {
    this.classList.remove('border-red-300');
  }
});

// Ensure at least one feature is selected
function validateFeatures() {
  const checkboxes = document.querySelectorAll('input[name="features[]"]');
  const checked = Array.from(checkboxes).some(cb => cb.checked);
  
  if (!checked) {
    alert('Please select at least one room feature.');
    return false;
  }
  return true;
}

// Add feature validation to submit button
const submitButton = document.querySelector('button[onclick*="edit.php"]');
const originalOnclick = submitButton.getAttribute('onclick');
submitButton.setAttribute('onclick', 'if (validateFeatures()) { ' + originalOnclick + ' }');
</script>
