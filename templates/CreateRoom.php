<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
if (!isset($_SESSION['user_id'])) {
  echo '<p class="text-red-600">You must be logged in to create a room.</p>';
  return;
}

require_once __DIR__ . '/../lib/db.php';

try {
  $roomTypes = $pdo->query("SELECT id, name FROM room_types ORDER BY name")->fetchAll();
} catch (Exception $e) {
  $roomTypes = [];
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

  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div>
      <label for="capacity" class="block mb-1 font-medium text-gray-700">Capacity *</label>
      <input id="capacity" name="capacity" type="number" min="1" required
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        placeholder="Number of people" />
    </div>

    <div>
      <label for="floor" class="block mb-1 font-medium text-gray-700">Floor *</label>
      <input id="floor" name="floor" type="text" required
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        placeholder="e.g., 1st Floor, Ground" />
    </div>

    <div>
      <label for="building" class="block mb-1 font-medium text-gray-700">Building *</label>
      <input id="building" name="building" type="text" required
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        placeholder="Building name or number" />
    </div>
  </div>

  <div>
    <label for="location" class="block mb-1 font-medium text-gray-700">Location *</label>
    <input id="location" name="location" type="text" required
      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
      placeholder="Detailed location or address" />
  </div>

  <div>
    <label for="description" class="block mb-1 font-medium text-gray-700">Description</label>
    <textarea id="description" name="description" rows="3"
      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
      placeholder="Room description and features"></textarea>
  </div>

  <div>
    <label for="equipment" class="block mb-1 font-medium text-gray-700">Equipment</label>
    <textarea id="equipment" name="equipment" rows="3"
      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
      placeholder="Available equipment (projector, whiteboard, etc.)"></textarea>
  </div>

  <!-- Features Section -->
  <div>
    <label class="block mb-3 font-medium text-gray-700">Room Features</label>
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
    <p class="mt-2 text-sm text-gray-500">Select at least one feature that describes this room</p>
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
        // Validate features
        const checkboxes = document.querySelectorAll('input[name=\'features[]\']');
        const checked = Array.from(checkboxes).some(cb => cb.checked);

        if (!checked) {
          alert('Please select at least one room feature.');
          return;
        }

        const form = document.getElementById('create-room-form');
        const formData = new FormData(form);

        // Convert FormData to JSON
        const data = {};
        for (let [key, value] of formData.entries()) {
          if (key === 'features[]') {
            if (!data.features) data.features = [];
            data.features.push(value);
          } else if (value) {
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
          if (response.ok && result.room_id) {
            alert('Room created successfully!');
            loadPage('Rooms');
          } else {
            alert('Error: ' + (result.error || 'Unknown error'));
          }
        } catch (error) {
          alert('Network error: ' + error.message);
        }
      })()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2
      focus:ring-blue-500">Create
      Room</button>
  </div>
</form>

<script>
// Add real-time validation
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

// Validate required text fields
['description', 'floor', 'building', 'location', 'equipment'].forEach(fieldId => {
  document.getElementById(fieldId).addEventListener('input', function() {
    const value = this.value.trim();
    if (value.length < 1) {
      this.classList.add('border-red-300');
    } else {
      this.classList.remove('border-red-300');
    }
  });
});

// Feature selection feedback
document.addEventListener('change', function(e) {
  if (e.target.name === 'features[]') {
    const checkboxes = document.querySelectorAll('input[name="features[]"]');
    const checked = Array.from(checkboxes).some(cb => cb.checked);
    const container = document.querySelector('label:has(+ div > label > input[name="features[]"])');

    if (!checked) {
      checkboxes.forEach(cb => cb.closest('label').classList.add('text-red-600'));
    } else {
      checkboxes.forEach(cb => cb.closest('label').classList.remove('text-red-600'));
    }
  }
});
</script>