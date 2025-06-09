<?php
if (session_status() === PHP_SESSION_NONE)
  session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: /');
  exit;
}
?>
<style>
  .bg-purple-25 {
    background-color: #faf5ff;
  }

  .bg-purple-10 {
    background-color: #f3e8ff;
  }

  .hover\:bg-purple-25:hover {
    background-color: #faf5ff;
  }

  /* Enhanced tooltip styling */
  .tooltip-light {
    animation: fadeInUp 0.2s ease-out;
    backdrop-filter: blur(8px);
    background-color: rgba(255, 255, 255, 0.95);
  }

  @keyframes fadeInUp {
    from {
      opacity: 0;
      transform: translate(-50%, 8px);
    }

    to {
      opacity: 1;
      transform: translate(-50%, 0);
    }
  }

  .tooltip-arrow-border {
    filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.1));
  }
</style>
<?php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/permissions.php';
require_once __DIR__ . '/../lib/reservation_utils.php';
if (!canViewReservations($_SESSION['user_id'])) {
  echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Access Denied</h1><p>You do not have permission to view reservations.</p></div>';
  return;
}
function getNextFutureOccurrence($reservation)
{
  if ($reservation['recurring_type'] === 'none' || $reservation['parent_reservation_id'] !== null)
    return $reservation['start_time'];
  $now = new DateTime();
  $originalStart = new DateTime($reservation['start_time']);
  $recurringEndDate = !empty($reservation['recurring_end_date'])
    ? new DateTime($reservation['recurring_end_date'] . ' 23:59:59')
    : new DateTime('+10 years');
  if ($originalStart > $now)
    return $reservation['start_time'];
  $currentStart = clone $originalStart;
  $maxIterations = 1000;
  $iterations = 0;
  while ($currentStart <= $now && $currentStart <= $recurringEndDate && $iterations < $maxIterations) {
    $iterations++;
    switch ($reservation['recurring_type']) {
      case 'daily':
        $currentStart->add(new DateInterval('P1D'));
        break;
      case 'weekly':
        $currentStart->add(new DateInterval('P1W'));
        break;
      case 'monthly':
        $currentStart->add(new DateInterval('P1M'));
        break;
      default:
        return $reservation['start_time'];
    }
  }
  if ($currentStart > $now && $currentStart <= $recurringEndDate)
    return $currentStart->format('Y-m-d H:i:s');
  return $reservation['start_time'];
}
$stmt = $pdo->prepare("
  SELECT r.*,
  u.name as user_name, u.surname as user_surname, u.email as user_email,
  rm.name as room_name,
  r.recurring_type,
  r.recurring_end_date,
  r.parent_reservation_id
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
<div class="space-y-4 sm:space-y-6 p-2 sm:p-4 lg:p-6">
  <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center space-y-3 sm:space-y-0">
    <div>
      <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Reservations</h1>
      <p class="text-sm sm:text-base text-gray-600">Manage room reservations and bookings</p>
    </div>
    <?php if (canCreateReservations($_SESSION['user_id'])): ?>
      <button onclick="loadPage('CreateReservation')"
        class="bg-blue-600 hover:bg-blue-700 text-white px-3 sm:px-4 py-2 rounded-lg flex items-center justify-center touch-manipulation transition-colors duration-200 text-sm sm:text-base">
        <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        <span class="hidden sm:inline">New Reservation</span>
        <span class="sm:hidden">New</span>
      </button>
    <?php endif; ?>
  </div>
  <?php if (empty($reservations)): ?>
    <div class="bg-white rounded-lg shadow p-8 text-center">
      <h3 class="text-lg font-medium text-gray-900 mb-2">No reservations found</h3>
      <p class="text-gray-600 mb-4">No reservations have been made yet.</p>
      <?php if (canCreateReservations($_SESSION['user_id'])): ?>
        <button onclick="loadPage('CreateReservation')"
          class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
          Create Reservation
        </button>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <!-- Mobile Card View -->
    <div class="lg:hidden space-y-4">
      <?php foreach ($reservations as $reservation):
        $isRecurring = $reservation['recurring_type'] !== 'none' && $reservation['parent_reservation_id'] === null;
        $isRecurringInstance = $reservation['parent_reservation_id'] !== null;
        $displayDate = $isRecurring ? getNextFutureOccurrence($reservation) : $reservation['start_time'];
        $cardClass = $isRecurring ? 'border-l-4 border-purple-400 bg-purple-50' : ($isRecurringInstance ? 'border-l-4 border-purple-200 bg-purple-25' : 'border-l-4 border-blue-400');
      ?>
        <div class="bg-white rounded-lg shadow border <?php echo $cardClass; ?>" data-reservation-id="<?php echo $reservation['id']; ?>">
          <!-- Header with title and status -->
          <div class="p-4 border-b border-gray-100">
            <div class="flex justify-between items-start">
              <div class="flex-1">
                <div class="flex items-center gap-2 mb-1">
                  <h3 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($reservation['title']); ?></h3>
                  <?php if ($isRecurring): ?>
                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-purple-100 text-purple-800 rounded-full">
                      📅 <?php echo ucfirst($reservation['recurring_type']); ?>
                    </span>
                  <?php elseif ($isRecurringInstance): ?>
                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-purple-50 text-purple-600 rounded-full">
                      📅 Instance
                    </span>
                  <?php endif; ?>
                </div>
                <?php if ($reservation['description']): ?>
                  <p class="text-sm text-gray-600"><?php echo htmlspecialchars($reservation['description']); ?></p>
                <?php endif; ?>
              </div>
              <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ml-3 flex-shrink-0
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
          </div>

          <!-- Main content area -->
          <div class="p-4">
            <div class="grid grid-cols-2 gap-4 text-sm">
              <!-- Left column -->
              <div class="space-y-3">
                <div>
                  <div class="text-gray-500 font-medium text-xs uppercase tracking-wide">User</div>
                  <div class="text-gray-900 font-medium"><?php echo htmlspecialchars($reservation['user_name'] . ' ' . $reservation['user_surname']); ?></div>
                  <div class="text-gray-500 text-xs"><?php echo htmlspecialchars($reservation['user_email']); ?></div>
                </div>

                <div>
                  <div class="text-gray-500 font-medium text-xs uppercase tracking-wide">Room</div>
                  <div class="text-gray-900 font-medium"><?php echo htmlspecialchars($reservation['room_name']); ?></div>
                </div>
              </div>

              <!-- Right column -->
              <div class="space-y-3">
                <div>
                  <div class="text-gray-500 font-medium text-xs uppercase tracking-wide">Date & Time</div>
                  <div class="text-gray-900 font-medium <?php echo $isRecurring ? 'text-purple-800' : ''; ?>">
                    <?php echo date('M j, Y', strtotime($displayDate)); ?>
                  </div>
                  <div class="text-gray-500 text-xs">
                    <?php echo date('H:i', strtotime($reservation['start_time'])); ?> -
                    <?php echo date('H:i', strtotime($reservation['end_time'])); ?>
                  </div>

                  <?php if ($isRecurring && $displayDate !== $reservation['start_time']): ?>
                    <div class="mt-2 p-2 bg-purple-50 rounded text-xs">
                      <div class="text-purple-700 font-medium">Next occurrence</div>
                      <div class="text-purple-600">Original: <?php echo date('M j, Y', strtotime($reservation['start_time'])); ?></div>
                    </div>
                  <?php elseif ($isRecurring): ?>
                    <div class="mt-2 p-2 bg-purple-50 rounded text-xs">
                      <div class="text-purple-700 font-medium">Recurring <?php echo $reservation['recurring_type']; ?></div>
                      <?php if ($reservation['recurring_end_date']): ?>
                        <div class="text-purple-600">Until: <?php echo date('M j, Y', strtotime($reservation['recurring_end_date'])); ?></div>
                      <?php else: ?>
                        <div class="text-purple-600">No end date</div>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>

          <!-- Action Buttons -->
          <?php if (($reservation['status'] === 'pending' && canReviewReservationStatus($_SESSION['user_id'])) || canEditReservations($_SESSION['user_id']) || (empty($reservation['parent_reservation_id']) && canDeleteReservations($_SESSION['user_id']))): ?>
            <div class="px-4 py-3 bg-gray-50 border-t border-gray-100 rounded-b-lg">
              <div class="flex gap-2">
                <?php if ($reservation['status'] === 'pending' && canReviewReservationStatus($_SESSION['user_id'])): ?>
                  <button onclick="approveReservation(<?php echo $reservation['id']; ?>)"
                    class="flex-1 px-3 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 text-sm font-medium touch-manipulation transition-colors flex items-center justify-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Approve
                  </button>
                  <button onclick="rejectReservation(<?php echo $reservation['id']; ?>)"
                    class="flex-1 px-3 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 text-sm font-medium touch-manipulation transition-colors flex items-center justify-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    Reject
                  </button>
                <?php endif; ?>

                <?php if (canEditReservations($_SESSION['user_id'])): ?>
                  <button onclick="loadPage('EditReservation&id=<?php echo $reservation['id']; ?>')"
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium touch-manipulation transition-colors flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Edit
                  </button>
                <?php endif; ?>

                <?php if (empty($reservation['parent_reservation_id']) && canDeleteReservations($_SESSION['user_id'])): ?>
                  <button onclick="deleteReservation(<?php echo $reservation['id']; ?>)"
                    class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 text-sm font-medium touch-manipulation transition-colors flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Delete
                  </button>
                <?php endif; ?>
              </div>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Desktop Table View -->
    <div class="hidden lg:block bg-white rounded-lg shadow overflow-hidden">
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
            <?php foreach ($reservations as $reservation):
              $isRecurring = $reservation['recurring_type'] !== 'none' && $reservation['parent_reservation_id'] === null;
              $isRecurringInstance = $reservation['parent_reservation_id'] !== null;
              $displayDate = $isRecurring ? getNextFutureOccurrence($reservation) : $reservation['start_time'];
              $rowClass = $isRecurring ? 'hover:bg-purple-50' : ($isRecurringInstance ? 'hover:bg-purple-25' : 'hover:bg-gray-50');
            ?>
              <tr class="<?php echo $rowClass; ?>" data-reservation-id="<?php echo $reservation['id']; ?>">
                <td class="px-6 py-4">
                  <div>
                    <div class="text-sm font-medium text-gray-900 flex gap-2 items-center">
                      <?php echo htmlspecialchars($reservation['title']); ?>
                      <?php if ($isRecurring): ?>
                        <span
                          class="inline-flex items-center px-2 py-1 text-xs font-medium bg-purple-100 text-purple-800 rounded-full">
                          🔄 <div><?php echo ucfirst($reservation['recurring_type']); ?></div>
                        </span>
                      <?php elseif ($isRecurringInstance): ?>
                        <span
                          class="inline-flex items-center px-2 py-1 text-xs font-medium bg-purple-50 text-purple-600 rounded-full">
                          🔄 Instance
                        </span>
                      <?php endif; ?>
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
                  <div
                    class="text-sm text-gray-900 <?php echo $isRecurring ? 'text-purple-800 font-medium' : ''; ?> relative group">
                    <div><?php echo date('M j, Y', strtotime($displayDate)); ?></div>
                    <?php if ($isRecurring && $displayDate !== $reservation['start_time']): ?>
                      <div class="text-xs text-purple-600">Next occurrence</div>
                      <!-- Tooltip showing original date -->
                      <div
                        class="absolute z-50 hidden group-hover:block w-52 p-3 tooltip-light border border-gray-200 text-gray-700 text-sm rounded-lg shadow-xl -top-12 left-1/2 transform -translate-x-1/2 pointer-events-none">
                        <div class="font-medium text-gray-800 mb-1">📅 Original Date:</div>
                        <div class="text-gray-600"><?php echo date('M j, Y', strtotime($reservation['start_time'])); ?></div>
                        <!-- Arrow pointing down with enhanced styling -->
                        <div
                          class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-white tooltip-arrow-border">
                        </div>
                        <div
                          class="absolute top-full left-1/2 transform -translate-x-1/2 translate-y-px w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-gray-200">
                        </div>
                      </div>
                    <?php endif; ?>
                  </div>
                  <div class="text-sm text-gray-500">
                    <div><?php echo date('H:i', strtotime($reservation['start_time'])); ?> -
                      <?php echo date('H:i', strtotime($reservation['end_time'])); ?></div>
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
                    <div><?php echo ucfirst($reservation['status']); ?></div>
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <div class="flex justify-end space-x-2">
                    <?php if ($reservation['status'] === 'pending' && canReviewReservationStatus($_SESSION['user_id'])): ?>
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
                    <?php if (canEditReservations($_SESSION['user_id'])): ?>
                      <button onclick="loadPage('EditReservation&id=<?php echo $reservation['id']; ?>')"
                        class="text-gray-400 hover:text-blue-600" title="Edit Reservation">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                          </path>
                        </svg>
                      </button>
                    <?php endif; ?>
                    <?php if (empty($reservation['parent_reservation_id']) && canDeleteReservations($_SESSION['user_id'])): ?>
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

<script>
  // Mobile-friendly action functions
  async function approveReservation(reservationId) {
    try {
      const confirmed = await popupSystem.confirm(
        'Are you sure you want to approve this reservation?',
        'Approve Reservation', {
          confirmText: 'Approve',
          cancelText: 'Cancel',
          type: 'info'
        }
      );
      if (!confirmed) return;

      const response = await fetch('/api/reservations/edit.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          id: reservationId,
          status: 'accepted'
        })
      });

      const data = await response.json();
      if (data.success) {
        // Update both mobile card and desktop table if present
        updateReservationStatus(reservationId, 'accepted', 'Accepted');
        popupSystem.success('Reservation approved successfully!');
      } else {
        popupSystem.error('Error: ' + (data.error || 'Failed to approve reservation'));
      }
    } catch (error) {
      popupSystem.error('Network error: ' + error.message);
    }
  }

  async function rejectReservation(reservationId) {
    try {
      const reason = await popupSystem.prompt(
        'Please provide a reason for rejection:',
        'Reject Reservation',
        '', {
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
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          id: reservationId,
          status: 'rejected',
          cancellation_reason: reason
        })
      });

      const data = await response.json();
      if (data.success) {
        // Update both mobile card and desktop table if present
        updateReservationStatus(reservationId, 'rejected', 'Rejected');
        popupSystem.success('Reservation rejected successfully!');
      } else {
        popupSystem.error('Error: ' + (data.error || 'Failed to reject reservation'));
      }
    } catch (error) {
      popupSystem.error('Network error: ' + error.message);
    }
  }

  async function deleteReservation(reservationId) {
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
        body: JSON.stringify({
          id: reservationId
        })
      });

      const data = await response.json();
      if (data.success) {
        // Remove both mobile card and desktop table row if present
        const mobileCard = document.querySelector(`div[data-reservation-id='${reservationId}']`);
        const desktopRow = document.querySelector(`tr[data-reservation-id='${reservationId}']`);

        if (mobileCard) {
          mobileCard.style.opacity = '0';
          mobileCard.style.transform = 'translateX(-100%)';
          setTimeout(() => mobileCard.remove(), 300);
        }
        if (desktopRow) {
          desktopRow.style.opacity = '0';
          setTimeout(() => desktopRow.remove(), 300);
        }

        popupSystem.success('Reservation deleted successfully');
      } else {
        popupSystem.error('Error: ' + (data.error || 'Failed to delete reservation'));
      }
    } catch (error) {
      popupSystem.error('Network error: ' + error.message);
    }
  }

  function updateReservationStatus(reservationId, status, statusText) {
    // Update mobile card status
    const mobileCard = document.querySelector(`div[data-reservation-id='${reservationId}']`);
    if (mobileCard) {
      const statusBadge = mobileCard.querySelector('.inline-flex.px-2.py-1');
      if (statusBadge) {
        statusBadge.textContent = statusText;
        statusBadge.className = `inline-flex px-2 py-1 text-xs font-semibold rounded-full ml-2 ${getStatusClasses(status)}`;
      }

      // Hide action buttons
      const actionButtons = mobileCard.querySelectorAll('button[onclick*="approve"], button[onclick*="reject"]');
      actionButtons.forEach(btn => btn.style.display = 'none');
    }

    // Update desktop table status
    const desktopRow = document.querySelector(`tr[data-reservation-id='${reservationId}']`);
    if (desktopRow) {
      const statusCell = desktopRow.querySelector('td:nth-child(5) span');
      if (statusCell) {
        statusCell.className = `inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getStatusClasses(status)}`;
        statusCell.textContent = statusText;
      }

      // Hide action buttons
      const actionButtons = desktopRow.querySelectorAll(`button[title='Approve'], button[title='Reject']`);
      actionButtons.forEach(btn => btn.style.display = 'none');
    }
  }

  function getStatusClasses(status) {
    switch (status) {
      case 'accepted':
        return 'bg-green-100 text-green-800';
      case 'pending':
        return 'bg-yellow-100 text-yellow-800';
      case 'rejected':
        return 'bg-red-100 text-red-800';
      case 'cancelled':
        return 'bg-gray-100 text-gray-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  }

  // Add touch-friendly interactions
  document.addEventListener('DOMContentLoaded', function() {
    // Add swipe-to-reveal functionality for mobile cards
    const mobileCards = document.querySelectorAll('[data-reservation-id]');
    mobileCards.forEach(card => {
      let startX = 0;
      let currentX = 0;
      let isSwipping = false;

      card.addEventListener('touchstart', function(e) {
        startX = e.touches[0].clientX;
        isSwipping = true;
      });

      card.addEventListener('touchmove', function(e) {
        if (!isSwipping) return;
        currentX = e.touches[0].clientX;
        const diffX = currentX - startX;

        // Only allow small horizontal movement for better UX
        if (Math.abs(diffX) > 10) {
          e.preventDefault();
        }
      });

      card.addEventListener('touchend', function() {
        isSwipping = false;
      });
    });
  });
</script>