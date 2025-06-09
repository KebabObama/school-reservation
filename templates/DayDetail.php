<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
if (!isset($_SESSION['user_id'])) {
  echo '<p class="text-red-600">You must be logged in to view day details.</p>';
  return;
}
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/permissions.php';
require_once __DIR__ . '/../lib/reservation_utils.php';
if (!canViewReservations($_SESSION['user_id'])) {
  echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Access Denied</h1><p>You do not have permission to view day details.</p></div>';
  return;
}
$selectedDate = $_GET['date'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
  echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Error</h1><p>Invalid date format.</p></div>';
  return;
}
$dateObj = new DateTime($selectedDate);
$formattedDate = $dateObj->format('l, F j, Y');

// Function to generate recurring instances for day detail display (dynamic, unlimited)
function generateRecurringInstancesForDay($reservation, $targetDate)
{
  $instances = [];

  if ($reservation['recurring_type'] === 'none') {
    return $instances;
  }

  $originalStart = new DateTime($reservation['start_time']);
  $originalEnd = new DateTime($reservation['end_time']);
  $duration = $originalStart->diff($originalEnd);
  $targetDateTime = new DateTime($targetDate);

  // Calculate the recurring end date (use a far future date if not specified)
  $recurringEndDate = !empty($reservation['recurring_end_date'])
    ? new DateTime($reservation['recurring_end_date'] . ' 23:59:59')
    : new DateTime('+10 years'); // Default to 10 years if no end date

  // Check if the target date is within the recurring period (compare dates only)
  $originalStartDate = new DateTime($originalStart->format('Y-m-d'));
  if ($targetDateTime < $originalStartDate || $targetDateTime > $recurringEndDate) {
    return $instances;
  }

  // Calculate if there should be an instance on the target date
  $shouldHaveInstance = false;
  $instanceStart = null;

  switch ($reservation['recurring_type']) {
    case 'daily':
      if ($targetDateTime >= $originalStartDate) {
        $shouldHaveInstance = true;
        $instanceStart = clone $targetDateTime;
        $instanceStart->setTime(
          (int)$originalStart->format('H'),
          (int)$originalStart->format('i'),
          (int)$originalStart->format('s')
        );
      }
      break;

    case 'weekly':
      if ($targetDateTime >= $originalStartDate && $originalStart->format('w') === $targetDateTime->format('w')) {
        $shouldHaveInstance = true;
        $instanceStart = clone $targetDateTime;
        $instanceStart->setTime(
          (int)$originalStart->format('H'),
          (int)$originalStart->format('i'),
          (int)$originalStart->format('s')
        );
      }
      break;

    case 'monthly':
      if (
        $targetDateTime >= $originalStartDate &&
        $targetDateTime->format('j') === $originalStart->format('j')
      ) {
        $shouldHaveInstance = true;
        $instanceStart = clone $targetDateTime;
        $instanceStart->setTime(
          (int)$originalStart->format('H'),
          (int)$originalStart->format('i'),
          (int)$originalStart->format('s')
        );
      }
      break;
  }

  if ($shouldHaveInstance && $instanceStart) {
    $instanceEnd = clone $instanceStart;
    $instanceEnd->add($duration);

    $instance = $reservation; // Copy all reservation data
    $instance['start_time'] = $instanceStart->format('Y-m-d H:i:s');
    $instance['end_time'] = $instanceEnd->format('Y-m-d H:i:s');
    $instance['start_time_only'] = $instanceStart->format('H:i:s');
    $instance['end_time_only'] = $instanceEnd->format('H:i:s');
    $instance['is_recurring_instance'] = true;
    $instances[] = $instance;
  }

  return $instances;
}
$dayOfWeek = $dateObj->format('l');

// Fetch all reservations (regular, recurring parent reservations, and recurring instances)
$stmt = $pdo->prepare("
  SELECT
    r.id,
    r.title,
    r.description,
    r.start_time,
    r.end_time,
    r.status,
    r.attendees_count,
    r.setup_requirements,
    r.special_requests,
    r.recurring_type,
    r.recurring_end_date,
    r.parent_reservation_id,
    u.name as user_name,
    u.surname as user_surname,
    u.email as user_email,
    rm.name as room_name,
    rm.capacity as room_capacity,
    b.name as building_name,
    f.name as floor_name,
    rp.name as purpose_name,
    TIME(r.start_time) as start_time_only,
    TIME(r.end_time) as end_time_only
  FROM reservations r
  LEFT JOIN users u ON r.user_id = u.id
  LEFT JOIN rooms rm ON r.room_id = rm.id
  LEFT JOIN floors f ON rm.floor_id = f.id
  LEFT JOIN buildings b ON f.building_id = b.id
  LEFT JOIN reservation_purposes rp ON r.purpose_id = rp.id
  WHERE r.status IN ('pending', 'accepted')
    AND (
      -- Regular reservations for the selected date
      (r.recurring_type = 'none' AND DATE(r.start_time) = ?)
      OR
      -- Individual recurring instances for the selected date
      (r.parent_reservation_id IS NOT NULL AND DATE(r.start_time) = ?)
      OR
      -- Parent recurring reservations that might have instances on the selected date
      (r.recurring_type != 'none' AND r.parent_reservation_id IS NULL
       AND (r.recurring_end_date IS NULL OR r.recurring_end_date >= ?))
    )
  ORDER BY r.start_time ASC
");
$stmt->execute([$selectedDate, $selectedDate, $selectedDate]);
$allReservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process reservations and generate recurring instances for the selected date
$reservations = [];
foreach ($allReservations as $reservation) {
  if ($reservation['recurring_type'] !== 'none' && $reservation['parent_reservation_id'] === null) {
    // This is a parent recurring reservation - generate instances for the selected date
    $instances = generateRecurringInstancesForDay($reservation, $selectedDate);
    foreach ($instances as $instance) {
      $reservations[] = $instance;
    }
  } elseif ($reservation['parent_reservation_id'] !== null) {
    // This is an individual recurring instance stored in database - add directly
    $reservation['is_recurring_instance'] = true;
    $reservations[] = $reservation;
  } else {
    // This is a regular reservation - add directly
    $reservations[] = $reservation;
  }
}

// Sort by start time
usort($reservations, function ($a, $b) {
  return strtotime($a['start_time']) - strtotime($b['start_time']);
});

function getStatusColor($status)
{
  switch ($status) {
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

function formatTime($timeString)
{
  if (!$timeString) return '';
  $parts = explode(':', $timeString);
  $hours = (int)$parts[0];
  $minutes = $parts[1];
  $ampm = $hours >= 12 ? 'PM' : 'AM';
  $displayHour = $hours % 12 ?: 12;
  return $displayHour . ':' . $minutes . ' ' . $ampm;
}
?>
<div class="max-w-7xl mx-auto p-2 sm:p-4 lg:p-6">
  <!-- Header -->
  <div class="bg-white rounded-lg shadow-md mb-4 sm:mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between p-3 sm:p-4 lg:p-6 border-b space-y-3 sm:space-y-0">
      <div class="flex items-center space-x-3 sm:space-x-4">
        <button onclick="loadPage('Calendar')"
          class="p-2 rounded-md hover:bg-gray-100 text-gray-600 hover:text-gray-900 touch-manipulation transition-colors duration-200">
          <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
          </svg>
        </button>
        <div>
          <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900">Day Schedule</h1>
          <p class="text-sm sm:text-base lg:text-lg text-gray-600"><?php echo $formattedDate; ?></p>
        </div>
      </div>
      <div class="flex items-center justify-between sm:justify-end space-x-2">
        <?php if (canCreateReservations($_SESSION['user_id'])): ?>
          <button onclick="loadPage('CreateReservation')"
            class="px-3 sm:px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 touch-manipulation transition-colors duration-200 text-sm">
            <svg class="w-3 h-3 sm:w-4 sm:h-4 inline mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            <span class="hidden sm:inline">New Reservation</span>
            <span class="sm:hidden">+</span>
          </button>
        <?php endif; ?>
      </div>
    </div>
    <!-- Day Content -->
    <div class="p-3 sm:p-4 lg:p-6">
      <div id="day-content">
        <?php if (count($reservations) === 0): ?>
          <div class="text-center py-8 sm:py-12">
            <svg class="w-12 h-12 sm:w-16 sm:h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            <h3 class="text-base sm:text-lg font-medium text-gray-900 mb-2">No reservations</h3>
            <p class="text-sm sm:text-base text-gray-600">There are no reservations scheduled for this day.</p>
            <?php if (canCreateReservations($_SESSION['user_id'])): ?>
              <button onclick="loadPage('CreateReservation')"
                class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 touch-manipulation transition-colors duration-200 text-sm">
                Create First Reservation
              </button>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <div class="mb-4 sm:mb-6">
            <h2 class="text-lg sm:text-xl font-semibold text-gray-900"><?php echo count($reservations); ?> Reservation<?php echo count($reservations) > 1 ? 's' : ''; ?></h2>
          </div>
          <div class="space-y-3 sm:space-y-4">
            <?php foreach ($reservations as $reservation): ?>
              <div class="bg-white border rounded-lg p-3 sm:p-4 lg:p-6 hover:shadow-md transition-all duration-200 active:scale-[0.99]">
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between space-y-4 lg:space-y-0">
                  <div class="flex-1">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-3 mb-3 space-y-2 sm:space-y-0">
                      <h3 class="text-lg sm:text-xl font-semibold text-gray-900"><?php echo htmlspecialchars($reservation['title']); ?></h3>
                      <div class="flex flex-wrap gap-2">
                        <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo getStatusColor($reservation['status']); ?>">
                          <?php echo ucfirst($reservation['status']); ?>
                        </span>
                        <?php if ($reservation['parent_reservation_id'] !== null || !empty($reservation['is_recurring_instance'])): ?>
                          <span class="px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800">
                            🔄 Recurring (instance)
                          </span>
                        <?php elseif (!empty($reservation['recurring_type']) && $reservation['recurring_type'] !== 'none'): ?>
                          <span class="px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800">
                            🔄 <?php echo ucfirst($reservation['recurring_type']); ?> recurring (parent)
                          </span>
                        <?php endif; ?>
                      </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4 text-sm text-gray-600">
                      <div class="space-y-2">
                        <div class="flex items-center">
                          <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                          </svg>
                          <span class="font-medium"><?php echo formatTime($reservation['start_time_only']) . ' - ' . formatTime($reservation['end_time_only']); ?></span>
                        </div>
                        <div class="flex items-center">
                          <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                          </svg>
                          <span class="break-words"><?php echo htmlspecialchars($reservation['room_name']); ?>
                            <?php if ($reservation['building_name']): ?>
                              - <?php echo htmlspecialchars($reservation['building_name']); ?>
                            <?php endif; ?>
                            <?php if ($reservation['floor_name']): ?>
                              , <?php echo htmlspecialchars($reservation['floor_name']); ?>
                            <?php endif; ?></span>
                        </div>
                        <div class="flex items-center">
                          <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                          </svg>
                          <span class="break-words"><?php echo htmlspecialchars($reservation['user_name'] . ' ' . $reservation['user_surname']); ?></span>
                        </div>
                      </div>
                      <div class="space-y-2">
                        <?php if ($reservation['attendees_count']): ?>
                          <div class="flex items-center">
                            <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            <span><?php echo $reservation['attendees_count']; ?> attendee<?php echo $reservation['attendees_count'] > 1 ? 's' : ''; ?></span>
                          </div>
                        <?php endif; ?>
                        <?php if ($reservation['purpose_name']): ?>
                          <div class="flex items-center">
                            <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                            </svg>
                            <span class="break-words"><?php echo htmlspecialchars($reservation['purpose_name']); ?></span>
                          </div>
                        <?php endif; ?>
                        <?php if ($reservation['room_capacity']): ?>
                          <div class="flex items-center">
                            <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                            <span>Capacity: <?php echo $reservation['room_capacity']; ?></span>
                          </div>
                        <?php endif; ?>
                      </div>
                    </div>
                    <?php if ($reservation['description']): ?>
                      <div class="mt-3 p-3 bg-gray-50 rounded text-sm">
                        <strong>Description:</strong> <?php echo nl2br(htmlspecialchars($reservation['description'])); ?>
                      </div>
                    <?php endif; ?>
                    <?php if ($reservation['setup_requirements']): ?>
                      <div class="mt-2 p-3 bg-yellow-50 rounded text-sm">
                        <strong>Setup Requirements:</strong> <?php echo nl2br(htmlspecialchars($reservation['setup_requirements'])); ?>
                      </div>
                    <?php endif; ?>
                    <?php if ($reservation['special_requests']): ?>
                      <div class="mt-2 p-3 bg-blue-50 rounded text-sm">
                        <strong>Special Requests:</strong> <?php echo nl2br(htmlspecialchars($reservation['special_requests'])); ?>
                      </div>
                    <?php endif; ?>
                  </div>
                  <div class="lg:ml-4 flex flex-col sm:flex-row gap-2 mt-4 lg:mt-0 pt-3 lg:pt-0 border-t lg:border-t-0 border-gray-200">
                    <?php if (canEditReservations($_SESSION['user_id'])): ?>
                      <button onclick="loadPage('EditReservation&id=<?php echo $reservation['id']; ?>')"
                        class="px-3 py-2 text-blue-600 hover:text-blue-800 text-sm border border-blue-200 rounded-md hover:bg-blue-50 touch-manipulation transition-colors duration-200 flex items-center justify-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Edit
                      </button>
                    <?php endif; ?>
                    <button onclick="loadPage('Calendar')"
                      class="px-3 py-2 text-gray-600 hover:text-gray-800 text-sm border border-gray-200 rounded-md hover:bg-gray-50 touch-manipulation transition-colors duration-200 flex items-center justify-center">
                      <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                      </svg>
                      <span class="hidden sm:inline">Back to Calendar</span>
                      <span class="sm:hidden">Calendar</span>
                    </button>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>