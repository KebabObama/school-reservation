<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
if (!isset($_SESSION['user_id'])) {
  header('Location: /');
  exit;
}
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/permissions.php';
$user_id = $_SESSION['user_id'];
$canViewRooms = canViewRooms($user_id);
$canViewReservations = canViewReservations($user_id);
try {
  $totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
  $totalRooms = $pdo->query("SELECT COUNT(*) FROM rooms WHERE is_active = 1")->fetchColumn();
  $totalReservations = $pdo->query("SELECT COUNT(*) FROM reservations")->fetchColumn();
  $pendingReservations = $pdo->query("SELECT COUNT(*) FROM reservations WHERE status = 'pending'")->fetchColumn();
  $recentReservations = $pdo->query("
        SELECT r.*, u.name as user_name, u.surname as user_surname, rm.name as room_name
        FROM reservations r
        JOIN users u ON r.user_id = u.id
        JOIN rooms rm ON r.room_id = rm.id
        ORDER BY r.created_at DESC
        LIMIT 5
    ")->fetchAll();
  $todayReservations = $pdo->query("
        SELECT r.*, u.name as user_name, u.surname as user_surname, rm.name as room_name
        FROM reservations r
        JOIN users u ON r.user_id = u.id
        JOIN rooms rm ON r.room_id = rm.id
        WHERE DATE(r.start_time) = CURDATE()
        ORDER BY r.start_time ASC
    ")->fetchAll();
} catch (Exception $e) {
  $totalUsers = $totalRooms = $totalReservations = $pendingReservations = 0;
  $recentReservations = $todayReservations = [];
}
$user_name = htmlspecialchars($_SESSION['user_name'] ?? 'User');
$user_surname = htmlspecialchars($_SESSION['user_surname'] ?? '');
?>
<div class="space-y-6">
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    <div class="bg-white rounded-lg shadow p-6">
      <div class="flex items-center">
        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z">
            </path>
          </svg>
        </div>
        <div class="ml-4">
          <p class="text-sm font-medium text-gray-600">Total Users</p>
          <p class="text-2xl font-semibold text-gray-900"><?php echo $totalUsers; ?></p>
        </div>
      </div>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
      <div class="flex items-center">
        <div class="p-3 rounded-full bg-green-100 text-green-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
            </path>
          </svg>
        </div>
        <div class="ml-4">
          <p class="text-sm font-medium text-gray-600">Active Rooms</p>
          <p class="text-2xl font-semibold text-gray-900"><?php echo $totalRooms; ?></p>
        </div>
      </div>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
      <div class="flex items-center">
        <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
          </svg>
        </div>
        <div class="ml-4">
          <p class="text-sm font-medium text-gray-600">Total Reservations</p>
          <p class="text-2xl font-semibold text-gray-900"><?php echo $totalReservations; ?></p>
        </div>
      </div>
    </div>
    <div class="flex items-center">
      <div class="p-3 rounded-full bg-red-100 text-red-600">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
      </div>
      <div class="ml-4">
        <p class="text-sm font-medium text-gray-600">Pending Approval</p>
        <p class="text-2xl font-semibold text-gray-900"><?php echo $pendingReservations; ?></p>
      </div>
    </div>
  </div>
</div>
<?php
$hasQuickActions = $canViewRooms || $canViewReservations;
if ($hasQuickActions): ?>
  <div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <?php if ($canViewRooms): ?>
        <button onclick="loadPage('Rooms')"
          class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
          <svg class="w-8 h-8 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
            </path>
          </svg>
          <div class="text-left">
            <p class="font-medium text-gray-900">Manage Rooms</p>
            <p class="text-sm text-gray-600">Add, edit, or view rooms</p>
          </div>
        </button>
      <?php endif; ?>
      <?php if ($canViewReservations): ?>
        <button onclick="loadPage('Reservations')"
          class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
          <svg class="w-8 h-8 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
          </svg>
          <div class="text-left">
            <p class="font-medium text-gray-900">View Reservations</p>
            <p class="text-sm text-gray-600">Manage bookings and approvals</p>
          </div>
        </button>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
  <div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Today's Reservations</h2>
    <?php if (empty($todayReservations)): ?>
      <p class="text-gray-600">No reservations scheduled for today.</p>
    <?php else: ?>
      <div class="space-y-3">
        <?php foreach ($todayReservations as $reservation): ?>
          <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
            <div>
              <p class="font-medium text-gray-900"><?php echo htmlspecialchars($reservation['title']); ?></p>
              <p class="text-sm text-gray-600"><?php echo htmlspecialchars($reservation['room_name']); ?></p>
              <p class="text-sm text-gray-600">
                <?php echo htmlspecialchars($reservation['user_name'] . ' ' . $reservation['user_surname']); ?></p>
            </div>
            <div class="text-right">
              <p class="text-sm font-medium text-gray-900">
                <?php echo date('H:i', strtotime($reservation['start_time'])); ?></p>
              <span
                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                  <?php echo $reservation['status'] === 'accepted' ? 'bg-green-100 text-green-800' : ($reservation['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?>">
                <?php echo ucfirst($reservation['status']); ?>
              </span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
  <div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Recent Reservations</h2>
    <?php if (empty($recentReservations)): ?>
      <p class="text-gray-600">No recent reservations.</p>
    <?php else: ?>
      <div class="space-y-3">
        <?php foreach ($recentReservations as $reservation): ?>
          <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
            <div>
              <p class="font-medium text-gray-900"><?php echo htmlspecialchars($reservation['title']); ?></p>
              <p class="text-sm text-gray-600"><?php echo htmlspecialchars($reservation['room_name']); ?></p>
              <p class="text-sm text-gray-600">
                <?php echo htmlspecialchars($reservation['user_name'] . ' ' . $reservation['user_surname']); ?></p>
            </div>
            <div class="text-right">
              <p class="text-sm text-gray-600"><?php echo date('M j, Y', strtotime($reservation['start_time'])); ?></p>
              <span
                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                  <?php echo $reservation['status'] === 'accepted' ? 'bg-green-100 text-green-800' : ($reservation['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?>">
                <?php echo ucfirst($reservation['status']); ?>
              </span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
</div>