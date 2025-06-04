<?php
function renderDeleteRoomButton($roomId, $roomName)
{
?>
  <button class="text-gray-400 hover:text-red-600" aria-label="Delete room <?php echo htmlspecialchars($roomName); ?>"
    title="Delete Room" onclick="(async (event) => {
    const confirmed = await popupSystem.confirm(
      'Are you sure you want to delete this room? This action cannot be undone.',
      'Delete Room',
      {
        confirmText: 'Delete',
        cancelText: 'Cancel',
        type: 'danger'
      }
    );
    if (!confirmed) return;

    event.currentTarget.closest('.bg-white.rounded-lg.shadow').classList.add('hidden');
    try {
      const response = await fetch('/api/rooms/delete.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ id: <?php echo (int)$roomId; ?> }),
        credentials: 'same-origin'
      });
      if (!response.ok) {
        const errorText = await response.text();
        popupSystem.error('Failed to delete room: ' + errorText);
        return;
      }
    } catch (error) {
      popupSystem.error('Error deleting room: ' + error.message);
    }
  })(event)">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6
         m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
    </svg>
  </button>

<?php
}
?>