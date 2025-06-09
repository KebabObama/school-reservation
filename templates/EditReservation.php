<?php
if (session_status() === PHP_SESSION_NONE)
  session_start();
if (!isset($_SESSION['user_id'])) {
  echo '<p class="text-red-600">You must be logged in to edit a reservation.</p>';
  return;
}

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/permissions.php';
$reservationId = $_GET['id'] ?? null;
if (!$reservationId) {
  echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Error</h1><p>Reservation ID is required.</p></div>';
  return;
}

try {
  $stmt = $pdo->prepare("
    SELECT r.*, u.name as user_name, u.surname as user_surname, u.email as user_email,
           rm.name as room_name, rp.name as purpose_name
    FROM reservations r
    JOIN users u ON r.user_id = u.id
    JOIN rooms rm ON r.room_id = rm.id
    LEFT JOIN reservation_purposes rp ON r.purpose_id = rp.id
    WHERE r.id = ?
  ");
  $stmt->execute([$reservationId]);
  $reservation = $stmt->fetch();
  if (!$reservation) {
    echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Error</h1><p>Reservation not found.</p></div>';
    return;
  }
  if (!canEditSpecificReservation($_SESSION['user_id'], $reservation['user_id'])) {
    echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Access Denied</h1><p>You do not have permission to edit this reservation.</p></div>';
    return;
  }
  $rooms = $pdo->query("SELECT id, name FROM rooms WHERE is_active = 1 ORDER BY name")->fetchAll();
  $purposes = $pdo->query("SELECT id, name FROM reservation_purposes ORDER BY name")->fetchAll();
} catch (Exception $e) {
  echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Error</h1><p>Unable to load reservation data.</p></div>';
  return;
}
?>

<form id="edit-reservation-form" class="space-y-6 max-w-4xl mx-auto p-6 bg-white rounded-md shadow-md">
  <div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-semibold text-gray-900">Edit Reservation:
      <?php echo htmlspecialchars($reservation['title']); ?></h2>
    <button type="button" onclick="loadPage('Reservations')" class="text-gray-600 hover:text-gray-800">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
      </svg>
    </button>
  </div>
  <input type="hidden" id="reservation_id" value="<?php echo $reservation['id']; ?>">
  <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
      <div>
        <span class="font-medium text-blue-800">Created by:</span>
        <span
          class="text-blue-700"><?php echo htmlspecialchars($reservation['user_name'] . ' ' . $reservation['user_surname']); ?></span>
      </div>
      <div>
        <span class="font-medium text-blue-800">Status:</span>
        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
          <?php
          switch ($reservation['status']) {
            case 'accepted':
              echo 'bg-green-100 text-green-800';
              break;
            case 'pending':
              echo 'bg-yellow-100 text-yellow-800';
              break;
            case 'rejected':
              echo 'bg-red-100 text-red-800';
              break;
            case 'cancelled':
              echo 'bg-gray-100 text-gray-800';
              break;
            default:
              echo 'bg-gray-100 text-gray-800';
          }
          ?>">
          <?php echo ucfirst($reservation['status']); ?>
        </span>
      </div>
      <div>
        <span class="font-medium text-blue-800">Created:</span>
        <span class="text-blue-700"><?php echo date('M j, Y H:i', strtotime($reservation['created_at'])); ?></span>
      </div>
    </div>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div>
      <label for="room_id" class="block mb-1 font-medium text-gray-700">Room *</label>
      <select id="room_id" name="room_id" required
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        <option value="">Select a room</option>
        <?php foreach ($rooms as $room): ?>
          <option value="<?php echo $room['id']; ?>"
            <?php echo $reservation['room_id'] == $room['id'] ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($room['name']); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label for="purpose_id" class="block mb-1 font-medium text-gray-700">Purpose *</label>
      <select id="purpose_id" name="purpose_id" required
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        <option value="">Select a purpose</option>
        <?php foreach ($purposes as $purpose): ?>
          <option value="<?php echo $purpose['id']; ?>"
            <?php echo $reservation['purpose_id'] == $purpose['id'] ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($purpose['name']); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <div>
    <label for="title" class="block mb-1 font-medium text-gray-700">Title *</label>
    <input id="title" name="title" type="text" required value="<?php echo htmlspecialchars($reservation['title']); ?>"
      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
      placeholder="Enter reservation title" />
  </div>
  <div>
    <label for="description" class="block mb-1 font-medium text-gray-700">Description</label>
    <textarea id="description" name="description" rows="3"
      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
      placeholder="Enter detailed description of the event/meeting"><?php echo htmlspecialchars($reservation['description'] ?? ''); ?></textarea>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div>
      <label for="start_time" class="block mb-1 font-medium text-gray-700">Start Time *</label>
      <input id="start_time" name="start_time" type="datetime-local" required
        value="<?php echo date('Y-m-d\TH:i', strtotime($reservation['start_time'])); ?>"
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
    </div>
    <div>
      <label for="end_time" class="block mb-1 font-medium text-gray-700">End Time *</label>
      <input id="end_time" name="end_time" type="datetime-local" required
        value="<?php echo date('Y-m-d\TH:i', strtotime($reservation['end_time'])); ?>"
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
    </div>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div>
      <label for="attendees_count" class="block mb-1 font-medium text-gray-700">Number of Attendees *</label>
      <input id="attendees_count" name="attendees_count" type="number" min="1" required
        value="<?php echo $reservation['attendees_count']; ?>"
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
    </div>
    <div>
      <label for="recurring_type" class="block mb-1 font-medium text-gray-700">Recurring</label>
      <select id="recurring_type" name="recurring_type"
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        <option value="none" <?php echo $reservation['recurring_type'] === 'none' ? 'selected' : ''; ?>>No Recurrence
        </option>
        <option value="daily" <?php echo $reservation['recurring_type'] === 'daily' ? 'selected' : ''; ?>>Daily</option>
        <option value="weekly" <?php echo $reservation['recurring_type'] === 'weekly' ? 'selected' : ''; ?>>Weekly
        </option>
        <option value="monthly" <?php echo $reservation['recurring_type'] === 'monthly' ? 'selected' : ''; ?>>Monthly
        </option>
      </select>
    </div>
  </div>
  <div id="recurring_end_container" class="<?php echo $reservation['recurring_type'] === 'none' ? 'hidden' : ''; ?>">
    <label for="recurring_end_date" class="block mb-1 font-medium text-gray-700">Recurring End Date</label>
    <input id="recurring_end_date" name="recurring_end_date" type="date"
      value="<?php echo $reservation['recurring_end_date'] ? date('Y-m-d', strtotime($reservation['recurring_end_date'])) : ''; ?>"
      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
  </div>
  <div>
    <label for="setup_requirements" class="block mb-1 font-medium text-gray-700">Setup Requirements</label>
    <textarea id="setup_requirements" name="setup_requirements" rows="3"
      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
      placeholder="Describe any special setup requirements (e.g., projector setup, seating arrangement, catering)"><?php echo htmlspecialchars($reservation['setup_requirements'] ?? ''); ?></textarea>
  </div>
  <div>
    <label for="special_requests" class="block mb-1 font-medium text-gray-700">Special Requests</label>
    <textarea id="special_requests" name="special_requests" rows="3"
      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
      placeholder="Any special requests or additional notes (e.g., accessibility needs, equipment requests)"><?php echo htmlspecialchars($reservation['special_requests'] ?? ''); ?></textarea>
  </div>
  <?php if (canReviewReservationStatus($_SESSION['user_id'])): ?>
    <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-md">
      <h3 class="text-lg font-medium text-blue-800 mb-3">Reservation Status Management</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label for="status" class="block mb-1 font-medium text-gray-700">Status</label>
          <select id="status" name="status"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="pending" <?php echo $reservation['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
            <option value="accepted" <?php echo $reservation['status'] === 'accepted' ? 'selected' : ''; ?>>Accepted
            </option>
            <option value="rejected" <?php echo $reservation['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected
            </option>
            <option value="cancelled" <?php echo $reservation['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled
            </option>
          </select>
        </div>
        <div id="cancellation_reason_container"
          class="<?php echo $reservation['status'] !== 'cancelled' && $reservation['status'] !== 'rejected' ? 'hidden' : ''; ?>">
          <label for="cancellation_reason" class="block mb-1 font-medium text-gray-700">Cancellation/Rejection
            Reason</label>
          <textarea id="cancellation_reason" name="cancellation_reason" rows="2"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Reason for cancellation or rejection"><?php echo htmlspecialchars($reservation['cancellation_reason'] ?? ''); ?></textarea>
        </div>
      </div>
    </div>
  <?php endif; ?>
  <div class="flex justify-end space-x-4 pt-4 border-t border-gray-200">
    <button type="button" onclick="loadPage('Reservations')"
      class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">Cancel</button>
    <button type="button" onclick="(async function() {
        const form = document.getElementById('edit-reservation-form');
        const formData = new FormData(form);
        const data = { id: document.getElementById('reservation_id').value };
        for (let [key, value] of formData.entries())
          if (value) data[key] = value;
        try {
          const response = await fetch('/api/reservations/edit.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data),
            credentials: 'same-origin'
          });
          const result = await response.json();
          if (response.ok) {
            popupSystem.success('Reservation updated successfully!');
            loadPage('Reservations');
          } else popupSystem.error(result.error || 'Unknown error');
        } catch (error) {
          popupSystem.error('Network error: ' + error.message);
        }
      })()"
      class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">Update
      Reservation</button>
  </div>
</form>
<script>
  document.getElementById('recurring_type').addEventListener('change', function() {
    const container = document.getElementById('recurring_end_container');
    if (this.value !== 'none') {
      container.classList.remove('hidden');
    } else {
      container.classList.add('hidden');
    }
  });
  document.getElementById('status')?.addEventListener('change', function() {
    const container = document.getElementById('cancellation_reason_container');
    if (container) {
      if (this.value === 'cancelled' || this.value === 'rejected') {
        container.classList.remove('hidden');
      } else {
        container.classList.add('hidden');
      }
    }
  });
  document.getElementById('start_time').addEventListener('change', function() {
    document.getElementById('end_time').min = this.value;
  });
  document.getElementById('attendees_count').addEventListener('input', function() {
    if (this.value < 1) {
      this.classList.add('border-red-300');
    } else {
      this.classList.remove('border-red-300');
    }
  });
</script>