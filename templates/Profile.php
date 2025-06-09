<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
if (!isset($_SESSION['user_id'])) {
  header('Location: /');
  exit;
}
require_once __DIR__ . '/../lib/db.php';
try {
  $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
  $stmt->execute([$_SESSION['user_id']]);
  $user = $stmt->fetch();
  $stmt = $pdo->prepare("
        SELECT
            COUNT(*) as total_reservations,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_reservations,
            SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted_reservations,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_reservations
        FROM reservations
        WHERE user_id = ?
    ");
  $stmt->execute([$_SESSION['user_id']]);
  $stats = $stmt->fetch();
} catch (Exception $e) {
  $user = null;
  $stats = ['total_reservations' => 0, 'pending_reservations' => 0, 'accepted_reservations' => 0, 'rejected_reservations' => 0];
}
if (!$user) {
  echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">User not found</h1></div>';
  exit;
}
?>
<div class="space-y-6">
  <div class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-lg p-6 text-white">
    <div class="flex items-center">
      <div class="h-20 w-20 rounded-full bg-white bg-opacity-20 flex items-center justify-center text-2xl font-bold">
        <?php echo strtoupper(substr($user['name'], 0, 1) . substr($user['surname'], 0, 1)); ?>
      </div>
      <div class="ml-6">
        <h1 class="text-3xl font-bold"><?php echo htmlspecialchars($user['name'] . ' ' . $user['surname']); ?></h1>
        <p class="text-blue-100"><?php echo htmlspecialchars($user['email']); ?></p>
      </div>
    </div>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
    <div class="bg-white rounded-lg shadow p-6">
      <div class="flex items-center">
        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
          </svg>
        </div>
        <div class="ml-4">
          <p class="text-sm font-medium text-gray-600">Total Reservations</p>
          <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['total_reservations']; ?></p>
        </div>
      </div>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
      <div class="flex items-center">
        <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
        </div>
        <div class="ml-4">
          <p class="text-sm font-medium text-gray-600">Pending</p>
          <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['pending_reservations']; ?></p>
        </div>
      </div>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
      <div class="flex items-center">
        <div class="p-3 rounded-full bg-green-100 text-green-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
          </svg>
        </div>
        <div class="ml-4">
          <p class="text-sm font-medium text-gray-600">Accepted</p>
          <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['accepted_reservations']; ?></p>
        </div>
      </div>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
      <div class="flex items-center">
        <div class="p-3 rounded-full bg-red-100 text-red-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </div>
        <div class="ml-4">
          <p class="text-sm font-medium text-gray-600">Rejected</p>
          <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['rejected_reservations']; ?></p>
        </div>
      </div>
    </div>
  </div>
  <div class="bg-white rounded-lg shadow p-6">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-lg font-semibold text-gray-900">Profile Information</h2>
      <button onclick="loadPage('EditProfile')"
        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">
        Edit Profile
      </button>
    </div>
    <div class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700">First Name</label>
        <div class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['name']); ?></div>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Last Name</label>
        <div class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['surname']); ?></div>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Email Address</label>
        <div class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['email']); ?></div>
      </div>
    </div>
  </div>
</div>