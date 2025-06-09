<?php
if (session_status() === PHP_SESSION_NONE)
  session_start();
if (!isset($_SESSION['user_id'])) {
  echo '<p class="text-red-600">You must be logged in to view the calendar.</p>';
  return;
}
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/permissions.php';
require_once __DIR__ . '/../lib/reservation_utils.php';
if (!canViewReservations($_SESSION['user_id'])) {
  echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Access Denied</h1><p>You do not have permission to view the calendar.</p></div>';
  return;
}
function generateRecurringInstancesForCalendar($reservation, $startDate, $endDate)
{
  $instances = [];
  if ($reservation['recurring_type'] === 'none')
    return $instances;
  $originalStart = new DateTime($reservation['start_time']);
  $originalEnd = new DateTime($reservation['end_time']);
  $duration = $originalStart->diff($originalEnd);
  $recurringEndDate = !empty($reservation['recurring_end_date'])
    ? new DateTime($reservation['recurring_end_date'] . ' 23:59:59')
    : new DateTime('+10 years'); // Default to 10 years if no end date
  $calendarStart = new DateTime($startDate);
  $calendarEnd = new DateTime($endDate);
  $currentStart = clone $originalStart;
  if ($currentStart < $calendarStart) {
    switch ($reservation['recurring_type']) {
      case 'daily':
        $daysDiff = $calendarStart->diff($currentStart)->days;
        $currentStart->add(new DateInterval('P' . $daysDiff . 'D'));
        break;
      case 'weekly':
        $weeksDiff = floor($calendarStart->diff($currentStart)->days / 7);
        $currentStart->add(new DateInterval('P' . $weeksDiff . 'W'));
        break;
      case 'monthly':
        $monthsDiff = ($calendarStart->format('Y') - $currentStart->format('Y')) * 12 +
          ($calendarStart->format('n') - $currentStart->format('n'));
        if ($monthsDiff > 0) {
          $currentStart->add(new DateInterval('P' . $monthsDiff . 'M'));
        }
        break;
    }
  }
  while ($currentStart <= $calendarEnd && $currentStart <= $recurringEndDate) {
    $currentEnd = clone $currentStart;
    $currentEnd->add($duration);

    $instanceDate = $currentStart->format('Y-m-d');
    if ($instanceDate >= $startDate && $instanceDate <= $endDate) {
      $instance = $reservation; // Copy all reservation data
      $instance['start_time'] = $currentStart->format('Y-m-d H:i:s');
      $instance['end_time'] = $currentEnd->format('Y-m-d H:i:s');
      $instance['reservation_date'] = $instanceDate;
      $instance['is_recurring_instance'] = true;
      $instances[] = $instance;
    }
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
        break 2;
    }
  }

  return $instances;
}

$currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$currentYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
if ($currentMonth < 1 || $currentMonth > 12) $currentMonth = date('n');
if ($currentYear < 2020 || $currentYear > 2030) $currentYear = date('Y');
$prevMonth = $currentMonth - 1;
$prevYear = $currentYear;
if ($prevMonth < 1) {
  $prevMonth = 12;
  $prevYear--;
}
$nextMonth = $currentMonth + 1;
$nextYear = $currentYear;
if ($nextMonth > 12) {
  $nextMonth = 1;
  $nextYear++;
}

$monthName = date('F', mktime(0, 0, 0, $currentMonth, 1, $currentYear));
$firstDayOfMonth = mktime(0, 0, 0, $currentMonth, 1, $currentYear);
$daysInMonth = date('t', $firstDayOfMonth);
$firstDayOfWeek = date('w', $firstDayOfMonth);
$daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
$calendarStartDate = date('Y-m-d', strtotime("-$firstDayOfWeek days", $firstDayOfMonth));
$lastDayOfMonth = mktime(0, 0, 0, $currentMonth, $daysInMonth, $currentYear);
$lastDayOfWeek = date('w', $lastDayOfMonth);
$daysToAdd = $lastDayOfWeek == 6 ? 0 : 6 - $lastDayOfWeek;
$calendarEndDate = date('Y-m-d', strtotime("+$daysToAdd days", $lastDayOfMonth));
$firstDay = sprintf('%04d-%02d-01', $currentYear, $currentMonth);
$lastDay = date('Y-m-t', strtotime($firstDay));
$stmt = $pdo->prepare("
  SELECT
    r.id,
    r.title,
    r.description,
    r.start_time,
    r.end_time,
    r.status,
    r.room_id,
    r.recurring_type,
    r.recurring_end_date,
    r.parent_reservation_id,
    ro.name AS room_name,
    DATE(r.start_time) AS reservation_date
  FROM reservations r
  LEFT JOIN rooms ro ON r.room_id = ro.id
  WHERE r.status IN ('pending', 'accepted')
    AND (
      -- Regular reservations within date range
      (r.recurring_type = 'none' AND DATE(r.start_time) BETWEEN ? AND ?)
      OR
      -- Individual recurring instances within date range
      (r.parent_reservation_id IS NOT NULL AND DATE(r.start_time) BETWEEN ? AND ?)
      OR
      -- Parent recurring reservations that are active and might have instances in this range
      (r.recurring_type != 'none' AND r.parent_reservation_id IS NULL
       AND (r.recurring_end_date IS NULL OR r.recurring_end_date >= ?))
    )
  ORDER BY r.start_time ASC
");
$stmt->execute([$calendarStartDate, $calendarEndDate, $calendarStartDate, $calendarEndDate, $calendarStartDate]);
$dailyReservations = [];
$allReservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($allReservations as $reservation) {
  if ($reservation['recurring_type'] !== 'none' && $reservation['parent_reservation_id'] === null) {
    $instances = generateRecurringInstancesForCalendar($reservation, $calendarStartDate, $calendarEndDate);
    foreach ($instances as $instance) {
      $dailyReservations[$instance['reservation_date']][] = $instance;
    }
  } elseif ($reservation['parent_reservation_id'] !== null) {
    // This is an individual recurring instance stored in database - add directly
    $reservation['is_recurring_instance'] = true;
    $dailyReservations[$reservation['reservation_date']][] = $reservation;
  } else {
    // This is a regular reservation - add directly
    $dailyReservations[$reservation['reservation_date']][] = $reservation;
  }
}
?>

<div class="max-w-7xl mx-auto p-2 sm:p-4 lg:p-6">
  <div class="bg-white rounded-lg shadow-md mb-4 sm:mb-6">
    <div
      class="flex flex-col sm:flex-row sm:items-center sm:justify-between p-3 sm:p-4 lg:p-6 border-b space-y-3 sm:space-y-0">
      <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-4">
        <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900">Calendar</h1>
        <div class="text-sm sm:text-base lg:text-lg text-gray-600"><?php echo $monthName . ' ' . $currentYear; ?></div>
      </div>
      <div class="flex items-center justify-between sm:justify-end space-x-1 sm:space-x-2">
        <button onclick="loadPage('Calendar&month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>')"
          class="p-2 sm:p-3 rounded-md hover:bg-gray-100 text-gray-600 hover:text-gray-900 touch-manipulation transition-colors duration-200">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
          </svg>
        </button>
        <button onclick="loadPage('Calendar&month=<?php echo date('n'); ?>&year=<?php echo date('Y'); ?>')"
          class="px-3 sm:px-4 py-2 bg-blue-600 text-white hover:text-white focus:text-white rounded-md hover:bg-blue-700 text-sm sm:text-base touch-manipulation">
          Today
        </button>
        <button onclick="loadPage('Calendar&month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>')"
          class="p-2 sm:p-3 rounded-md hover:bg-gray-100 text-gray-600 hover:text-gray-900 touch-manipulation transition-colors duration-200">
          <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
          </svg>
        </button>
        <?php if (canCreateReservations($_SESSION['user_id'])): ?>
          <button onclick="loadPage('CreateReservation')"
            class="ml-1 sm:ml-2 px-2 sm:px-3 lg:px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 touch-manipulation transition-colors duration-200 text-xs sm:text-sm">
            <svg class="w-3 h-3 sm:w-4 sm:h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            <span class="hidden sm:inline">New</span>
            <span class="sm:hidden">+</span>
          </button>
        <?php endif; ?>
      </div>
    </div>

    <div class="p-3 sm:p-6">
      <!-- Desktop day headers -->
      <div class="hidden sm:grid grid-cols-7 gap-1 mb-2">
        <?php foreach ($daysOfWeek as $day): ?>
          <div class="p-3 text-center text-sm font-semibold text-gray-700 bg-gray-50 rounded"><?php echo $day; ?></div>
        <?php endforeach; ?>
      </div>

      <!-- Mobile day headers -->
      <div class="sm:hidden grid grid-cols-7 gap-1 mb-2">
        <?php foreach ($daysOfWeek as $day): ?>
          <div class="p-1 text-center text-xs font-semibold text-gray-700 bg-gray-50 rounded">
            <?php echo substr($day, 0, 1); ?></div>
        <?php endforeach; ?>
      </div>

      <div class="grid grid-cols-7 gap-1" id="calendar-grid">
        <?php
        $currentDate = new DateTime($calendarStartDate);
        $endDate = new DateTime($calendarEndDate);
        while ($currentDate <= $endDate):
          $date = $currentDate->format('Y-m-d');
          $day = $currentDate->format('j');
          $isCurrentMonth = $currentDate->format('n') == $currentMonth;
          $isToday = $date === date('Y-m-d');
          $reservations = $dailyReservations[$date] ?? [];
          if ($isToday) {
            $dayClass = 'bg-blue-50 border-blue-200';
            $textClass = 'text-blue-600';
          } elseif ($isCurrentMonth) {
            $dayClass = 'bg-white hover:bg-gray-50';
            $textClass = 'text-gray-900';
          } else {
            $dayClass = 'bg-gray-50 hover:bg-gray-100';
            $textClass = 'text-gray-400';
          }
        ?>
          <div
            class="h-14 sm:h-20 lg:h-24 border rounded cursor-pointer transition-all duration-200 ease-in-out hover:-translate-y-1 hover:shadow-md <?php echo $dayClass; ?> touch-manipulation active:scale-95"
            onclick="loadPage('DayDetail&date=<?php echo $date; ?>')"
            data-date="<?php echo $date; ?>"
            data-reservations='<?php echo json_encode($reservations); ?>'>
            <div class="p-1 sm:p-2 h-full flex flex-col group relative">
              <div class="text-xs sm:text-sm lg:text-base font-medium <?php echo $textClass; ?>">
                <?php echo $day; ?>
              </div>
              <div class="flex-1 mt-0.5 sm:mt-1 relative group">
                <?php if (count($reservations) > 0): ?>
                  <span
                    class="inline-block px-1 sm:px-2 py-0.5 sm:py-1 text-xs <?php echo $isCurrentMonth ? 'bg-blue-100 text-blue-800' : 'bg-gray-200 text-gray-600'; ?> rounded-full animate-[fadeIn_0.3s_ease-in] leading-tight font-medium">
                    <span
                      class="hidden sm:inline"><?php echo count($reservations) . ' reservation' . (count($reservations) > 1 ? 's' : ''); ?></span>
                    <span class="sm:hidden"><?php echo count($reservations); ?></span>
                  </span>
                  <!-- Desktop tooltip -->
                  <div
                    class="absolute z-50 hidden lg:group-hover:block w-72 p-3 bg-white border border-gray-300 rounded-lg shadow-lg text-sm text-left -top-[15px] left-1/2 -translate-x-1/2 -translate-y-full pointer-events-none">
                    <?php foreach ($reservations as $res): ?>
                      <div class="mb-2 pointer-events-auto">
                        <div class="font-semibold text-gray-800"><?php echo htmlspecialchars($res['title']); ?></div>
                        <div class="text-gray-600">
                          <?php echo date('H:i', strtotime($res['start_time'])) . ' - ' . date('H:i', strtotime($res['end_time'])); ?>
                        </div>
                        <div class="text-gray-500 italic">
                          <?php echo htmlspecialchars($res['room_name'] ?? 'Unknown room'); ?>
                        </div>
                        <?php if (!empty($res['description'])): ?>
                          <div class="text-gray-700 mt-1"><?php echo nl2br(htmlspecialchars($res['description'])); ?></div>
                        <?php endif; ?>
                        <?php if ($res['parent_reservation_id'] !== null || !empty($res['is_recurring_instance'])): ?>
                          <div class="text-xs text-purple-600 mt-1 font-medium">
                            🔄 Recurring (instance)
                          </div>
                        <?php elseif (!empty($res['recurring_type']) && $res['recurring_type'] !== 'none'): ?>
                          <div class="text-xs text-purple-600 mt-1 font-medium">
                            🔄 <?php echo ucfirst($res['recurring_type']); ?> recurring
                          </div>
                        <?php endif; ?>
                      </div>
                      <?php if ($res !== end($reservations)): ?>
                        <hr class="my-2 border-gray-200">
                      <?php endif; ?>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php
          $currentDate->add(new DateInterval('P1D'));
        endwhile;
        ?>
      </div>
    </div>
  </div>
</div>

<!-- Mobile Reservation Details Modal -->
<div id="mobile-reservation-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden lg:hidden">
  <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
    <div class="fixed inset-0 transition-opacity" onclick="closeMobileModal()"></div>
    <div class="inline-block align-bottom bg-white rounded-t-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:rounded-lg">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-medium text-gray-900" id="modal-date"></h3>
        <button onclick="closeMobileModal()" class="text-gray-400 hover:text-gray-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>
      <div id="modal-reservations" class="space-y-3 max-h-96 overflow-y-auto"></div>
      <div class="mt-4 pt-4 border-t">
        <button onclick="closeMobileModal(); loadPage('DayDetail&date=' + document.getElementById('mobile-reservation-modal').dataset.date);"
          class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
          View Full Day Details
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  // Mobile calendar interactions
  let touchStartX = 0;
  let touchStartY = 0;
  let touchEndX = 0;
  let touchEndY = 0;

  // Add touch event listeners for swipe gestures
  document.getElementById('calendar-grid').addEventListener('touchstart', function(e) {
    touchStartX = e.changedTouches[0].screenX;
    touchStartY = e.changedTouches[0].screenY;
  }, false);

  document.getElementById('calendar-grid').addEventListener('touchend', function(e) {
    touchEndX = e.changedTouches[0].screenX;
    touchEndY = e.changedTouches[0].screenY;
    handleSwipe();
  }, false);

  function handleSwipe() {
    const deltaX = touchEndX - touchStartX;
    const deltaY = touchEndY - touchStartY;
    const minSwipeDistance = 50;

    // Only handle horizontal swipes that are longer than vertical swipes
    if (Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > minSwipeDistance) {
      if (deltaX > 0) {
        // Swipe right - go to previous month
        loadPage('Calendar&month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>');
      } else {
        // Swipe left - go to next month
        loadPage('Calendar&month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>');
      }
    }
  }

  // Mobile modal functions
  function showMobileReservations(date, reservations) {
    if (window.innerWidth >= 1024) return; // Only show on mobile/tablet

    const modal = document.getElementById('mobile-reservation-modal');
    const modalDate = document.getElementById('modal-date');
    const modalReservations = document.getElementById('modal-reservations');

    modal.dataset.date = date;
    modalDate.textContent = new Date(date).toLocaleDateString('en-US', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });

    modalReservations.innerHTML = '';

    if (reservations.length === 0) {
      modalReservations.innerHTML = '<p class="text-gray-500 text-center py-4">No reservations for this day</p>';
    } else {
      reservations.forEach(res => {
        const div = document.createElement('div');
        div.className = 'bg-gray-50 rounded-lg p-3 border';
        div.innerHTML = `
        <div class="font-semibold text-gray-900">${res.title}</div>
        <div class="text-sm text-gray-600 mt-1">
          ${new Date(res.start_time).toLocaleTimeString('en-US', {hour: '2-digit', minute: '2-digit'})} -
          ${new Date(res.end_time).toLocaleTimeString('en-US', {hour: '2-digit', minute: '2-digit'})}
        </div>
        <div class="text-sm text-gray-500 italic">${res.room_name || 'Unknown room'}</div>
        ${res.description ? `<div class="text-sm text-gray-700 mt-2">${res.description}</div>` : ''}
        ${res.parent_reservation_id !== null || res.is_recurring_instance ?
          '<div class="text-xs text-purple-600 mt-1 font-medium">🔄 Recurring (instance)</div>' :
          (res.recurring_type && res.recurring_type !== 'none' ?
            `<div class="text-xs text-purple-600 mt-1 font-medium">🔄 ${res.recurring_type.charAt(0).toUpperCase() + res.recurring_type.slice(1)} recurring</div>` : '')}
      `;
        modalReservations.appendChild(div);
      });
    }

    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
  }

  function closeMobileModal() {
    const modal = document.getElementById('mobile-reservation-modal');
    modal.classList.add('hidden');
    document.body.style.overflow = '';
  }

  // Override calendar day clicks on mobile to show modal first
  document.addEventListener('DOMContentLoaded', function() {
    const calendarDays = document.querySelectorAll('[data-date]');
    calendarDays.forEach(day => {
      day.addEventListener('click', function(e) {
        if (window.innerWidth < 1024) { // Mobile/tablet
          e.preventDefault();
          e.stopPropagation();
          const date = this.dataset.date;
          const reservations = JSON.parse(this.dataset.reservations || '[]');
          showMobileReservations(date, reservations);
        }
      });
    });
  });
</script>