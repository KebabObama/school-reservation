<?php
header('Content-Type: text/html; charset=UTF-8');

require_once __DIR__ . '/../../lib/token_middleware.php';

// Authenticate using token or session
$userData = TokenMiddleware::authenticate();

if (!$userData) {
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

// Parse URL-style parameters from page name (e.g., "EditRoom&id=123")
$urlParams = [];
if (strpos($page, '&') !== false) {
  $parts = explode('&', $page);
  $page = $parts[0]; // The actual page name

  // Parse the parameters
  for ($i = 1; $i < count($parts); $i++) {
    if (strpos($parts[$i], '=') !== false) {
      list($key, $value) = explode('=', $parts[$i], 2);
      $urlParams[$key] = $value;
    }
  }
}
$allowed_pages = [
  'Dashboard',
  'Buildings',
  'CreateBuilding',
  'EditBuilding',
  'Floors',
  'CreateFloor',
  'EditFloor',
  'Rooms',
  'CreateRoom',
  'EditRoom',
  'Reservations',
  'CreateReservation',
  'EditReservation',
  'Profile',
  'EditProfile',
  'RoomTypes',
  'CreateRoomType',
  'EditRoomType',
  'ReservationPurposes',
  'CreatePurpose',
  'EditPurpose',
  'Permissions',
  'PermissionChanges',
  'PopupDemo',
  'ReservationTest'
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

// Set up GET parameters for templates that need them
// First, check for parameters in the JSON data
if (isset($data['room_id'])) {
  $_GET['room_id'] = $data['room_id'];
}
if (isset($data['reservation_id'])) {
  $_GET['reservation_id'] = $data['reservation_id'];
}
if (isset($data['room_type_id'])) {
  $_GET['room_type_id'] = $data['room_type_id'];
}
if (isset($data['purpose_id'])) {
  $_GET['purpose_id'] = $data['purpose_id'];
}

// Then, check for parameters parsed from URL-style format
if (isset($urlParams['id'])) {
  $_GET['id'] = $urlParams['id'];
}
if (isset($urlParams['room_id'])) {
  $_GET['room_id'] = $urlParams['room_id'];
}
if (isset($urlParams['reservation_id'])) {
  $_GET['reservation_id'] = $urlParams['reservation_id'];
}
if (isset($urlParams['room_type_id'])) {
  $_GET['room_type_id'] = $urlParams['room_type_id'];
}
if (isset($urlParams['purpose_id'])) {
  $_GET['purpose_id'] = $urlParams['purpose_id'];
}

// Include the template
try {
  require $template_file;
} catch (Exception $e) {
  http_response_code(500);
  echo '<div class="p-6"><h1 class="text-2xl font-bold text-red-600">Server Error</h1><p>An error occurred while loading the page: ' . htmlspecialchars($e->getMessage()) . '</p></div>';
}
