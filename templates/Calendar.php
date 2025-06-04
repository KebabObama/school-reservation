<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
if (!isset($_SESSION['user_id'])) {
  echo '<p class="text-red-600">You must be logged in to view the calendar.</p>';
  return;
}

// Get current month and year or from URL parameters
$currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$currentYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Validate month and year
if ($currentMonth < 1 || $currentMonth > 12) {
  $currentMonth = date('n');
}
if ($currentYear < 2020 || $currentYear > 2030) {
  $currentYear = date('Y');
}

// Calculate previous and next month/year
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

// Get month name and other calendar info
$monthName = date('F', mktime(0, 0, 0, $currentMonth, 1, $currentYear));
$firstDayOfMonth = mktime(0, 0, 0, $currentMonth, 1, $currentYear);
$daysInMonth = date('t', $firstDayOfMonth);
$firstDayOfWeek = date('w', $firstDayOfMonth); // 0 = Sunday, 6 = Saturday

// Days of the week
$daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
?>

<style>
  .calendar-day {
    transition: all 0.2s ease;
  }

  .calendar-day:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  }

  .reservation-badge {
    animation: fadeIn 0.3s ease-in;
  }

  @keyframes fadeIn {
    from {
      opacity: 0;
      transform: scale(0.8);
    }

    to {
      opacity: 1;
      transform: scale(1);
    }
  }
</style>

<div class="max-w-7xl mx-auto p-6">
  <!-- Calendar Header -->
  <div class="bg-white rounded-lg shadow-md mb-6">
    <div class="flex items-center justify-between p-6 border-b">
      <div class="flex items-center space-x-4">
        <h1 class="text-3xl font-bold text-gray-900">Calendar</h1>
        <div class="text-lg text-gray-600"><?php echo $monthName . ' ' . $currentYear; ?></div>
      </div>

      <div class="flex items-center space-x-2">
        <!-- Previous Month Button -->
        <button onclick="loadCalendar(<?php echo $prevMonth; ?>, <?php echo $prevYear; ?>)"
          class="p-2 rounded-md hover:bg-gray-100 text-gray-600 hover:text-gray-900">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
          </svg>
        </button>

        <!-- Today Button -->
        <button onclick="loadCalendar(<?php echo date('n'); ?>, <?php echo date('Y'); ?>)"
          class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
          Today
        </button>

        <!-- Next Month Button -->
        <button onclick="loadCalendar(<?php echo $nextMonth; ?>, <?php echo $nextYear; ?>)"
          class="p-2 rounded-md hover:bg-gray-100 text-gray-600 hover:text-gray-900">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
          </svg>
        </button>
      </div>
    </div>

    <!-- Calendar Grid -->
    <div class="p-6">
      <!-- Days of Week Header -->
      <div class="grid grid-cols-7 gap-1 mb-2">
        <?php foreach ($daysOfWeek as $day): ?>
          <div class="p-3 text-center text-sm font-semibold text-gray-700 bg-gray-50 rounded">
            <?php echo $day; ?>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Calendar Days -->
      <div class="grid grid-cols-7 gap-1" id="calendar-grid">
        <!-- Empty cells for days before the first day of the month -->
        <?php for ($i = 0; $i < $firstDayOfWeek; $i++): ?>
          <div class="h-24 bg-gray-50 rounded"></div>
        <?php endfor; ?>

        <!-- Days of the month -->
        <?php for ($day = 1; $day <= $daysInMonth; $day++): ?>
          <?php
          $currentDate = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $day);
          $isToday = $currentDate === date('Y-m-d');
          $dayClass = $isToday ? 'bg-blue-50 border-blue-200' : 'bg-white hover:bg-gray-50';
          ?>
          <div class="h-24 border rounded cursor-pointer calendar-day <?php echo $dayClass; ?>"
            onclick="openDayDetail('<?php echo $currentDate; ?>')"
            data-date="<?php echo $currentDate; ?>">
            <div class="p-2 h-full flex flex-col">
              <div class="text-sm font-medium <?php echo $isToday ? 'text-blue-600' : 'text-gray-900'; ?>">
                <?php echo $day; ?>
              </div>
              <div class="flex-1 mt-1">
                <div class="reservation-count text-xs text-gray-600" data-date="<?php echo $currentDate; ?>">
                  <!-- Reservation count will be loaded via JavaScript -->
                </div>
              </div>
            </div>
          </div>
        <?php endfor; ?>
      </div>
    </div>
  </div>
</div>

<script>
  let currentCalendarMonth = <?php echo $currentMonth; ?>;
  let currentCalendarYear = <?php echo $currentYear; ?>;

  // Load calendar data
  function loadCalendar(month, year) {
    if (month && year) {
      currentCalendarMonth = month;
      currentCalendarYear = year;
      loadPage(`Calendar&month=${month}&year=${year}`);
    } else {
      loadCalendarData();
    }
  }

  // Load reservation data for the current month
  function loadCalendarData() {
    fetch(`/api/reservations/get-by-date.php?month=${currentCalendarMonth}&year=${currentCalendarYear}`)
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          updateCalendarWithReservations(data.daily_counts);
        } else {
          console.error('Error loading calendar data:', data.error);
          // Show error notification
          if (typeof NotificationCard !== 'undefined') {
            NotificationCard.show('Failed to load calendar data: ' + data.error, 'error');
          }
        }
      })
      .catch(error => {
        console.error('Error:', error);
        // Show error notification
        if (typeof NotificationCard !== 'undefined') {
          NotificationCard.show('Failed to load calendar data', 'error');
        }
      });
  }

  // Update calendar cells with reservation counts
  function updateCalendarWithReservations(dailyCounts) {
    document.querySelectorAll('.reservation-count').forEach(element => {
      const date = element.getAttribute('data-date');
      const count = dailyCounts[date] || 0;

      if (count > 0) {
        element.innerHTML = `<span class="inline-block px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full reservation-badge">${count} reservation${count > 1 ? 's' : ''}</span>`;
      } else {
        element.innerHTML = '';
      }
    });
  }

  // Open day detail view
  function openDayDetail(date) {
    loadPage(`DayDetail&date=${date}`);
  }

  // Load calendar data when page loads
  document.addEventListener('DOMContentLoaded', function() {
    loadCalendarData();
  });
</script>