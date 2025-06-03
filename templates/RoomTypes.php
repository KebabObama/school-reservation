<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!isset($_SESSION['user_id'])) {
  header('Location: /');
  exit;
}

require_once __DIR__ . '/../lib/db.php';

// Get room types
try {
  $roomTypes = $pdo->query("
        SELECT rt.*, COUNT(r.id) as room_count 
        FROM room_types rt 
        LEFT JOIN rooms r ON rt.id = r.room_type_id AND r.is_active = 1
        GROUP BY rt.id 
        ORDER BY rt.name
    ")->fetchAll();
} catch (Exception $e) {
  $roomTypes = [];
}
?>

<div class="space-y-6">
  <!-- Header -->
  <div class="flex justify-between items-center">
    <div>
      <h1 class="text-3xl font-bold text-gray-900">Room Types</h1>
      <p class="text-gray-600">Manage different types of rooms in your system</p>
    </div>
    <button onclick="loadPage('CreateRoomType')"
      class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
      <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
      </svg>
      Add Room Type
    </button>
  </div>

  <!-- Room Types Grid -->
  <?php if (empty($roomTypes)): ?>
  <div class="bg-white rounded-lg shadow p-8 text-center">
    <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z">
      </path>
    </svg>
    <h3 class="text-lg font-medium text-gray-900 mb-2">No room types found</h3>
    <p class="text-gray-600 mb-4">Get started by creating your first room type.</p>
    <button onclick="loadPage('CreateRoomType')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
      Create Room Type
    </button>
  </div>
  <?php else: ?>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($roomTypes as $type): ?>
    <div class="bg-white rounded-lg shadow hover:shadow-md transition-shadow">
      <div class="p-6">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center">
            <div class="w-4 h-4 rounded-full mr-3"
              style="background-color: <?php echo htmlspecialchars($type['color']); ?>"></div>
            <h3 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($type['name']); ?></h3>
          </div>
          <div class="flex space-x-2">
            <button onclick="loadPage('EditRoomType&id=<?php echo $type['id']; ?>')"
              class="text-gray-400 hover:text-blue-600" title="Edit Room Type">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                </path>
              </svg>
            </button>
            <button onclick="(async function() {
                  const roomTypeId = <?php echo $type['id']; ?>;
                  const roomTypeName = '<?php echo addslashes($type['name']); ?>';
                  const roomCount = <?php echo $type['room_count']; ?>;

                  if (roomCount > 0) {
                    alert('Cannot delete this room type. It is currently used by ' + roomCount + ' room(s).');
                    return;
                  }

                  if (!confirm('Are you sure you want to delete the room type?' )) return; try {
              const response=await fetch('/api/room-types/delete.php', { method: 'POST' , headers:
              {'Content-Type': 'application/json' }, body: JSON.stringify({ id: roomTypeId }),
              credentials: 'same-origin' }); const result=await response.json(); if (response.ok) { 
               location.reload(); } else { alert('Error: ' + (result.error || ' Unknown error')); } } catch (error) { alert('Network error: ' + error.message);
                  }
                })()" class="text-gray-400 hover:text-red-600" title="Delete Room Type">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                </path>
              </svg>
            </button>
          </div>
        </div>

        <?php if ($type['description']): ?>
        <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($type['description']); ?></p>
        <?php endif; ?>

        <div class="flex items-center justify-between text-sm">
          <span class="text-gray-500">
            <?php echo $type['room_count']; ?> room<?php echo $type['room_count'] !== 1 ? 's' : ''; ?>
          </span>
          <span class="text-gray-500">
            Created <?php echo date('M j, Y', strtotime($type['created_at'])); ?>
          </span>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>