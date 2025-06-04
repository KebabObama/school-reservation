<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Access Denied</h1><p>Please log in to access this page.</p></div>';
  exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Method Not Allowed</h1><p>Only POST requests are accepted.</p></div>';
  exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['page'])) {
  http_response_code(400);
  echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Bad Request</h1><p>Page parameter is required.</p></div>';
  exit;
}

$page = $data['page'];
$allowed_pages = [
  'Dashboard', 'Rooms', 'CreateRoom', 'EditRoom', 
  'Reservations', 'CreateReservation', 'EditReservation', 
  'UserPage', 'Profile', 'EditProfile', 
  'RoomTypes', 'CreateRoomType', 'EditRoomType', 
  'ReservationPurposes', 'CreatePurpose', 'EditPurpose', 
  'Permissions', 'PermissionChanges', 'ProfileVerification', 
  'PopupDemo', 'ReservationTest'
];

if (!in_array($page, $allowed_pages)) {
  http_response_code(404);
  echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Page Not Found</h1><p>The requested page does not exist.</p></div>';
  exit;
}

$template_file = __DIR__ . '/../../templates/' . $page . '.php';
if (!file_exists($template_file)) {
  http_response_code(404);
  echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Template Not Found</h1><p>The requested template file does not exist.</p></div>';
  exit;
}

// Include the template
try {
  require $template_file;
} catch (Exception $e) {
  http_response_code(500);
  echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Server Error</h1><p>An error occurred while loading the page: ' . htmlspecialchars($e->getMessage()) . '</p></div>';
}
?>
