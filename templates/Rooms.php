<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
if (!isset($_SESSION['user_id'])) {
  header('Location: /');
  exit;
}
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/permissions.php';
if (!canViewRooms($_SESSION['user_id'])) {
  echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Access Denied</h1><p>You do not have permission to view rooms.</p></div>';
  return;
}
try {
  $rooms = $pdo->query("
        SELECT r.*, rt.name as room_type_name, rt.color as room_type_color,
        b.name as building_name, f.name as floor_name,
        COUNT(res.id) as reservation_count
        FROM rooms r
        LEFT JOIN room_types rt ON r.room_type_id = rt.id
        LEFT JOIN buildings b ON r.building_id = b.id
        LEFT JOIN floors f ON r.floor_id = f.id
        LEFT JOIN reservations res ON r.id = res.room_id
        GROUP BY r.id
        ORDER BY r.name
    ")->fetchAll();
} catch (Exception $e) {
  $rooms = [];
}
function renderDeleteRoomButton($roomId, $roomName, $reservationCount)
{
  $canDelete = canDeleteRooms($_SESSION['user_id']);
  if (!$canDelete)
    return '';
  $hasReservations = $reservationCount > 0;
  $title = $hasReservations ? 'Delete room and all its reservations' : 'Delete Room';
  return "
  <button onclick=\"(async () => { 
      let message = 'Are you sure you want to delete the room &quot;$roomName&quot;?';
      if ($reservationCount > 0)
        message += '\\n\\nThis will also delete:\\n• $reservationCount reservation(s)';
      const confirmed = await popupSystem.confirm(message, 'This action cannot be undone.');
      if (!confirmed) return;
      try {
        const response = await fetch('/api/rooms/delete.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: $roomId }),
          credentials: 'same-origin'
        });
        const result = await response.json();
        if (response.ok) {
          popupSystem.success(result.message || 'Room deleted successfully!');
          loadPage('Rooms');
        } else {
          popupSystem.error(result.error || 'Failed to delete room');
        }
      } catch (error) {
        popupSystem.error('Network error: ' + error.message);
      }
  })()\" 
  class=\"text-gray-400 hover:text-red-600\" title=\"$title\">
    <svg class=\"w-5 h-5\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
      <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\"
            d=\"M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16\"></path>
    </svg>
  </button>
";
}
?>
<div class="space-y-6">
  <div class="flex justify-between items-center">
    <div>
      <h1 class="text-3xl font-bold text-gray-900">Rooms</h1>
      <p class="text-gray-600">Manage your facility's rooms and their details</p>
    </div>
    <?php if (canCreateRooms($_SESSION['user_id'])): ?>
    <button onclick="loadPage('CreateRoom')"
      class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
      <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
      </svg>
      Add Room
    </button>
    <?php endif; ?>
  </div>
  <div class="bg-white rounded-lg shadow p-4">
    <div class="flex flex-wrap gap-4">
      <div class="flex-1 min-w-64">
        <input type="text" placeholder="Search rooms..."
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>
      <select class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        <option value="">All Types</option>
        <option value="conference">Conference Room</option>
        <option value="meeting">Meeting Room</option>
        <option value="training">Training Room</option>
      </select>
      <select class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        <option value="">All Status</option>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
      </select>
    </div>
  </div>
  <?php if (empty($rooms)): ?>
  <div class="bg-white rounded-lg shadow p-8 text-center">
    <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
      </path>
    </svg>
    <h3 class="text-lg font-medium text-gray-900 mb-2">No rooms found</h3>
    <p class="text-gray-600 mb-4">Get started by adding your first room.</p>
    <?php if (canCreateRooms($_SESSION['user_id'])): ?>
    <button onclick="loadPage('CreateRoom')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
      Add Room
    </button>
    <?php endif; ?>
  </div>
  <?php else: ?>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($rooms as $room): ?>
    <div class="bg-white rounded-lg shadow hover:shadow-md transition-shadow">
      <?php if ($room['image_url']): ?>
      <img src="<?php echo htmlspecialchars($room['image_url']); ?>"
        alt="<?php echo htmlspecialchars($room['name']); ?>" class="w-full h-48 object-cover rounded-t-lg">
      <?php else: ?>
      <div class="w-full h-48 bg-gray-200 rounded-t-lg flex items-center justify-center">
        <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
          </path>
        </svg>
      </div>
      <?php endif; ?>
      <div class="px-6 pt-6">
        <div class="flex items-center justify-between mb-2">
          <h3 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($room['name']); ?></h3>
          <div class="flex items-center space-x-2">
            <?php if ($room['room_type_name']): ?>
            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full text-white"
              style="background-color: <?php echo htmlspecialchars($room['room_type_color'] ?? '#3B82F6'); ?>">
              <?php echo htmlspecialchars($room['room_type_name']); ?>
            </span>
            <?php endif; ?>
            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                      <?php echo $room['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
              <?php echo $room['is_active'] ? 'Active' : 'Inactive'; ?>
            </span>
          </div>
        </div>
        <?php if ($room['description']): ?>
        <p class="text-gray-600 text-sm mb-3 line-clamp-2"><?php echo htmlspecialchars($room['description']); ?></p>
        <?php endif; ?>
        <div class="space-y-2 text-sm text-gray-600 mb-4">
          <?php if ($room['capacity']): ?>
          <div class="flex items-center">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z">
              </path>
            </svg>
            Capacity: <?php echo $room['capacity']; ?> people
          </div>
          <?php endif; ?>
          <?php if ($room['building_name']): ?>
          <div class="flex items-center">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
              </path>
            </svg>
            Building: <?php echo htmlspecialchars($room['building_name']); ?>
          </div>
          <?php endif; ?>
          <?php if ($room['floor_name']): ?>
          <div class="flex items-center">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2 2v0"></path>
            </svg>
            Floor: <?php echo htmlspecialchars($room['floor_name']); ?>
          </div>
          <?php endif; ?>
          <div class="flex justify-between items-center">
            <div class="flex items-center">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
              </svg>
              <?php echo $room['reservation_count']; ?>
              reservation<?php echo $room['reservation_count'] !== 1 ? 's' : ''; ?>
            </div>
            <div class="flex space-x-2">
              <?php if (canEditRooms($_SESSION['user_id'])): ?>
              <button onclick="loadPage('EditRoom&id=<?php echo $room['id']; ?>')"
                class="text-gray-400 hover:text-blue-600" title="Edit Room">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                  </path>
                </svg>
              </button>
              <?php endif; ?>
              <?php echo renderDeleteRoomButton($room['id'], $room['name'], $room['reservation_count']); ?>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>