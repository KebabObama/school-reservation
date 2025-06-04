<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
if (!isset($_SESSION['user_id'])) {
  echo '<p class="text-red-600">You must be logged in to edit a room type.</p>';
  return;
}

require_once __DIR__ . '/../lib/db.php';

// Check if user has permission to manage rooms
try {
  $stmt = $pdo->prepare("SELECT can_manage_rooms FROM permissions WHERE user_id = ?");
  $stmt->execute([$_SESSION['user_id']]);
  $canManage = $stmt->fetchColumn();

  if (!$canManage) {
    echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Access Denied</h1><p>You do not have permission to edit room types.</p></div>';
    return;
  }
} catch (Exception $e) {
  echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Error</h1><p>Unable to verify permissions.</p></div>';
  return;
}

// Get room type ID from URL parameter
$roomTypeId = $_GET['id'] ?? null;
if (!$roomTypeId) {
  echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Error</h1><p>Room type ID is required.</p></div>';
  return;
}

try {
  // Get room type data
  $stmt = $pdo->prepare("SELECT * FROM room_types WHERE id = ?");
  $stmt->execute([$roomTypeId]);
  $roomType = $stmt->fetch();

  if (!$roomType) {
    echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Error</h1><p>Room type not found.</p></div>';
    return;
  }

  // Get room count for this type
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM rooms WHERE room_type_id = ? AND is_active = 1");
  $stmt->execute([$roomTypeId]);
  $roomCount = $stmt->fetchColumn();
} catch (Exception $e) {
  echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Error</h1><p>Unable to load room type data.</p></div>';
  return;
}
?>

<form id="edit-room-type-form" class="space-y-6 max-w-2xl mx-auto p-6 bg-white rounded-md shadow-md">
  <div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-semibold text-gray-900">Edit Room Type: <?php echo htmlspecialchars($roomType['name']); ?></h2>
    <button type="button" onclick="loadPage('RoomTypes')" class="text-gray-600 hover:text-gray-800">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
      </svg>
    </button>
  </div>

  <input type="hidden" id="room_type_id" value="<?php echo $roomType['id']; ?>">

  <!-- Usage Info -->
  <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
    <div class="flex items-center">
      <svg class="h-5 w-5 text-blue-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
      </svg>
      <span class="text-sm text-blue-800">
        This room type is currently used by <strong><?php echo $roomCount; ?></strong> room<?php echo $roomCount !== 1 ? 's' : ''; ?>
      </span>
    </div>
  </div>

  <div>
    <label for="name" class="block mb-1 font-medium text-gray-700">Type Name *</label>
    <input id="name" name="name" type="text" required
      value="<?php echo htmlspecialchars($roomType['name']); ?>"
      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
      placeholder="Enter room type name" />
  </div>

  <div>
    <label for="description" class="block mb-1 font-medium text-gray-700">Description *</label>
    <textarea id="description" name="description" rows="4" required
      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
      placeholder="Describe this room type"><?php echo htmlspecialchars($roomType['description'] ?? ''); ?></textarea>
  </div>

  <div>
    <label for="color" class="block mb-1 font-medium text-gray-700">Color *</label>
    <div class="flex items-center space-x-3">
      <input id="color" name="color" type="color" required
        value="<?php echo htmlspecialchars($roomType['color']); ?>"
        class="h-10 w-20 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
      <span class="text-sm text-gray-600">Choose a color to represent this room type</span>
    </div>
  </div>

  <!-- Predefined Color Options -->
  <div>
    <label class="block mb-2 font-medium text-gray-700">Quick Color Selection</label>
    <div class="grid grid-cols-6 gap-2">
      <?php
      $predefinedColors = [
        '#3B82F6' => 'Blue',
        '#10B981' => 'Green',
        '#F59E0B' => 'Orange',
        '#8B5CF6' => 'Purple',
        '#EF4444' => 'Red',
        '#6B7280' => 'Gray',
        '#EC4899' => 'Pink',
        '#14B8A6' => 'Teal',
        '#F97316' => 'Orange',
        '#84CC16' => 'Lime',
        '#06B6D4' => 'Cyan',
        '#A855F7' => 'Violet'
      ];

      foreach ($predefinedColors as $colorCode => $colorName): ?>
        <button type="button"
          onclick="document.getElementById('color').value = '<?php echo $colorCode; ?>'"
          class="w-8 h-8 rounded-md border-2 border-gray-300 hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 <?php echo $roomType['color'] === $colorCode ? 'ring-2 ring-blue-500' : ''; ?>"
          style="background-color: <?php echo $colorCode; ?>"
          title="<?php echo $colorName; ?>">
        </button>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Preview -->
  <div>
    <label class="block mb-2 font-medium text-gray-700">Preview</label>
    <div class="flex items-center p-3 bg-gray-50 rounded-md">
      <div id="color-preview" class="w-4 h-4 rounded-full mr-3" style="background-color: <?php echo htmlspecialchars($roomType['color']); ?>"></div>
      <span id="name-preview" class="font-medium"><?php echo htmlspecialchars($roomType['name']); ?></span>
    </div>
  </div>

  <?php if ($roomCount > 0): ?>
    <!-- Warning for rooms using this type -->
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
            <p>Changes to this room type will affect all <?php echo $roomCount; ?> room<?php echo $roomCount !== 1 ? 's' : ''; ?> that use this type. The color and name changes will be reflected immediately across the system.</p>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <div class="flex justify-end space-x-4 pt-4 border-t border-gray-200">
    <button type="button" onclick="loadPage('RoomTypes')"
      class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">Cancel</button>
    <button type="button" onclick="(async function() {
        const form = document.getElementById('edit-room-type-form');
        const formData = new FormData(form);
        
        // Convert FormData to JSON
        const data = { id: document.getElementById('room_type_id').value };
        for (let [key, value] of formData.entries()) {
          if (value.trim()) data[key] = value.trim();
        }
        
        try {
          const response = await fetch('/api/room-types/edit.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data),
            credentials: 'same-origin'
          });
          
          const result = await response.json();
          if (response.ok) {
            popupSystem.success('Room type updated successfully!');
            loadPage('RoomTypes');
          } else {
            popupSystem.error(result.error || 'Unknown error');
          }
        } catch (error) {
          popupSystem.error('Network error: ' + error.message);
        }
      })()"
      class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">Update Room Type</button>
  </div>
</form>

<script>
  // Real-time preview updates
  document.getElementById('name').addEventListener('input', function() {
    document.getElementById('name-preview').textContent = this.value || 'Room Type Name';
  });

  document.getElementById('color').addEventListener('change', function() {
    document.getElementById('color-preview').style.backgroundColor = this.value;
  });

  // Validation
  document.getElementById('name').addEventListener('input', function() {
    const value = this.value.trim();
    if (value.length < 2) {
      this.classList.add('border-red-300');
    } else {
      this.classList.remove('border-red-300');
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