<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!isset($_SESSION['user_id'])) {
  header('Location: /');
  exit;
}

require_once __DIR__ . '/../lib/db.php';

// Get reservations with related data
try {
  $reservations = $pdo->query("
        SELECT r.*, u.name as user_name, u.surname as user_surname, u.email as user_email,
               rm.name as room_name, rp.name as purpose_name,
               approver.name as approver_name, approver.surname as approver_surname
        FROM reservations r
        JOIN users u ON r.user_id = u.id
        JOIN rooms rm ON r.room_id = rm.id
        LEFT JOIN reservation_purposes rp ON r.purpose_id = rp.id
        LEFT JOIN users approver ON r.approved_by = approver.id
        ORDER BY r.start_time DESC
        LIMIT 50
    ")->fetchAll();
} catch (Exception $e) {
  $reservations = [];
}

// Get status counts
try {
  $statusCounts = $pdo->query("
        SELECT status, COUNT(*) as count
        FROM reservations
        GROUP BY status
    ")->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) {
  $statusCounts = [];
}
?>

<div class="space-y-6">
  <!-- Header -->
  <div class="flex justify-between items-center">
    <div>
      <h1 class="text-3xl font-bold text-gray-900">Reservations</h1>
      <p class="text-gray-600">Manage room reservations and bookings</p>
    </div>
    <button onclick="loadPage('CreateReservation')"
      class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
      <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
      </svg>
      New Reservation
    </button>
  </div>

  <!-- Status Overview -->
  <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
    <div class="bg-white rounded-lg shadow p-4">
      <div class="flex items-center">
        <div class="p-2 rounded-full bg-yellow-100 text-yellow-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
        </div>
        <div class="ml-3">
          <p class="text-sm font-medium text-gray-600">Pending</p>
          <p class="text-lg font-semibold text-gray-900"><?php echo $statusCounts['pending'] ?? 0; ?></p>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-lg shadow p-4">
      <div class="flex items-center">
        <div class="p-2 rounded-full bg-green-100 text-green-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
          </svg>
        </div>
        <div class="ml-3">
          <p class="text-sm font-medium text-gray-600">Accepted</p>
          <p class="text-lg font-semibold text-gray-900"><?php echo $statusCounts['accepted'] ?? 0; ?></p>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-lg shadow p-4">
      <div class="flex items-center">
        <div class="p-2 rounded-full bg-red-100 text-red-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </div>
        <div class="ml-3">
          <p class="text-sm font-medium text-gray-600">Rejected</p>
          <p class="text-lg font-semibold text-gray-900"><?php echo $statusCounts['rejected'] ?? 0; ?></p>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-lg shadow p-4">
      <div class="flex items-center">
        <div class="p-2 rounded-full bg-gray-100 text-gray-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728">
            </path>
          </svg>
        </div>
        <div class="ml-3">
          <p class="text-sm font-medium text-gray-600">Cancelled</p>
          <p class="text-lg font-semibold text-gray-900"><?php echo $statusCounts['cancelled'] ?? 0; ?></p>
        </div>
      </div>
    </div>
  </div>

  <!-- Filters -->
  <div class="bg-white rounded-lg shadow p-4">
    <div class="flex flex-wrap gap-4">
      <div class="flex-1 min-w-64">
        <input type="text" placeholder="Search reservations..."
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>
      <select class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        <option value="">All Status</option>
        <option value="pending">Pending</option>
        <option value="accepted">Accepted</option>
        <option value="rejected">Rejected</option>
        <option value="cancelled">Cancelled</option>
      </select>
      <select class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        <option value="">All Rooms</option>
      </select>
      <input type="date"
        class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
  </div>

  <!-- Reservations Table -->
  <?php if (empty($reservations)): ?>
  <div class="bg-white rounded-lg shadow p-8 text-center">
    <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
    </svg>
    <h3 class="text-lg font-medium text-gray-900 mb-2">No reservations found</h3>
    <p class="text-gray-600 mb-4">No reservations have been made yet.</p>
    <button onclick="loadPage('CreateReservation')"
      class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
      Create Reservation
    </button>
  </div>
  <?php else: ?>
  <div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Reservation
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              User
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Room
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Date & Time
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Status
            </th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
              Actions
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <?php foreach ($reservations as $reservation): ?>
          <tr class="hover:bg-gray-50">
            <td class="px-6 py-4">
              <div>
                <div class="text-sm font-medium text-gray-900">
                  <?php echo htmlspecialchars($reservation['title']); ?>
                </div>
                <?php if ($reservation['purpose_name']): ?>
                <div class="text-sm text-gray-500">
                  <?php echo htmlspecialchars($reservation['purpose_name']); ?>
                </div>
                <?php endif; ?>
                <?php if ($reservation['attendees_count'] > 1): ?>
                <div class="text-xs text-gray-400">
                  <?php echo $reservation['attendees_count']; ?> attendees
                </div>
                <?php endif; ?>
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm font-medium text-gray-900">
                <?php echo htmlspecialchars($reservation['user_name'] . ' ' . $reservation['user_surname']); ?>
              </div>
              <div class="text-sm text-gray-500">
                <?php echo htmlspecialchars($reservation['user_email']); ?>
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm font-medium text-gray-900">
                <?php echo htmlspecialchars($reservation['room_name']); ?>
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm text-gray-900">
                <?php echo date('M j, Y', strtotime($reservation['start_time'])); ?>
              </div>
              <div class="text-sm text-gray-500">
                <?php echo date('H:i', strtotime($reservation['start_time'])); ?> -
                <?php echo date('H:i', strtotime($reservation['end_time'])); ?>
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
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
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <div class="flex justify-end space-x-2">
                <button class="text-blue-600 hover:text-blue-900" title="View Details">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                    </path>
                  </svg>
                </button>
                <?php if ($reservation['status'] === 'pending'): ?>
                <button onclick="(async function() {
                        try {
                          const response = await fetch('/api/reservations/edit.php', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify({
                              id: <?php echo $reservation['id']; ?>,
                              status: 'accepted'
                            }),
                            credentials: 'same-origin'
                          });
                          
                          const result = await response.json();
                          if (response.ok) {
                            alert('Reservation approved successfully!');
                            location.reload();
                          } else {
                            alert('Error: ' + (result.error || 'Unknown error'));
                          }
                        } catch (error) {
                          alert('Network error: ' + error.message);
                        }
                      })()" class="text-green-600 hover:text-green-900" title="Approve">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                  </svg>
                </button>
                <button onclick="(async function() {
                        const reason = prompt('Please provide a reason for rejection:');
                        if (reason === null) return; // User cancelled
                        
                        try {
                          const response = await fetch('/api/reservations/edit.php', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify({
                              id: <?php echo $reservation['id']; ?>,
                              status: 'rejected',
                              cancellation_reason: reason || null
                            }),
                            credentials: 'same-origin'
                          });
                          
                          const result = await response.json();
                          if (response.ok) {
                            alert('Reservation rejected successfully!');
                            location.reload();
                          } else {
                            alert('Error: ' + (result.error || 'Unknown error'));
                          }
                        } catch (error) {
                          alert('Network error: ' + error.message);
                        }
                      })()" class="text-red-600 hover:text-red-900" title="Reject">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                    </path>
                  </svg>
                </button>
                <?php endif; ?>
                <button onclick="loadPage('EditReservation&id=<?php echo $reservation['id']; ?>')"
                  class="text-gray-400 hover:text-blue-600" title="Edit Reservation">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                    </path>
                  </svg>
                </button>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>
</div>

<script>
// No global updateReservationStatus function needed anymore
</script>