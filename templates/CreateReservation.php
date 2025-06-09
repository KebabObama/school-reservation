<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
if (!isset($_SESSION['user_id'])) {
  echo '<p class="text-red-600">You must be logged in to create a reservation.</p>';
  return;
}
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/permissions.php';
if (!hasPermission($_SESSION['user_id'], 'reservations_create')) {
  echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Access Denied</h1><p>You do not have permission to create reservations.</p></div>';
  return;
}
try {
  $rooms = $pdo->query("SELECT id, name FROM rooms WHERE is_active = 1 ORDER BY name")->fetchAll();
  $purposes = $pdo->query("SELECT id, name FROM reservation_purposes ORDER BY name")->fetchAll();
} catch (Exception $e) {
  $rooms = [];
  $purposes = [];
}
?>
<div class="max-w-4xl mx-auto p-3 sm:p-4 lg:p-6">
  <form id="create-reservation-form" class="space-y-4 sm:space-y-6 bg-white rounded-lg shadow-md p-4 sm:p-6">
    <div class="border-b border-gray-200 pb-4">
      <h2 class="text-xl sm:text-2xl font-semibold text-gray-900">Create New Reservation</h2>
      <p class="text-sm text-gray-600 mt-1">Fill in the details for your room reservation</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
      <div>
        <label for="room_id" class="block mb-2 font-medium text-gray-700 text-sm sm:text-base">Room *</label>
        <select id="room_id" name="room_id" required
          class="w-full px-3 py-2 sm:py-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm sm:text-base touch-manipulation">
          <option value="">Select a room</option>
          <?php foreach ($rooms as $room): ?>
            <option value="<?php echo $room['id']; ?>"><?php echo htmlspecialchars($room['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label for="purpose_id" class="block mb-2 font-medium text-gray-700 text-sm sm:text-base">Purpose *</label>
        <select id="purpose_id" name="purpose_id" required
          class="w-full px-3 py-2 sm:py-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm sm:text-base touch-manipulation">
          <option value="">Select a purpose</option>
          <?php foreach ($purposes as $purpose): ?>
            <option value="<?php echo $purpose['id']; ?>"><?php echo htmlspecialchars($purpose['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div>
      <label for="title" class="block mb-2 font-medium text-gray-700 text-sm sm:text-base">Title *</label>
      <input id="title" name="title" type="text" required
        class="w-full px-3 py-2 sm:py-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm sm:text-base touch-manipulation"
        placeholder="Enter reservation title" />
    </div>

    <div>
      <label for="description" class="block mb-2 font-medium text-gray-700 text-sm sm:text-base">Description *</label>
      <textarea id="description" name="description" rows="3" required
        class="w-full px-3 py-2 sm:py-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm sm:text-base resize-y touch-manipulation"
        placeholder="Enter detailed description of the event/meeting"></textarea>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
      <div>
        <label for="start_time" class="block mb-2 font-medium text-gray-700 text-sm sm:text-base">Start Time *</label>
        <input id="start_time" name="start_time" type="datetime-local" required
          class="w-full px-3 py-2 sm:py-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm sm:text-base touch-manipulation" />
      </div>
      <div>
        <label for="end_time" class="block mb-2 font-medium text-gray-700 text-sm sm:text-base">End Time *</label>
        <input id="end_time" name="end_time" type="datetime-local" required
          class="w-full px-3 py-2 sm:py-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm sm:text-base touch-manipulation" />
      </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <label for="attendees_count" class="block mb-1 font-medium text-gray-700">Number of Attendees *</label>
        <input id="attendees_count" name="attendees_count" type="number" min="1" value="1" required
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
      </div>
      <div>
        <label for="recurring_type" class="block mb-1 font-medium text-gray-700">Recurring</label>
        <select id="recurring_type" name="recurring_type"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
          <option value="none">No Recurrence</option>
          <option value="daily">Daily</option>
          <option value="weekly">Weekly</option>
          <option value="monthly">Monthly</option>
        </select>
      </div>
    </div>
    <div id="recurring_end_container" class="hidden">
      <label for="recurring_end_date" class="block mb-1 font-medium text-gray-700">Recurring End Date</label>
      <input id="recurring_end_date" name="recurring_end_date" type="date"
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
    </div>
    <div>
      <label for="setup_requirements" class="block mb-1 font-medium text-gray-700">Setup Requirements *</label>
      <textarea id="setup_requirements" name="setup_requirements" rows="3" required
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        placeholder="Describe any special setup requirements (e.g., projector setup, seating arrangement, catering)"></textarea>
    </div>
    <div>
      <label for="special_requests" class="block mb-1 font-medium text-gray-700">Special Requests *</label>
      <textarea id="special_requests" name="special_requests" rows="3" required
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        placeholder="Any special requests or additional notes (e.g., accessibility needs, equipment requests)"></textarea>
    </div>
    <div class="flex flex-col sm:flex-row justify-end gap-3 sm:gap-4 pt-4 sm:pt-6 border-t border-gray-200">
      <button type="button" onclick="loadPage('Reservations')"
        class="w-full sm:w-auto px-4 py-2 sm:py-3 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 touch-manipulation transition-colors duration-200 text-sm sm:text-base">
        Cancel
      </button>
      <button type="button" onclick="(async function() {
        const form = document.getElementById('create-reservation-form');
        const formData = new FormData(form);
        const data = {};
        for (let [key, value] of formData.entries()) {
          if (value) data[key] = value;
        }
        try {
          const response = await fetch('/api/reservations/create.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data),
            credentials: 'same-origin'
          });
          const result = await response.json();
          if (response.ok && result.reservation_id) {
            popupSystem.success('Reservation created successfully!');
            loadPage('Reservations');
          } else {
            popupSystem.error(result.error || 'Unknown error');
          }
        } catch (error) {
          popupSystem.error('Network error: ' + error.message);
        }
      })()"
        class="w-full sm:w-auto px-4 py-2 sm:py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 touch-manipulation transition-colors duration-200 text-sm sm:text-base font-medium">
        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
        Create Reservation
      </button>
    </div>
  </form>
</div>

<script>
  document.getElementById('recurring_type').addEventListener('change', function() {
    const container = document.getElementById('recurring_end_container');
    if (this.value !== 'none') {
      container.classList.remove('hidden');
    } else {
      container.classList.add('hidden');
    }
  });
  const now = new Date();
  const currentDateTime = now.toISOString().slice(0, 16);
  document.getElementById('start_time').min = currentDateTime;
  document.getElementById('end_time').min = currentDateTime;
  document.getElementById('start_time').addEventListener('change', function() {
    document.getElementById('end_time').min = this.value;
  });
</script>