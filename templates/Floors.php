<?php
if (session_status() === PHP_SESSION_NONE)
  session_start();
if (!isset($_SESSION['user_id'])) {
  echo '<p class="text-red-600">You must be logged in to view floors.</p>';
  return;
}
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/permissions.php';
if (!canViewFloors($_SESSION['user_id'])) {
  echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Access Denied</h1><p>You do not have permission to view floors.</p></div>';
  return;
}
try {
  $stmt = $pdo->query("
    SELECT f.*, 
           b.name as building_name,
           COUNT(DISTINCT r.id) as room_count
    FROM floors f
    LEFT JOIN buildings b ON f.building_id = b.id
    LEFT JOIN rooms r ON f.id = r.floor_id
    GROUP BY f.id
    ORDER BY b.name, f.level_number, f.name
  ");
  $floors = $stmt->fetchAll();
} catch (Exception $e) {
  $floors = [];
}
function renderDeleteFloorButton($floorId, $floorName, $roomCount)
{
  $canDelete = canDeleteFloors($_SESSION['user_id']);
  if (!$canDelete)
    return '';
  $hasRooms = $roomCount > 0;
  $title = $hasRooms ? 'Delete floor and all its rooms and reservations' : 'Delete Floor';
  return "
    <button onclick=\"deleteFloorAction($floorId, '$floorName', $roomCount)\"
            class=\"text-gray-400 hover:text-red-600\"
            title=\"$title\">
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
      <h1 class="text-3xl font-bold text-gray-900">Floors</h1>
      <p class="text-gray-600">Manage floors within your buildings</p>
    </div>
    <?php if (canCreateFloors($_SESSION['user_id'])): ?>
      <button onclick="loadPage('CreateFloor')"
        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        Add Floor
      </button>
    <?php endif; ?>
  </div>

  <?php if (empty($floors)): ?>
    <div class="bg-white rounded-lg shadow p-8 text-center">
      <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
        </path>
      </svg>
      <h3 class="text-lg font-medium text-gray-900 mb-2">No floors found</h3>
      <p class="text-gray-600 mb-4">Get started by adding your first floor.</p>
      <?php if (canCreateFloors($_SESSION['user_id'])): ?>
        <button onclick="loadPage('CreateFloor')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
          Add Floor
        </button>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach ($floors as $floor): ?>
        <div class="bg-white rounded-lg shadow hover:shadow-md transition-shadow">
          <div class="p-6">
            <div class="flex justify-between items-start mb-4">
              <div>
                <h3 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($floor['name']); ?></h3>
                <p class="text-sm text-blue-600"><?php echo htmlspecialchars($floor['building_name']); ?></p>
              </div>
              <div class="flex space-x-2">
                <?php if (canEditFloors($_SESSION['user_id'])): ?>
                  <button onclick="loadPage('EditFloor&id=<?php echo $floor['id']; ?>')"
                    class="text-gray-400 hover:text-blue-600" title="Edit Floor">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                      </path>
                    </svg>
                  </button>
                <?php endif; ?>
                <?php echo renderDeleteFloorButton($floor['id'], $floor['name'], $floor['room_count']); ?>
              </div>
            </div>
            <?php if (!empty($floor['description'])): ?>
              <p class="text-gray-600 text-sm mb-4"><?php echo htmlspecialchars($floor['description']); ?></p>
            <?php endif; ?>
            <div class="flex justify-between items-center text-sm text-gray-500">
              <?php if ($floor['level_number'] !== null): ?>
                <span>Level <?php echo $floor['level_number']; ?></span>
              <?php else: ?>
                <span>No level specified</span>
              <?php endif; ?>
              <span><?php echo $floor['room_count']; ?> room(s)</span>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<script>
  async function deleteFloorAction(floorId, floorName, roomCount) {
    let message = `Are you sure you want to delete the floor "${floorName}"?`;
    if (roomCount > 0)
      message += `\n\nThis will also delete:\n• ${roomCount} room(s)\n• All reservations for these rooms`;
    const confirmed = await popupSystem.confirm(
      message,
      'This action cannot be undone.'
    );
    if (!confirmed) return;
    try {
      const response = await fetch('/api/floors/delete.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          id: floorId
        }),
        credentials: 'same-origin'
      });

      const result = await response.json();
      if (response.ok) {
        popupSystem.success(result.message || 'Floor deleted successfully!');
        loadPage('Floors');
      } else
        popupSystem.error(result.error || 'Failed to delete floor');
    } catch (error) {
      popupSystem.error('Network error: ' + error.message);
    }
  }
</script>