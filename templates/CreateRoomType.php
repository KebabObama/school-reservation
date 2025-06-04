<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
if (!isset($_SESSION['user_id'])) {
  echo '<p class="text-red-600">You must be logged in to create a room type.</p>';
  return;
}

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/permissions.php';

// Check if user has permission to create rooms (room types are part of room management)
if (!canCreateRooms($_SESSION['user_id'])) {
  echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Access Denied</h1><p>You do not have permission to create room types.</p></div>';
  return;
}
?>

<form id="create-room-type-form" class="space-y-6 max-w-2xl mx-auto p-6 bg-white rounded-md shadow-md">
  <h2 class="text-2xl font-semibold mb-4 text-gray-900">Create New Room Type</h2>

  <div>
    <label for="name" class="block mb-1 font-medium text-gray-700">Type Name *</label>
    <input id="name" name="name" type="text" required
      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
      placeholder="Enter room type name (e.g., Conference Room, Meeting Room)" />
  </div>

  <div>
    <label for="description" class="block mb-1 font-medium text-gray-700">Description *</label>
    <textarea id="description" name="description" rows="4" required
      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
      placeholder="Describe this room type, its typical use cases, and characteristics"></textarea>
  </div>

  <div>
    <label for="color" class="block mb-1 font-medium text-gray-700">Color *</label>
    <div class="flex items-center space-x-3">
      <input id="color" name="color" type="color" value="#3B82F6" required
        class="h-10 w-20 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
      <span class="text-sm text-gray-600">Choose a color to represent this room type in the system</span>
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
          class="w-8 h-8 rounded-md border-2 border-gray-300 hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
          style="background-color: <?php echo $colorCode; ?>"
          title="<?php echo $colorName; ?>">
        </button>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Usage Guidelines -->
  <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
    <div class="flex">
      <div class="flex-shrink-0">
        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
        </svg>
      </div>
      <div class="ml-3">
        <h3 class="text-sm font-medium text-blue-800">Room Type Guidelines</h3>
        <div class="mt-2 text-sm text-blue-700">
          <ul class="list-disc pl-5 space-y-1">
            <li>Choose descriptive names that clearly indicate the room's purpose</li>
            <li>Provide detailed descriptions to help users understand when to use this type</li>
            <li>Select colors that are visually distinct from existing room types</li>
            <li>Consider capacity ranges and typical equipment when describing the type</li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <!-- Example Room Types -->
  <div class="bg-gray-50 border border-gray-200 rounded-md p-4">
    <h3 class="text-sm font-medium text-gray-800 mb-2">Example Room Types</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
      <div class="flex items-center">
        <div class="w-3 h-3 rounded-full bg-blue-500 mr-2"></div>
        <span><strong>Conference Room:</strong> Large rooms for meetings and presentations</span>
      </div>
      <div class="flex items-center">
        <div class="w-3 h-3 rounded-full bg-green-500 mr-2"></div>
        <span><strong>Meeting Room:</strong> Small to medium rooms for team meetings</span>
      </div>
      <div class="flex items-center">
        <div class="w-3 h-3 rounded-full bg-orange-500 mr-2"></div>
        <span><strong>Training Room:</strong> Rooms equipped for training and workshops</span>
      </div>
      <div class="flex items-center">
        <div class="w-3 h-3 rounded-full bg-purple-500 mr-2"></div>
        <span><strong>Auditorium:</strong> Large spaces for events and presentations</span>
      </div>
    </div>
  </div>

  <div class="flex justify-end space-x-4 pt-4 border-t border-gray-200">
    <button type="button" onclick="loadPage('RoomTypes')"
      class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">Cancel</button>
    <button type="button" onclick="(async function() {
        const form = document.getElementById('create-room-type-form');
        const formData = new FormData(form);
        
        // Convert FormData to JSON
        const data = {};
        for (let [key, value] of formData.entries()) {
          if (value.trim()) data[key] = value.trim();
        }
        
        // Validate required fields
        if (!data.name || !data.description || !data.color) {
          alert('Please fill in all required fields.');
          return;
        }
        
        try {
          const response = await fetch('/api/room-types/create.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data),
            credentials: 'same-origin'
          });
          
          const result = await response.json();
          if (response.ok && result.room_type_id) {
            popupSystem.success('Room type created successfully!');
            loadPage('RoomTypes');
          } else {
            popupSystem.error(result.error || 'Unknown error occurred');
          }
        } catch (error) {
          popupSystem.error('Network error: ' + error.message);
        }
      })()"
      class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">Create Room Type</button>
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
    if (value.length < 10) {
      this.classList.add('border-red-300');
    } else {
      this.classList.remove('border-red-300');
    }
  });

  // Color preview update
  document.getElementById('color').addEventListener('change', function() {
    const colorValue = this.value;
    // You could add a preview here if needed
  });
</script>