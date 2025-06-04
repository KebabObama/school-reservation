<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!isset($_SESSION['user_id'])) {
  header('Location: /');
  exit;
}

require_once __DIR__ . '/../lib/db.php';

// Get reservation purposes
try {
  $purposes = $pdo->query("
        SELECT rp.*, COUNT(r.id) as usage_count 
        FROM reservation_purposes rp 
        LEFT JOIN reservations r ON rp.id = r.purpose_id
        GROUP BY rp.id 
        ORDER BY rp.name
    ")->fetchAll();
} catch (Exception $e) {
  $purposes = [];
}
?>

<div class="space-y-6">
  <!-- Header -->
  <div class="flex justify-between items-center">
    <div>
      <h1 class="text-3xl font-bold text-gray-900">Reservation Purposes</h1>
      <p class="text-gray-600">Manage the different purposes for room reservations</p>
    </div>
    <button onclick="loadPage('CreatePurpose')"
      class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
      <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
      </svg>
      Add Purpose
    </button>
  </div>

  <!-- Purposes Table -->
  <?php if (empty($purposes)): ?>
    <div class="bg-white rounded-lg shadow p-8 text-center">
      <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
        </path>
      </svg>
      <h3 class="text-lg font-medium text-gray-900 mb-2">No reservation purposes found</h3>
      <p class="text-gray-600 mb-4">Get started by creating your first reservation purpose.</p>
      <button onclick="loadPage('CreatePurpose')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
        Create Purpose
      </button>
    </div>
  <?php else: ?>
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Purpose
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Description
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Approval Required
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Usage Count
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Created
            </th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
              Actions
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <?php foreach ($purposes as $purpose): ?>
            <tr class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">
                  <?php echo htmlspecialchars($purpose['name']); ?>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="text-sm text-gray-600 max-w-xs truncate">
                  <?php echo htmlspecialchars($purpose['description'] ?? 'No description'); ?>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <?php if ($purpose['requires_approval']): ?>
                  <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                    Required
                  </span>
                <?php else: ?>
                  <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                    Not Required
                  </span>
                <?php endif; ?>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                <?php echo $purpose['usage_count']; ?> reservation<?php echo $purpose['usage_count'] !== 1 ? 's' : ''; ?>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                <?php echo date('M j, Y', strtotime($purpose['created_at'])); ?>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div class="flex justify-end space-x-2">
                  <button onclick="(async function() {
                    const purposeId = <?php echo $purpose['id']; ?>;
                    const currentName = '<?php echo addslashes($purpose['name']); ?>';
                    const currentDescription = '<?php echo addslashes($purpose['description'] ?? ''); ?>';
                    const currentRequiresApproval = <?php echo $purpose['requires_approval'] ? 'true' : 'false'; ?>;

                    const newName = prompt('Enter new purpose name:', currentName);
                    if (newName === null || newName.trim() === '') return;

                    const newDescription = prompt('Enter new description:', currentDescription);
                    const requiresApproval = confirm('Should this purpose require approval?');

                    try {
                      const response = await fetch('/api/purposes/edit.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({
                          id: purposeId,
                          name: newName.trim(),
                          description: newDescription?.trim() || '',
                          requires_approval: requiresApproval
                        }),
                        credentials: 'same-origin'
                      });

                      const result = await response.json();
                      if (response.ok) {
                        alert('Purpose updated successfully!');
                        location.reload();
                      } else {
                        alert('Error: ' + (result.error || 'Unknown error'));
                      }
                    } catch (error) {
                      alert('Network error: ' + error.message);
                    }
                  })()" class="text-blue-600 hover:text-blue-900" title="Edit Purpose">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                      </path>
                    </svg>
                  </button>
                  <button onclick="(async function() {
                    const purposeId = <?php echo $purpose['id']; ?>;
                    const purposeName = '<?php echo addslashes($purpose['name']); ?>';
                    const usageCount = <?php echo $purpose['usage_count']; ?>;

                    if (usageCount > 0) {
                      popupSystem.warning('Cannot delete this purpose. It is currently used in ' + usageCount + ' reservation(s).', 'Cannot Delete');
                      return;
                    }

                    const confirmed = await popupSystem.confirm(
                      'Are you sure you want to delete this purpose?',
                      'Delete Purpose',
                      {
                        confirmText: 'Delete',
                        cancelText: 'Cancel',
                        type: 'danger'
                      }
                    );

                    if (!confirmed) return;

                    try {
                    const response=await fetch('/api/purposes/delete.php', {
                    method: 'POST' ,
                    headers: {'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: purposeId }),
                    credentials: 'same-origin'
                    });

                    const result=await response.json();
                    if (response.ok) {
                    popupSystem.success('Purpose deleted successfully!');
                    location.reload();
                    } else {
                    popupSystem.error(result.error || 'Unknown error occurred');
                    }
                    } catch (error) {
                    popupSystem.error('Network error: ' + error.message);
                    }
                  })()" class="text-red-600 hover:text-red-900" title="Delete Purpose">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
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
  <?php endif; ?>
</div>