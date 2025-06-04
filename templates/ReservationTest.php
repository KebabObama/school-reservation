<?php
// Test page for reservation functionality
// This is for testing purposes only
?>

<div class="space-y-6">
  <!-- Header -->
  <div class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-lg p-6 text-white">
    <h1 class="text-3xl font-bold mb-2">Reservation System Test</h1>
    <p class="text-blue-100">Test reservation acceptance and rejection functionality.</p>
  </div>

  <!-- Test Controls -->
  <div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-xl font-semibold text-gray-900 mb-4">Test Controls</h2>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <button onclick="testApproveReservation()" 
              class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
        Test Approve (ID: 1)
      </button>
      
      <button onclick="testRejectReservation()" 
              class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
        Test Reject (ID: 1)
      </button>
      
      <button onclick="testAPIDirectly()" 
              class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
        Test API Directly
      </button>
    </div>
  </div>

  <!-- Debug Output -->
  <div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-xl font-semibold text-gray-900 mb-4">Debug Output</h2>
    <div id="debug-output" class="bg-gray-100 p-4 rounded-md font-mono text-sm min-h-32">
      Ready for testing...
    </div>
    <button onclick="clearDebug()" class="mt-2 px-3 py-1 bg-gray-500 text-white rounded text-sm">
      Clear Debug
    </button>
  </div>

  <!-- Sample Reservation Data -->
  <div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-xl font-semibold text-gray-900 mb-4">Sample Reservation Data</h2>
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr id="test-row-1">
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">1</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Test Reservation</td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span id="status-1" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                Pending
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <div class="flex justify-end space-x-2">
                <button onclick="approveReservation(1)" class="text-green-600 hover:text-green-900" title="Approve">
                  ✓ Approve
                </button>
                <button onclick="rejectReservation(1)" class="text-red-600 hover:text-red-900" title="Reject">
                  ✗ Reject
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
function debugLog(message) {
  const debugOutput = document.getElementById('debug-output');
  const timestamp = new Date().toLocaleTimeString();
  debugOutput.innerHTML += `[${timestamp}] ${message}\n`;
  debugOutput.scrollTop = debugOutput.scrollHeight;
}

function clearDebug() {
  document.getElementById('debug-output').innerHTML = 'Debug cleared...\n';
}

async function testApproveReservation() {
  debugLog('Testing approve reservation...');
  
  try {
    const response = await fetch('/api/reservations/edit.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({
        id: 1,
        status: 'accepted',
        approved_by: <?php echo $_SESSION['user_id'] ?? 'null'; ?>,
        approved_at: new Date().toISOString().slice(0, 19).replace('T', ' ')
      }),
      credentials: 'same-origin'
    });
    
    debugLog(`Response status: ${response.status}`);
    debugLog(`Response headers: ${JSON.stringify([...response.headers.entries()])}`);
    
    const result = await response.json();
    debugLog(`Response body: ${JSON.stringify(result, null, 2)}`);
    
    if (response.ok) {
      popupSystem.success('Test approve successful!');
      updateTestRowStatus(1, 'accepted');
    } else {
      popupSystem.error('Test approve failed: ' + (result.error || 'Unknown error'));
    }
  } catch (error) {
    debugLog(`Error: ${error.message}`);
    popupSystem.error('Network error: ' + error.message);
  }
}

async function testRejectReservation() {
  debugLog('Testing reject reservation...');
  
  try {
    const response = await fetch('/api/reservations/edit.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({
        id: 1,
        status: 'rejected',
        cancellation_reason: 'Test rejection reason',
        cancelled_at: new Date().toISOString().slice(0, 19).replace('T', ' ')
      }),
      credentials: 'same-origin'
    });
    
    debugLog(`Response status: ${response.status}`);
    debugLog(`Response headers: ${JSON.stringify([...response.headers.entries()])}`);
    
    const result = await response.json();
    debugLog(`Response body: ${JSON.stringify(result, null, 2)}`);
    
    if (response.ok) {
      popupSystem.success('Test reject successful!');
      updateTestRowStatus(1, 'rejected');
    } else {
      popupSystem.error('Test reject failed: ' + (result.error || 'Unknown error'));
    }
  } catch (error) {
    debugLog(`Error: ${error.message}`);
    popupSystem.error('Network error: ' + error.message);
  }
}

async function testAPIDirectly() {
  debugLog('Testing API endpoint directly...');
  
  try {
    // First, let's check if the reservation exists
    const checkResponse = await fetch('/api/reservations/edit.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({
        id: 999999 // Non-existent ID
      }),
      credentials: 'same-origin'
    });
    
    const checkResult = await checkResponse.json();
    debugLog(`Check non-existent reservation: ${JSON.stringify(checkResult)}`);
    
    // Now test with minimal data
    const minimalResponse = await fetch('/api/reservations/edit.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({
        id: 1,
        status: 'pending' // Just change to same status
      }),
      credentials: 'same-origin'
    });
    
    const minimalResult = await minimalResponse.json();
    debugLog(`Minimal update test: ${JSON.stringify(minimalResult)}`);
    
  } catch (error) {
    debugLog(`API test error: ${error.message}`);
  }
}

function updateTestRowStatus(id, status) {
  const statusElement = document.getElementById(`status-${id}`);
  if (statusElement) {
    let statusClass = '';
    let statusText = '';
    
    switch (status) {
      case 'accepted':
        statusClass = 'bg-green-100 text-green-800';
        statusText = 'Accepted';
        break;
      case 'rejected':
        statusClass = 'bg-red-100 text-red-800';
        statusText = 'Rejected';
        break;
      default:
        statusClass = 'bg-yellow-100 text-yellow-800';
        statusText = 'Pending';
    }
    
    statusElement.className = `inline-flex px-2 py-1 text-xs font-semibold rounded-full ${statusClass}`;
    statusElement.textContent = statusText;
  }
}

// Include the same functions from Reservations.php for testing
async function approveReservation(reservationId) {
  debugLog(`Approve reservation called with ID: ${reservationId}`);
  
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
    
    if (!confirmed) {
      debugLog('User cancelled approval');
      return;
    }
    
    const response = await fetch('/api/reservations/edit.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({
        id: reservationId,
        status: 'accepted',
        approved_by: <?php echo $_SESSION['user_id'] ?? 'null'; ?>,
        approved_at: new Date().toISOString().slice(0, 19).replace('T', ' ')
      }),
      credentials: 'same-origin'
    });
    
    debugLog(`Approve response status: ${response.status}`);
    const result = await response.json();
    debugLog(`Approve response: ${JSON.stringify(result)}`);
    
    if (response.ok) {
      popupSystem.success('Reservation approved successfully!');
      updateTestRowStatus(reservationId, 'accepted');
    } else {
      popupSystem.error(result.error || 'Failed to approve reservation');
    }
  } catch (error) {
    debugLog(`Approve error: ${error.message}`);
    popupSystem.error('Network error: ' + error.message);
  }
}

async function rejectReservation(reservationId) {
  debugLog(`Reject reservation called with ID: ${reservationId}`);
  
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
    
    if (reason === null) {
      debugLog('User cancelled rejection');
      return;
    }
    
    const confirmed = await popupSystem.confirm(
      `Are you sure you want to reject this reservation?\n\nReason: ${reason}`,
      'Confirm Rejection',
      {
        confirmText: 'Reject',
        cancelText: 'Cancel',
        type: 'danger'
      }
    );
    
    if (!confirmed) {
      debugLog('User cancelled rejection confirmation');
      return;
    }
    
    const response = await fetch('/api/reservations/edit.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({
        id: reservationId,
        status: 'rejected',
        cancellation_reason: reason.trim(),
        cancelled_at: new Date().toISOString().slice(0, 19).replace('T', ' ')
      }),
      credentials: 'same-origin'
    });
    
    debugLog(`Reject response status: ${response.status}`);
    const result = await response.json();
    debugLog(`Reject response: ${JSON.stringify(result)}`);
    
    if (response.ok) {
      popupSystem.success('Reservation rejected successfully!');
      updateTestRowStatus(reservationId, 'rejected');
    } else {
      popupSystem.error(result.error || 'Failed to reject reservation');
    }
  } catch (error) {
    debugLog(`Reject error: ${error.message}`);
    popupSystem.error('Network error: ' + error.message);
  }
}

// Initialize debug
debugLog('Reservation test page loaded');
debugLog(`User ID: <?php echo $_SESSION['user_id'] ?? 'Not logged in'; ?>`);
</script>
