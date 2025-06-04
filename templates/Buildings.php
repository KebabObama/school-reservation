<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!isset($_SESSION['user_id'])) {
  echo '<p class="text-red-600">You must be logged in to view buildings.</p>';
  return;
}

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/permissions.php';

// Check if user has permission to view buildings
if (!canViewBuildings($_SESSION['user_id'])) {
  echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Access Denied</h1><p>You do not have permission to view buildings.</p></div>';
  return;
}

try {
  $stmt = $pdo->query("
    SELECT b.*, 
           COUNT(DISTINCT f.id) as floor_count,
           COUNT(DISTINCT r.id) as room_count
    FROM buildings b
    LEFT JOIN floors f ON b.id = f.building_id
    LEFT JOIN rooms r ON b.id = r.building_id
    GROUP BY b.id
    ORDER BY b.name
  ");
  $buildings = $stmt->fetchAll();
} catch (Exception $e) {
  $buildings = [];
}

function renderDeleteBuildingButton($buildingId, $buildingName, $floorCount, $roomCount)
{
  $canDelete = canDeleteBuildings($_SESSION['user_id']);

  if (!$canDelete) {
    return '';
  }

  $hasContent = $floorCount > 0 || $roomCount > 0;
  $title = $hasContent ? 'Delete building and all its floors, rooms, and reservations' : 'Delete Building';

  return "
    <button onclick=\"deleteBuildingAction($buildingId, '$buildingName', $floorCount, $roomCount)\"
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
  <!-- Header -->
  <div class="flex justify-between items-center">
    <div>
      <h1 class="text-3xl font-bold text-gray-900">Buildings</h1>
      <p class="text-gray-600">Manage your facility's buildings</p>
    </div>
    <?php if (canCreateBuildings($_SESSION['user_id'])): ?>
      <button onclick="loadPage('CreateBuilding')"
        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        Add Building
      </button>
    <?php endif; ?>
  </div>

  <!-- Buildings Grid -->
  <?php if (empty($buildings)): ?>
    <div class="bg-white rounded-lg shadow p-8 text-center">
      <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
        </path>
      </svg>
      <h3 class="text-lg font-medium text-gray-900 mb-2">No buildings found</h3>
      <p class="text-gray-600 mb-4">Get started by adding your first building.</p>
      <?php if (canCreateBuildings($_SESSION['user_id'])): ?>
        <button onclick="loadPage('CreateBuilding')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
          Add Building
        </button>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach ($buildings as $building): ?>
        <div class="bg-white rounded-lg shadow hover:shadow-md transition-shadow">
          <div class="p-6">
            <div class="flex justify-between items-start mb-4">
              <h3 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($building['name']); ?></h3>
              <div class="flex space-x-2">
                <?php if (canEditBuildings($_SESSION['user_id'])): ?>
                  <button onclick="loadPage('EditBuilding&id=<?php echo $building['id']; ?>')" class="text-gray-400 hover:text-blue-600" title="Edit Building">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                      </path>
                    </svg>
                  </button>
                <?php endif; ?>
                <?php echo renderDeleteBuildingButton($building['id'], $building['name'], $building['floor_count'], $building['room_count']); ?>
              </div>
            </div>

            <?php if (!empty($building['description'])): ?>
              <p class="text-gray-600 text-sm mb-4"><?php echo htmlspecialchars($building['description']); ?></p>
            <?php endif; ?>

            <?php if (!empty($building['address'])): ?>
              <div class="flex items-center text-gray-500 text-sm mb-4">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <?php echo htmlspecialchars($building['address']); ?>
              </div>
            <?php endif; ?>

            <div class="flex justify-between items-center text-sm text-gray-500">
              <span><?php echo $building['floor_count']; ?> floor(s)</span>
              <span><?php echo $building['room_count']; ?> room(s)</span>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<script>
  async function deleteBuildingAction(buildingId, buildingName, floorCount, roomCount) {
    let message = `Are you sure you want to delete the building "${buildingName}"?`;
    if (floorCount > 0 || roomCount > 0) {
      message += `\n\nThis will also delete:\n• ${floorCount} floor(s)\n• ${roomCount} room(s)\n• All reservations for these rooms`;
    }

    const confirmed = await popupSystem.confirm(
      message,
      'This action cannot be undone.'
    );

    if (!confirmed) return;

    try {
      const response = await fetch('/api/buildings/delete.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          id: buildingId
        }),
        credentials: 'same-origin'
      });

      const result = await response.json();
      if (response.ok) {
        popupSystem.success(result.message || 'Building deleted successfully!');
        loadPage('Buildings');
      } else {
        popupSystem.error(result.error || 'Failed to delete building');
      }
    } catch (error) {
      popupSystem.error('Network error: ' + error.message);
    }
  }
</script>