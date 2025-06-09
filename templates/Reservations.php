<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!isset($_SESSION['user_id'])) {
  header('Location: /');
  exit;
}

require_once __DIR__ . '/../lib/db.php';
$stmt = $pdo->prepare("
  SELECT r.*,
         u.name as user_name, u.surname as user_surname, u.email as user_email,
         rm.name as room_name
  FROM reservations r
  LEFT JOIN users u ON r.user_id = u.id
  LEFT JOIN rooms rm ON r.room_id = rm.id
  ORDER BY r.created_at DESC
");
$stmt->execute();
$reservations = $stmt->fetchAll();
$stmt = $pdo->prepare("
  SELECT status, COUNT(*) as count 
  FROM reservations 
  GROUP BY status
");
$stmt->execute();
$statusCounts = [];
foreach ($stmt->fetchAll() as $row) {
  $statusCounts[$row['status']] = $row['count'];
}
?>

<div class="space-y-6">
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
  <?php if (empty($reservations)): ?>
    <div class="bg-white rounded-lg shadow p-8 text-center">
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
              <tr class="hover:bg-gray-50" data-reservation-id="<?php echo $reservation['id']; ?>">
                <td class="px-6 py-4">
                  <div>
                    <div class="text-sm font-medium text-gray-900">
                      <?php echo htmlspecialchars($reservation['title']); ?>
                    </div>
                    <?php if ($reservation['description']): ?>
                      <div class="text-sm text-gray-500">
                        <?php echo htmlspecialchars($reservation['description']); ?>
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
                    <?php if ($reservation['status'] === 'pending'): ?>
                      <button onclick="(async () => {
                  try {
                    const confirmed = await popupSystem.confirm(
                      'Are you sure you want to approve this reservation?',
                      'Approve Reservation',
                      {
                        confirmText: 'Approve',
                        cancelText: 'Cancel',
                        type: 'info'
                      }
                    );
                    if (!confirmed) return;
                    const response = await fetch('/api/reservations/edit.php', {
                      method: 'POST',
                      headers: { 'Content-Type': 'application/json' },
                      body: JSON.stringify({
                        id: <?php echo $reservation['id']; ?>,
                        status: 'accepted'
                      })
                    });
                    const data = await response.json();
                    if (data.success) {
                      const row = this.closest('tr');
                      const statusCell = row.querySelector('td:nth-child(5) span');
                      statusCell.className = 'inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800';
                      statusCell.textContent = 'Accepted';
                      const actionButtons = row.querySelectorAll(`button[title='Approve'], button[title='Reject']`);
                  actionButtons.forEach(btn=> btn.style.display = 'none');
                  popupSystem.success('Reservation approved successfully!');
                  } else popupSystem.error('Error: ' + (data.error || 'Failed to approve reservation'));
                  } catch (error) {
                  popupSystem.error('Network error: ' + error.message);
                  }
                  })()" class="text-green-600 hover:text-green-900" title="Approve">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                      </button>
                      <button onclick="(async () => {
                  try {
                    const reason = await popupSystem.prompt(
                      'Please provide a reason for rejection:',
                      'Reject Reservation',
                      '',
                      {
                        required: true,
                        minLength: 5,
                        validator: (value) => {
                          if (value.trim().length < 5) {
                            return 'Please provide a detailed reason (at least 5 characters)';
                          }
                          return true;
                        }
                      }
                    );
                    if (reason === null) return;
                    const response = await fetch('/api/reservations/edit.php', {
                      method: 'POST',
                      headers: { 'Content-Type': 'application/json' },
                      body: JSON.stringify({
                        id: <?php echo $reservation['id']; ?>,
                        status: 'rejected',
                        cancellation_reason: reason
                      })
                    });
                    const data = await response.json();
                    if (data.success) {
                      const row = this.closest('tr');
                      const statusCell = row.querySelector('td:nth-child(5) span');
                      statusCell.className = 'inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800';
                      statusCell.textContent = 'Rejected';
                      const actionButtons = row.querySelectorAll(`button[title='Approve'], button[title='Reject']`);
                  actionButtons.forEach(btn=> btn.style.display = 'none');
                  popupSystem.success('Reservation rejected successfully!');
                  } else popupSystem.error('Error: ' + (data.error || 'Failed to reject reservation'));
                  } catch (error) {
                  popupSystem.error('Network error: ' + error.message);
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
                    <?php if (empty($reservation['parent_reservation_id'])): ?>
                      <button onclick="(async () => {
                        try {
                          const confirmed = await popupSystem.confirm(
                            'Are you sure you want to delete this reservation?',
                            'Delete Reservation', {
                              confirmText: 'Delete',
                              cancelText: 'Cancel',
                              type: 'danger'
                            }
                          );
                          if (!confirmed) return;
                          const response = await fetch('/api/reservations/delete.php', {
                            method: 'POST',
                            headers: {
                              'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({ id: <?php echo $reservation['id']; ?> })
                          });
                          const data = await response.json();
                          if (data.success) {
                            const row = document.querySelector(`tr[data-reservation-id='<?php echo $reservation['id']; ?>']`);
                            if (row) {
                              row.style.opacity = '0';
                              setTimeout(() => row.remove(), 300);
                            }
                            popupSystem.success('Reservation deleted successfully');
                          } else popupSystem.error('Error: ' + (data.error || 'Failed to delete reservation'));
                        } catch (error) {
                          popupSystem.error('Network error: ' + error.message);
                        }})()" class="text-gray-400 hover:text-red-600" title="Delete Reservation">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                          </path>
                        </svg>
                      </button>
                    <?php endif; ?>
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