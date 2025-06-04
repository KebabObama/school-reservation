<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
if (!isset($_SESSION['user_id'])) {
  echo '<p class="text-red-600">You must be logged in to view day details.</p>';
  return;
}

require_once __DIR__ . '/../lib/permissions.php';

// Get date from URL parameters
$selectedDate = $_GET['date'] ?? date('Y-m-d');

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
  echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Error</h1><p>Invalid date format.</p></div>';
  return;
}

// Format date for display
$dateObj = new DateTime($selectedDate);
$formattedDate = $dateObj->format('l, F j, Y');
$dayOfWeek = $dateObj->format('l');
?>

<div class="max-w-6xl mx-auto p-6">
  <!-- Header -->
  <div class="bg-white rounded-lg shadow-md mb-6">
    <div class="flex items-center justify-between p-6 border-b">
      <div class="flex items-center space-x-4">
        <button onclick="loadPage('Calendar')"
          class="p-2 rounded-md hover:bg-gray-100 text-gray-600 hover:text-gray-900">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
          </svg>
        </button>
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Day Schedule</h1>
          <p class="text-lg text-gray-600"><?php echo $formattedDate; ?></p>
        </div>
      </div>

      <div class="flex items-center space-x-2">
        <?php if (canCreateReservations($_SESSION['user_id'])): ?>
          <button onclick="loadPage('CreateReservation')"
            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            New Reservation
          </button>
        <?php endif; ?>
      </div>
    </div>

    <!-- Day Content -->
    <div class="p-6">
      <div id="day-content">
        <div class="flex items-center justify-center py-8">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
          <span class="ml-2 text-gray-600">Loading reservations...</span>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  const selectedDate = '<?php echo $selectedDate; ?>';

  // Load day detail data
  function loadDayDetail() {
    fetch(`/api/reservations/get-day-detail.php?date=${selectedDate}`)
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          displayDayReservations(data);
        } else {
          displayError(data.error);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        displayError('Failed to load day details');
        // Show error notification
        if (typeof NotificationCard !== 'undefined') {
          NotificationCard.show('Failed to load day details', 'error');
        }
      });
  }

  // Display reservations for the day
  function displayDayReservations(data) {
    const contentDiv = document.getElementById('day-content');

    if (data.reservations.length === 0) {
      contentDiv.innerHTML = `
      <div class="text-center py-12">
        <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
        </svg>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No reservations</h3>
        <p class="text-gray-600">There are no reservations scheduled for this day.</p>
      </div>
    `;
      return;
    }

    let html = `
    <div class="mb-4">
      <h2 class="text-xl font-semibold text-gray-900">${data.count} Reservation${data.count > 1 ? 's' : ''}</h2>
    </div>
    <div class="space-y-4">
  `;

    data.reservations.forEach(reservation => {
      const statusColor = getStatusColor(reservation.status);
      const startTime = formatTime(reservation.start_time_only);
      const endTime = formatTime(reservation.end_time_only);

      html += `
      <div class="bg-white border rounded-lg p-4 hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between">
          <div class="flex-1">
            <div class="flex items-center space-x-3 mb-2">
              <h3 class="text-lg font-semibold text-gray-900">${escapeHtml(reservation.title)}</h3>
              <span class="px-2 py-1 text-xs font-medium rounded-full ${statusColor}">
                ${reservation.status.charAt(0).toUpperCase() + reservation.status.slice(1)}
              </span>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-600">
              <div class="space-y-2">
                <div class="flex items-center">
                  <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                  <span class="font-medium">${startTime} - ${endTime}</span>
                </div>
                
                <div class="flex items-center">
                  <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                  </svg>
                  <span>${escapeHtml(reservation.room_name)}</span>
                  ${reservation.building_name ? ` - ${escapeHtml(reservation.building_name)}` : ''}
                  ${reservation.floor_name ? `, ${escapeHtml(reservation.floor_name)}` : ''}
                </div>
                
                <div class="flex items-center">
                  <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                  </svg>
                  <span>${escapeHtml(reservation.user_name)} ${escapeHtml(reservation.user_surname)}</span>
                </div>
              </div>
              
              <div class="space-y-2">
                ${reservation.attendees_count ? `
                  <div class="flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <span>${reservation.attendees_count} attendee${reservation.attendees_count > 1 ? 's' : ''}</span>
                  </div>
                ` : ''}
                
                ${reservation.purpose_name ? `
                  <div class="flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                    </svg>
                    <span>${escapeHtml(reservation.purpose_name)}</span>
                  </div>
                ` : ''}
              </div>
            </div>
            
            ${reservation.description ? `
              <div class="mt-3 p-3 bg-gray-50 rounded text-sm">
                <strong>Description:</strong> ${escapeHtml(reservation.description)}
              </div>
            ` : ''}
            
            ${reservation.setup_requirements ? `
              <div class="mt-2 p-3 bg-yellow-50 rounded text-sm">
                <strong>Setup Requirements:</strong> ${escapeHtml(reservation.setup_requirements)}
              </div>
            ` : ''}
            
            ${reservation.special_requests ? `
              <div class="mt-2 p-3 bg-blue-50 rounded text-sm">
                <strong>Special Requests:</strong> ${escapeHtml(reservation.special_requests)}
              </div>
            ` : ''}
          </div>
          
          <div class="ml-4 flex flex-col space-y-2">
            ${canEditReservations() ? `
              <button onclick="loadPage('EditReservation&id=${reservation.id}')" 
                      class="text-blue-600 hover:text-blue-800 text-sm">
                Edit
              </button>
            ` : ''}
          </div>
        </div>
      </div>
    `;
    });

    html += '</div>';
    contentDiv.innerHTML = html;
  }

  // Display error message
  function displayError(message) {
    const contentDiv = document.getElementById('day-content');
    contentDiv.innerHTML = `
    <div class="text-center py-12">
      <svg class="w-16 h-16 mx-auto text-red-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
      </svg>
      <h3 class="text-lg font-medium text-red-900 mb-2">Error</h3>
      <p class="text-red-600">${escapeHtml(message)}</p>
    </div>
  `;
  }

  // Helper functions
  function getStatusColor(status) {
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

  function formatTime(timeString) {
    if (!timeString) return '';
    const [hours, minutes] = timeString.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${minutes} ${ampm}`;
  }

  function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  function canEditReservations() {
    // This would need to be populated from PHP session data
    return <?php echo canEditReservations($_SESSION['user_id']) ? 'true' : 'false'; ?>;
  }

  // Load day detail when page loads
  document.addEventListener('DOMContentLoaded', function() {
    loadDayDetail();
  });
</script>